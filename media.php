<?php
$currentPage = 'media';
$pageTitle   = 'Biblioteca de Mídias';
require_once __DIR__ . '/../includes/header.php';

$msg = '';

// Delete media
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $mediaId = (int)$_POST['id'];
    $media   = dbFetch("SELECT * FROM media WHERE id=?", [$mediaId]);
    if ($media) {
        @unlink(UPLOAD_PATH . '/videos/' . $media['filename']);
        @unlink(UPLOAD_PATH . '/images/' . $media['filename']);
        @unlink(UPLOAD_PATH . '/thumbs/' . $media['thumb']);
        dbQuery("DELETE FROM media WHERE id=?", [$mediaId]);
        $msg = 'success:Mídia removida.';
    }
}

$mediaList = dbFetchAll("SELECT * FROM media ORDER BY uploaded_at DESC");
$totalSize = array_sum(array_column($mediaList, 'size'));
?>

<?php if ($msg): [$t,$txt] = explode(':',$msg,2); ?>
<div class="alert alert-<?= $t ?>"><i class="fa-solid fa-check-circle"></i> <?= sanitize($txt) ?></div>
<?php endif; ?>

<!-- Upload Area -->
<div class="card" style="margin-bottom:1.5rem">
  <div class="card-header">
    <h3><i class="fa-solid fa-cloud-arrow-up"></i> Upload de Mídias</h3>
    <span class="badge badge-blue"><?= count($mediaList) ?> arquivo(s) · <?= formatBytes((int)$totalSize) ?></span>
  </div>
  <div class="card-body">
    <div id="uploadArea" class="upload-area" ondragover="event.preventDefault()" ondrop="handleDrop(event)">
      <i class="fa-solid fa-cloud-arrow-up upload-icon"></i>
      <p>Arraste vídeos ou imagens aqui</p>
      <p style="color:var(--muted);font-size:.85rem">MP4, AVI, MOV, MKV, WebM, JPG, PNG, GIF, WebP e mais</p>
      <label class="btn btn-primary" style="cursor:pointer">
        <i class="fa-solid fa-folder-open"></i> Selecionar Arquivos
        <input type="file" id="fileInput" multiple accept="video/*,image/*" style="display:none" onchange="uploadFiles(this.files)">
      </label>
    </div>
    <div id="uploadQueue"></div>
  </div>
</div>

<!-- Media Grid -->
<div class="card">
  <div class="card-header">
    <h3><i class="fa-solid fa-photo-film"></i> Mídias</h3>
    <div class="search-box">
      <i class="fa-solid fa-magnifying-glass"></i>
      <input type="text" id="mediaSearch" placeholder="Buscar..." oninput="filterMedia(this.value)">
    </div>
  </div>
  <div class="card-body p0">
    <?php if (empty($mediaList)): ?>
      <p class="empty-msg"><i class="fa-solid fa-photo-film"></i> Nenhuma mídia ainda. Faça upload acima.</p>
    <?php else: ?>
    <table class="table" id="mediaTable">
      <thead>
        <tr>
          <th>Prévia</th>
          <th>Nome</th>
          <th>Tipo</th>
          <th>Tamanho</th>
          <th>Duração</th>
          <th>Upload</th>
          <th>Ações</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($mediaList as $m): ?>
      <tr data-name="<?= strtolower(sanitize($m['original_name'])) ?>">
        <td>
          <?php
          $baseUrl = BASE_URL . '/uploads/';
          if ($m['type'] === 'video') {
              $src = $m['thumb']
                  ? $baseUrl . 'thumbs/' . $m['thumb']
                  : 'data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 80 50"><rect fill="%23222" width="80" height="50"/><text x="40" y="30" fill="%23fff" text-anchor="middle" font-size="20">▶</text></svg>';
              echo '<img src="'.sanitize($src).'" class="media-thumb" alt="">';
          } else {
              $src = $baseUrl . 'images/' . $m['filename'];
              echo '<img src="'.sanitize($src).'" class="media-thumb" alt="">';
          }
          ?>
        </td>
        <td><strong><?= sanitize($m['original_name']) ?></strong></td>
        <td>
          <span class="badge <?= $m['type']==='video'?'badge-purple':'badge-blue' ?>">
            <i class="fa-solid fa-<?= $m['type']==='video'?'film':'image' ?>"></i>
            <?= ucfirst($m['type']) ?>
          </span>
        </td>
        <td><?= formatBytes((int)$m['size']) ?></td>
        <td><?= $m['duration'] > 0 ? formatDuration((float)$m['duration']) : '—' ?></td>
        <td><?= date('d/m/y H:i', strtotime($m['uploaded_at'])) ?></td>
        <td class="actions">
          <form method="POST" style="display:inline" onsubmit="return confirm('Remover esta mídia?')">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= $m['id'] ?>">
            <button type="submit" class="btn btn-sm btn-red" title="Remover"><i class="fa-solid fa-trash"></i></button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>
</div>

<script>
const BASE_URL = '<?= BASE_URL ?>';

function filterMedia(q) {
    q = q.toLowerCase();
    document.querySelectorAll('#mediaTable tbody tr').forEach(tr => {
        tr.style.display = tr.dataset.name.includes(q) ? '' : 'none';
    });
}

function handleDrop(e) {
    e.preventDefault();
    uploadFiles(e.dataTransfer.files);
}

function uploadFiles(files) {
    [...files].forEach(file => uploadSingleFile(file));
}

function uploadSingleFile(file) {
    const queue = document.getElementById('uploadQueue');
    const id    = 'up_' + Date.now() + Math.random();

    const div = document.createElement('div');
    div.className = 'upload-item';
    div.id = id;
    div.innerHTML = `
        <div class="upload-item-info">
            <span class="upload-name">${escHtml(file.name)}</span>
            <span class="upload-size">${formatBytes(file.size)}</span>
        </div>
        <div class="progress-bar"><div class="progress-fill" style="width:0%"></div></div>
        <span class="upload-status">Enviando...</span>`;
    queue.prepend(div);

    const formData = new FormData();
    formData.append('file', file);

    const xhr = new XMLHttpRequest();
    xhr.upload.onprogress = e => {
        if (e.lengthComputable) {
            const pct = Math.round(e.loaded / e.total * 100);
            div.querySelector('.progress-fill').style.width = pct + '%';
            div.querySelector('.upload-status').textContent  = pct + '%';
        }
    };
    xhr.onload = () => {
        try {
            const res = JSON.parse(xhr.responseText);
            if (res.success) {
                div.querySelector('.upload-status').textContent = '✅ Concluído';
                div.querySelector('.progress-fill').style.background = 'var(--green)';
                setTimeout(() => location.reload(), 1200);
            } else {
                div.querySelector('.upload-status').textContent = '❌ ' + (res.error || 'Erro');
                div.querySelector('.progress-fill').style.background = 'var(--red)';
            }
        } catch(e) { div.querySelector('.upload-status').textContent = '❌ Erro inesperado'; }
    };
    xhr.onerror = () => { div.querySelector('.upload-status').textContent = '❌ Falha na conexão'; };
    xhr.open('POST', BASE_URL + '/api/upload.php');
    xhr.send(formData);
}

function formatBytes(b) {
    if (b >= 1073741824) return (b/1073741824).toFixed(2)+' GB';
    if (b >= 1048576)    return (b/1048576).toFixed(2)+' MB';
    if (b >= 1024)       return (b/1024).toFixed(2)+' KB';
    return b+' B';
}
function escHtml(s){ const d=document.createElement('div');d.textContent=s;return d.innerHTML; }
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
