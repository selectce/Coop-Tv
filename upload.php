<?php
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);
@ini_set('upload_max_filesize', '2048M');
@ini_set('post_max_size', '2048M');
@ini_set('max_execution_time', '300');
@ini_set('memory_limit', '256M');

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success'=>false,'error'=>'Não autorizado']); exit;
}

if (empty($_FILES['file'])) {
    echo json_encode(['success'=>false,'error'=>'Nenhum arquivo recebido']); exit;
}

$file = $_FILES['file'];

if ($file['error'] !== UPLOAD_ERR_OK) {
    $phpErrors = [
        UPLOAD_ERR_INI_SIZE   => 'Arquivo maior que upload_max_filesize do servidor',
        UPLOAD_ERR_FORM_SIZE  => 'Arquivo maior que MAX_FILE_SIZE',
        UPLOAD_ERR_PARTIAL    => 'Upload incompleto',
        UPLOAD_ERR_NO_FILE    => 'Nenhum arquivo enviado',
        UPLOAD_ERR_NO_TMP_DIR => 'Pasta temporária não encontrada',
        UPLOAD_ERR_CANT_WRITE => 'Falha ao gravar no disco',
        UPLOAD_ERR_EXTENSION  => 'Upload bloqueado por extensão PHP',
    ];
    $msg = $phpErrors[$file['error']] ?? 'Erro código '.$file['error'];
    echo json_encode(['success'=>false,'error'=>$msg]); exit;
}

$origName = basename($file['name']);
$tmpPath  = $file['tmp_name'];
$fileSize = $file['size'];

if (!is_uploaded_file($tmpPath)) {
    echo json_encode(['success'=>false,'error'=>'Arquivo temporário inválido']); exit;
}

$mime      = detectMime($tmpPath, $origName);
$mediaType = getMediaType($mime);

if ($mediaType === 'unknown') {
    $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
    $videoExts = ['mp4','webm','ogv','avi','mov','mkv','flv','3gp','wmv','m4v','mpeg','mpg'];
    $imageExts = ['jpg','jpeg','png','gif','webp','bmp'];
    if (in_array($ext, $videoExts))      { $mediaType='video'; $mime='video/mp4'; }
    elseif (in_array($ext, $imageExts))  { $mediaType='image'; $mime='image/jpeg'; }
    else {
        echo json_encode(['success'=>false,'error'=>"Formato não suportado: .$ext"]); exit;
    }
}

$uploadBase = UPLOAD_PATH;
foreach ([$uploadBase,"$uploadBase/videos","$uploadBase/images","$uploadBase/thumbs"] as $dir) {
    if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
        echo json_encode(['success'=>false,'error'=>"Não criou pasta: $dir — permissão negada"]); exit;
    }
    if (!is_writable($dir)) {
        echo json_encode(['success'=>false,'error'=>"Sem escrita em: $dir — chmod 755"]); exit;
    }
}

$ext      = strtolower(pathinfo($origName, PATHINFO_EXTENSION)) ?: ($mediaType==='video'?'mp4':'jpg');
$filename = uniqid('media_',true).'.'.$ext;
$subdir   = $mediaType==='video' ? 'videos' : 'images';
$destPath = "$uploadBase/$subdir/$filename";

if (!move_uploaded_file($tmpPath, $destPath)) {
    echo json_encode(['success'=>false,'error'=>'Falha ao mover arquivo — verifique permissões da pasta uploads/']); exit;
}

$duration=0; $width=0; $height=0; $thumb=null;

if ($mediaType==='video') {
    $duration = getVideoDuration($destPath);
    $thumb    = generateVideoThumb($destPath, $filename, $uploadBase);
} else {
    $imgInfo = @getimagesize($destPath);
    if ($imgInfo) { $width=(int)$imgInfo[0]; $height=(int)$imgInfo[1]; }
    $duration = 10;
}

try {
    $mediaId = dbInsert('media',[
        'filename'      => $filename,
        'original_name' => $origName,
        'type'          => $mediaType,
        'mime_type'     => $mime,
        'size'          => (int)$fileSize,
        'duration'      => (float)$duration,
        'width'         => (int)$width,
        'height'        => (int)$height,
        'thumb'         => $thumb,
    ]);
} catch (Exception $e) {
    @unlink($destPath);
    echo json_encode(['success'=>false,'error'=>'BD: '.$e->getMessage()]); exit;
}

echo json_encode(['success'=>true,'id'=>$mediaId,'name'=>$origName,'type'=>$mediaType,'duration'=>$duration,'size'=>$fileSize]);

// ---- Helpers ----
function detectMime(string $path, string $origName): string {
    if (class_exists('finfo')) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $m = $finfo->file($path);
        if ($m && $m!=='application/octet-stream') return $m;
    }
    if (function_exists('mime_content_type')) {
        $m = @mime_content_type($path);
        if ($m && $m!=='application/octet-stream') return $m;
    }
    $map = [
        'mp4'=>'video/mp4','webm'=>'video/webm','mov'=>'video/quicktime',
        'avi'=>'video/x-msvideo','mkv'=>'video/x-matroska','flv'=>'video/x-flv',
        '3gp'=>'video/3gpp','wmv'=>'video/x-ms-wmv','m4v'=>'video/mp4',
        'mpeg'=>'video/mpeg','mpg'=>'video/mpeg','ogv'=>'video/ogg',
        'jpg'=>'image/jpeg','jpeg'=>'image/jpeg','png'=>'image/png',
        'gif'=>'image/gif','webp'=>'image/webp','bmp'=>'image/bmp',
    ];
    $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
    return $map[$ext] ?? 'application/octet-stream';
}

function getVideoDuration(string $path): float {
    if (commandExists('ffprobe')) {
        $out = safeExec('ffprobe -v quiet -show_entries format=duration -of csv=p=0 '.escapeshellarg($path).' 2>/dev/null');
        if ($out && is_numeric(trim($out)) && (float)trim($out)>0) return round((float)trim($out),2);
    }
    if (commandExists('ffmpeg')) {
        $out = safeExec('ffmpeg -i '.escapeshellarg($path).' 2>&1');
        if ($out && preg_match('/Duration:\s*(\d+):(\d+):([\d.]+)/',$out,$m))
            return round($m[1]*3600+$m[2]*60+(float)$m[3],2);
    }
    return 0;
}

function generateVideoThumb(string $videoPath, string $filename, string $uploadBase): ?string {
    if (!commandExists('ffmpeg')) return null;
    $thumbName = pathinfo($filename,PATHINFO_FILENAME).'.jpg';
    $thumbPath = "$uploadBase/thumbs/$thumbName";
    safeExec('ffmpeg -ss 00:00:02 -i '.escapeshellarg($videoPath)." -vframes 1 -q:v 3 -vf 'scale=320:-1' ".escapeshellarg($thumbPath).' -y 2>/dev/null');
    return (file_exists($thumbPath) && filesize($thumbPath)>0) ? $thumbName : null;
}

function commandExists(string $cmd): bool {
    if (!function_exists('shell_exec')) return false;
    $t = safeExec("which $cmd 2>/dev/null");
    return !empty(trim($t??''));
}

function safeExec(string $cmd): ?string {
    if (!function_exists('shell_exec')) return null;
    try { return @shell_exec($cmd); } catch(Throwable $e){ return null; }
}
