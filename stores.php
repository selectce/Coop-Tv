<?php
$currentPage = 'stores';
$pageTitle   = 'Pontos de Exibição';
require_once __DIR__ . '/../includes/header.php';

// Actions
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id   = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $type = $_POST['type'] === 'franchise' ? 'franchise' : 'own';
        $slg  = $id ? trim($_POST['slug'] ?? '') : slug($name);

        if (!$name) { $msg = 'error:Nome é obrigatório.'; goto end; }

        if ($id) {
            dbUpdate('stores', ['name'=>$name,'type'=>$type,'slug'=>$slg], 'id=?', [$id]);
            $msg = 'success:Loja atualizada!';
        } else {
            dbInsert('stores', ['name'=>$name,'type'=>$type,'slug'=>$slg]);
            $msg = 'success:Loja criada!';
        }
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        dbQuery("DELETE FROM stores WHERE id=?", [$id]);
        $msg = 'success:Loja removida.';
    }
}
end:

$stores = dbFetchAll("
    SELECT s.*, COUNT(t.id) as items
    FROM stores s
    LEFT JOIN timeline_items t ON s.id = t.store_id
    GROUP BY s.id ORDER BY s.type, s.name
");

$editStore = null;
if (!empty($_GET['edit'])) {
    $editStore = dbFetch("SELECT * FROM stores WHERE id=?", [(int)$_GET['edit']]);
}
?>

<?php if ($msg): [$type,$text] = explode(':',$msg,2); ?>
<div class="alert alert-<?= $type ?>"><i class="fa-solid fa-<?= $type==='success'?'check':'xmark' ?>-circle"></i> <?= sanitize($text) ?></div>
<?php endif; ?>

<div class="dash-grid">
<!-- Form -->
<div class="card">
  <div class="card-header">
    <h3><i class="fa-solid fa-<?= $editStore?'pen':'plus' ?>"></i> <?= $editStore?'Editar Ponto':'Novo Ponto' ?></h3>
  </div>
  <div class="card-body">
  <form method="POST">
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="id" value="<?= $editStore['id'] ?? 0 ?>">
    <div class="field">
      <label>Nome do Ponto</label>
      <input type="text" name="name" required value="<?= sanitize($editStore['name'] ?? '') ?>" placeholder="Ex: Loja Centro">
    </div>
    <?php if ($editStore): ?>
    <div class="field">
      <label>Slug (URL)</label>
      <input type="text" name="slug" required value="<?= sanitize($editStore['slug'] ?? '') ?>">
      <small>Não use espaços. Usado na URL do player.</small>
    </div>
    <?php endif; ?>
    <div class="field">
      <label>Tipo</label>
      <select name="type">
        <option value="own"       <?= ($editStore['type']??'own')==='own'?'selected':'' ?>>Própria</option>
        <option value="franchise" <?= ($editStore['type']??'')==='franchise'?'selected':'' ?>>Franquia</option>
      </select>
    </div>
    <div class="btn-row">
      <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Salvar</button>
      <?php if ($editStore): ?>
        <a href="<?= BASE_URL ?>/admin/stores.php" class="btn">Cancelar</a>
      <?php endif; ?>
    </div>
  </form>
  </div>
</div>

<!-- List -->
<div class="card">
  <div class="card-header">
    <h3><i class="fa-solid fa-list"></i> Todos os Pontos (<?= count($stores) ?>)</h3>
  </div>
  <div class="card-body p0">
  <table class="table">
    <thead><tr><th>Nome</th><th>Tipo</th><th>Slug</th><th>Itens</th><th>Ações</th></tr></thead>
    <tbody>
    <?php foreach ($stores as $s): ?>
    <tr>
      <td><strong><?= sanitize($s['name']) ?></strong></td>
      <td><span class="badge <?= $s['type']==='own'?'badge-blue':'badge-orange' ?>"><?= $s['type']==='own'?'Própria':'Franquia' ?></span></td>
      <td><code><?= sanitize($s['slug']) ?></code></td>
      <td><?= $s['items'] ?></td>
      <td class="actions">
        <a href="?edit=<?= $s['id'] ?>" class="btn btn-sm" title="Editar"><i class="fa-solid fa-pen"></i></a>
        <a href="<?= BASE_URL ?>/admin/timeline.php?store=<?= $s['id'] ?>" class="btn btn-sm btn-purple" title="Timeline"><i class="fa-solid fa-film"></i></a>
        <a href="<?= BASE_URL ?>/admin/settings.php?store=<?= $s['id'] ?>" class="btn btn-sm btn-blue" title="Configurações"><i class="fa-solid fa-gear"></i></a>
        <a href="<?= storeUrl($s) ?>" target="_blank" class="btn btn-sm btn-green" title="Abrir Player"><i class="fa-solid fa-tv"></i></a>
        <form method="POST" style="display:inline" onsubmit="return confirm('Remover este ponto?')">
          <input type="hidden" name="action" value="delete">
          <input type="hidden" name="id" value="<?= $s['id'] ?>">
          <button type="submit" class="btn btn-sm btn-red" title="Remover"><i class="fa-solid fa-trash"></i></button>
        </form>
      </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
</div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
