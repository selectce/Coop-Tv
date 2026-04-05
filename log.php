<?php
// api/log.php — chamado pelo player a cada vez que um vídeo começa
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error'=>'POST only']); exit;
}

$body    = json_decode(file_get_contents('php://input'), true);
$storeId = (int)($body['store_id'] ?? 0);
$mediaId = (int)($body['media_id'] ?? 0);

if (!$storeId || !$mediaId) {
    echo json_encode(['error'=>'missing params']); exit;
}

dbInsert('playback_logs', [
    'store_id' => $storeId,
    'media_id' => $mediaId,
]);

echo json_encode(['success'=>true]);
