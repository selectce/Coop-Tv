<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

// GET: return timeline for a store
if ($method === 'GET') {
    $storeId = (int)($_GET['store'] ?? 0);
    if (!$storeId) { jsonResponse(['error'=>'store required'], 400); }

    $store = dbFetch("SELECT * FROM stores WHERE id=?", [$storeId]);
    if (!$store) { jsonResponse(['error'=>'store not found'], 404); }

    $items = dbFetchAll("
        SELECT t.id, t.media_id, t.position,
               COALESCE(t.duration, m.duration) as duration,
               m.original_name, m.type, m.filename, m.thumb
        FROM timeline_items t
        JOIN media m ON t.media_id = m.id
        WHERE t.store_id = ?
        ORDER BY t.position
    ", [$storeId]);

    $baseUrl = BASE_URL . '/uploads/';
    foreach ($items as &$item) {
        $item['url'] = $baseUrl . ($item['type']==='video' ? 'videos' : 'images') . '/' . $item['filename'];
        $item['thumb_url'] = $item['thumb'] ? $baseUrl . 'thumbs/' . $item['thumb'] : null;
        $item['duration']  = (float)$item['duration'];
    }

    jsonResponse([
        'store'   => $store,
        'items'   => $items,
        'count'   => count($items),
        'total_duration' => array_sum(array_column($items, 'duration')),
    ]);
}

// POST: save timeline
if ($method === 'POST') {
    if (!isLoggedIn()) { jsonResponse(['error'=>'unauthorized'], 401); }

    $body    = json_decode(file_get_contents('php://input'), true);
    $storeId = (int)($body['store_id'] ?? 0);
    $items   = $body['items'] ?? [];

    if (!$storeId) { jsonResponse(['error'=>'store_id required'], 400); }

    // Delete existing
    dbQuery("DELETE FROM timeline_items WHERE store_id=?", [$storeId]);

    // Insert new
    foreach ($items as $i => $item) {
        $mediaId  = (int)($item['media_id'] ?? 0);
        $position = (int)($item['position'] ?? $i);
        $duration = isset($item['duration']) && $item['duration'] > 0 ? (float)$item['duration'] : null;

        if (!$mediaId) continue;

        dbInsert('timeline_items', [
            'store_id' => $storeId,
            'media_id' => $mediaId,
            'position' => $position,
            'duration' => $duration,
        ]);
    }

    jsonResponse(['success'=>true,'count'=>count($items)]);
}

jsonResponse(['error'=>'method not allowed'], 405);
