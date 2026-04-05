<?php
$currentPage = 'users';
$pageTitle   = 'Usuários';
require_once __DIR__ . '/../includes/header.php';

$me  = currentUser();
$msg = '';

// ---- Actions ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Add user
    if ($action === 'add') {
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $confirm  = trim($_POST['confirm']  ?? '');

        if (!$username)             { $msg = 'error:Nome de usuário é obrigatório.'; }
        elseif (strlen($password)<6){ $msg = 'error:Senha deve ter ao menos 6 caracteres.'; }
        elseif ($password!==$confirm){ $msg = 'error:Senhas não conferem.'; }
        else {
            $exists = dbFetch("SELECT id FROM users WHERE username=?", [$username]);
            if ($exists) { $msg = 'error:Usuário já existe.'; }
            else {
                dbInsert('users', ['username'=>$username,'password'=>password_hash($password,PASSWORD_BCRYPT)]);
                $msg = 'success:Usuário criado com sucesso!';
            }
        }
    }

    // Delete user
    if ($action === 'delete') {
        $uid = (int)($_POST['id'] ?? 0);
        if ($uid === (int)$me['id']) { $msg = 'error:Você não pode remover seu próprio usuário.'; }
        else {
            dbQuery("DELETE FROM users WHERE id=?", [$uid]);
            $msg = 'success:Usuário removido.';
        }
    }

    // Change password
    if ($action === 'changepass') {
        $uid      = (int)($_POST['id'] ?? 0);
        $newpass  = trim($_POST['newpass']  ?? '');
        $confirm  = trim($_POST['confirm2'] ?? '');
        if (strlen($newpass)<6)       { $msg = 'error:Senha deve ter ao menos 6 caracteres.'; }
        elseif ($newpass !== $confirm) { $msg = 'error:Senhas não conferem.'; }
        else {
            dbUpdate('users', ['password'=>password_hash($newpass,PASSWORD_BCRYPT)], 'id=?', [$uid]);
            $msg = 'success:Senha alterada com sucesso!';
        }
    }
}

$users = dbFetchAll("SELECT id, username, created_at FROM users ORDER BY id ASC");
?>

<?php if ($msg): [$t,$txt]=explode(':',$msg,2); ?>
<div class="alert alert-<?= $t ?>"><i class="fa-solid fa-<?= $t==='success'?'check':'circle-xmark' ?>-circle"></i> <?= sanitize($txt) ?></div>
<?php endif; ?>

<div class="dash-grid">

  <!-- Add user form -->
  <div class="card">
    <div class="card-header">
      <h3><i class="fa-solid fa-user-plus"></i> Novo Usuário</h3>
    </div>
    <div class="card-body">
      <form method="POST" autocomplete="off">
        <input type="hidden" name="action" value="add">
        <div class="field">
          <label>Nome de Usuário</label>
          <div class="input-icon">
            <i class="fa-solid fa-user"></i>
            <input type="text" name="username" required autocomplete="new-password" placeholder="ex: operador01">
          </div>
        </div>
        <div class="field">
          <label>Senha</label>
          <div class="input-icon">
            <i class="fa-solid fa-lock"></i>
            <input type="password" name="password" required autocomplete="new-password" placeholder="mínimo 6 caracteres">
          </div>
        </div>
        <div class="field">
          <label>Confirmar Senha</label>
          <div class="input-icon">
            <i class="fa-solid fa-lock"></i>
            <input type="password" name="confirm" required placeholder="repita a senha">
          </div>
        </div>
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Criar Usuário</button>
      </form>
    </div>
  </div>

  <!-- Users list -->
  <div class="card">
    <div class="card-header">
      <h3><i class="fa-solid fa-users"></i> Usuários (<?= count($users) ?>)</h3>
    </div>
    <div class="card-body p0">
      <table class="table">
        <thead>
          <tr>
            <th>#</th>
            <th>Usuário</th>
            <th>Criado em</th>
            <th>Ações</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($users as $u): ?>
        <tr>
          <td><?= $u['id'] ?></td>
          <td>
            <strong><?= sanitize($u['username']) ?></strong>
            <?php if ($u['id']==$me['id']): ?>
              <span class="badge badge-blue" style="margin-left:.4rem">Você</span>
            <?php endif; ?>
          </td>
          <td><?= date('d/m/Y H:i', strtotime($u['created_at'])) ?></td>
          <td class="actions">
            <!-- Change password button -->
            <button class="btn btn-sm btn-blue" onclick="openPassModal(<?= $u['id'] ?>, '<?= sanitize($u['username']) ?>')" title="Alterar Senha">
              <i class="fa-solid fa-key"></i>
            </button>
            <!-- Delete -->
            <?php if ($u['id']!=$me['id']): ?>
            <form method="POST" style="display:inline" onsubmit="return confirm('Remover o usuário <?= sanitize($u['username']) ?>?')">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= $u['id'] ?>">
              <button type="submit" class="btn btn-sm btn-red" title="Remover">
                <i class="fa-solid fa-trash"></i>
              </button>
            </form>
            <?php else: ?>
            <button class="btn btn-sm" disabled title="Não pode remover a si mesmo"><i class="fa-solid fa-ban"></i></button>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>

<!-- Change Password Modal -->
<div id="passModal" class="modal-overlay" style="display:none" onclick="if(event.target===this)closePassModal()">
  <div class="modal-box">
    <div class="modal-header">
      <h3><i class="fa-solid fa-key"></i> Alterar Senha — <span id="modalUsername"></span></h3>
      <button class="modal-close" onclick="closePassModal()"><i class="fa-solid fa-times"></i></button>
    </div>
    <form method="POST" autocomplete="off" class="modal-body">
      <input type="hidden" name="action" value="changepass">
      <input type="hidden" name="id" id="modalUserId">
      <div class="field">
        <label>Nova Senha</label>
        <div class="input-icon">
          <i class="fa-solid fa-lock"></i>
          <input type="password" name="newpass" required placeholder="mínimo 6 caracteres" autocomplete="new-password">
        </div>
      </div>
      <div class="field">
        <label>Confirmar Nova Senha</label>
        <div class="input-icon">
          <i class="fa-solid fa-lock"></i>
          <input type="password" name="confirm2" required placeholder="repita a nova senha">
        </div>
      </div>
      <div class="btn-row">
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Salvar</button>
        <button type="button" class="btn" onclick="closePassModal()">Cancelar</button>
      </div>
    </form>
  </div>
</div>

<style>
.modal-overlay {
  position:fixed; inset:0;
  background:rgba(0,0,0,.6);
  backdrop-filter:blur(4px);
  display:flex; align-items:center; justify-content:center;
  z-index:500;
}
.modal-box {
  background:var(--bg2);
  border:1px solid var(--border);
  border-radius:16px;
  width:100%; max-width:420px;
  box-shadow:var(--shadow);
  overflow:hidden;
}
.modal-header {
  display:flex; align-items:center; justify-content:space-between;
  padding:1rem 1.25rem;
  border-bottom:1px solid var(--border);
}
.modal-header h3 { font-size:.95rem; font-weight:600; display:flex; align-items:center; gap:.5rem; }
.modal-close {
  background:none; border:none; color:var(--muted);
  cursor:pointer; font-size:1rem; padding:.3rem;
  border-radius:6px;
}
.modal-close:hover { background:var(--bg3); color:var(--text); }
.modal-body { padding:1.25rem; }
</style>

<script>
function openPassModal(id, username) {
  document.getElementById('modalUserId').value    = id;
  document.getElementById('modalUsername').textContent = username;
  document.getElementById('passModal').style.display = 'flex';
}
function closePassModal() {
  document.getElementById('passModal').style.display = 'none';
}
document.addEventListener('keydown', e => { if (e.key==='Escape') closePassModal(); });
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
