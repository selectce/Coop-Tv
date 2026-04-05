<?php
$currentPage = 'stores';
$pageTitle   = 'Configurações do Ponto';
require_once __DIR__ . '/../includes/header.php';

$storeId = (int)($_GET['store'] ?? 0);
$store   = $storeId ? dbFetch("SELECT * FROM stores WHERE id=?", [$storeId]) : null;
if (!$store) { echo '<p class="alert alert-error">Ponto não encontrado.</p>'; require_once __DIR__.'/../includes/footer.php'; exit; }

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orientation   = $_POST['orientation'] === 'portrait' ? 'portrait' : 'landscape';
    $order         = $_POST['playback_order'] === 'random' ? 'random' : 'sequential';
    $singleMode    = isset($_POST['single_video_mode']) ? 1 : 0;
    $singleVideoId = (int)($_POST['single_video_id'] ?? 0);
    $showControls  = isset($_POST['show_controls']) ? 1 : 0;

    dbUpdate('stores', [
        'orientation'      => $orientation,
        'playback_order'   => $order,
        'single_video_mode'=> $singleMode,
        'single_video_id'  => $singleVideoId ?: null,
        'show_controls'    => $showControls,
    ], 'id=?', [$storeId]);

    $store = dbFetch("SELECT * FROM stores WHERE id=?", [$storeId]);
    $msg = 'success:Configurações salvas!';
}

$videos = dbFetchAll("SELECT id, original_name FROM media WHERE type='video' ORDER BY original_name");
?>

<?php if ($msg): [$t,$txt] = explode(':',$msg,2); ?>
<div class="alert alert-<?= $t ?>"><i class="fa-solid fa-check-circle"></i> <?= sanitize($txt) ?></div>
<?php endif; ?>

<div class="dash-grid dash-grid-1col">
<div class="card">
  <div class="card-header">
    <h3><i class="fa-solid fa-gear"></i> <?= sanitize($store['name']) ?></h3>
    <a href="<?= storeUrl($store) ?>" target="_blank" class="btn btn-green btn-sm"><i class="fa-solid fa-tv"></i> Abrir Player</a>
  </div>
  <div class="card-body">
  <form method="POST">

    <h4 class="settings-section">📺 Configurações de Exibição</h4>
    <div class="field-row">
      <div class="field">
        <label>Orientação da TV</label>
        <select name="orientation">
          <option value="landscape" <?= $store['orientation']==='landscape'?'selected':'' ?>>🖥 Horizontal (Paisagem)</option>
          <option value="portrait"  <?= $store['orientation']==='portrait'?'selected':'' ?>>📱 Vertical (Retrato)</option>
        </select>
      </div>
      <div class="field">
        <label>Ordem de Reprodução</label>
        <select name="playback_order">
          <option value="sequential" <?= $store['playback_order']==='sequential'?'selected':'' ?>>▶ Sequencial (ordem da timeline)</option>
          <option value="random"     <?= $store['playback_order']==='random'?'selected':'' ?>>🔀 Aleatória</option>
        </select>
      </div>
    </div>

    <h4 class="settings-section">🎬 Modo de Vídeo Único</h4>
    <div class="field">
      <label class="checkbox-label">
        <input type="checkbox" name="single_video_mode" value="1" <?= $store['single_video_mode']?'checked':'' ?>>
        Exibir apenas um único vídeo (ignora timeline)
      </label>
    </div>
    <div class="field" id="singleVideoField" <?= !$store['single_video_mode']?'style="display:none"':'' ?>>
      <label>Selecionar Vídeo</label>
      <select name="single_video_id">
        <option value="">— selecione —</option>
        <?php foreach ($videos as $v): ?>
          <option value="<?= $v['id'] ?>" <?= $store['single_video_id']==$v['id']?'selected':'' ?>>
            <?= sanitize($v['original_name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <h4 class="settings-section">🎮 Controle Remoto</h4>
    <div class="field">
      <label class="checkbox-label">
        <input type="checkbox" name="show_controls" value="1" <?= $store['show_controls']?'checked':'' ?>>
        Exibir barra de controles ao pressionar qualquer botão do controle remoto
      </label>
      <small>Mostra botões: ⏸ Pausar / ▶ Continuar / 🔄 Atualizar / ⚙ Configurações</small>
    </div>

    <div class="btn-row">
      <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Salvar Configurações</button>
      <a href="<?= BASE_URL ?>/admin/stores.php" class="btn">← Voltar</a>
    </div>
  </form>

  <hr style="margin:2rem 0">
  <h4 class="settings-section">📺 Player — URL e QR Code</h4>
  <div class="url-box">
    <code id="playerUrl"><?= storeUrl($store) ?></code>
    <button class="btn btn-sm" onclick="copyUrl()"><i class="fa-solid fa-copy"></i> Copiar</button>
    <a href="<?= storeUrl($store) ?>" target="_blank" class="btn btn-sm btn-green"><i class="fa-solid fa-tv"></i> Abrir</a>
  </div>

  <!-- QR Code para abrir na TV -->
  <div class="qr-box" style="margin-top:1.2rem">
    <p style="font-size:.82rem;color:var(--muted);text-align:center;margin-bottom:.5rem">
      📱 Escaneie o QR Code com o celular ou com a câmera da TV para abrir o player
    </p>
    <div id="qrcode"></div>
    <button class="btn btn-sm" onclick="window.print()" style="margin-top:.5rem">
      <i class="fa-solid fa-print"></i> Imprimir QR Code
    </button>
  </div>

  <div style="background:var(--bg3);border:1px solid var(--border);border-radius:10px;padding:1rem;margin-top:1rem;font-size:.82rem;line-height:1.8;color:var(--muted)">
    <strong style="color:var(--text)">💡 Como instalar como app na TV:</strong><br>
    <strong>TV Smart (LG/Samsung):</strong> Abra o navegador da TV → acesse a URL → menu do browser → "Adicionar à tela inicial"<br>
    <strong>Android TV / Fire Stick:</strong> Abra o Chrome → acesse a URL → ⋮ menu → "Adicionar à tela inicial" (instala como PWA)<br>
    <strong>Aparelhinhos (Chromecast/Mi Box):</strong> Mesmo processo do Android TV via Chrome<br>
    <strong>Qualquer TV com HDMI:</strong> Conecte um notebook/PC, abra Chrome em modo quiosque:<br>
    <code style="font-size:.75rem;display:block;margin-top:.3rem;padding:.4rem .6rem;background:var(--bg);border-radius:6px">chrome --kiosk --noerrdialogs <?= storeUrl($store) ?></code>
  </div>
  </div>
</div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
document.querySelector('[name="single_video_mode"]').addEventListener('change', function(){
    document.getElementById('singleVideoField').style.display = this.checked ? '' : 'none';
});
function copyUrl() {
    navigator.clipboard.writeText(document.getElementById('playerUrl').textContent);
    showToast('✅ URL copiada!');
}
// Gerar QR Code
new QRCode(document.getElementById('qrcode'), {
    text: document.getElementById('playerUrl').textContent,
    width: 180, height: 180,
    colorDark: getComputedStyle(document.documentElement).getPropertyValue('--text').trim() || '#000',
    colorLight: 'transparent',
    correctLevel: QRCode.CorrectLevel.M
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
