<?php
// manage_users.php – Gestão de Usuários (Admin)
session_start();
require_once 'includes/auth_check.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
requireAdmin();

$pageTitle = 'Gestão de Usuários';
$db  = getDB();
$me  = currentUser();
$msg = '';

// Toggle ativo/inativo
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['toggle_id'])){
    $tid=(int)$_POST['toggle_id'];
    if($tid!==$me['id']){
        $row=$db->prepare('SELECT is_active FROM users WHERE id=?');
        $row->execute([$tid]); $cur=$row->fetchColumn();
        $db->prepare('UPDATE users SET is_active=? WHERE id=?')->execute([!$cur,$tid]);
        $msg = !$cur ? 'Usuário ativado.' : 'Usuário desativado.';
    }
}

// Mudar papel
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['role_id'])){
    $rid=(int)$_POST['role_id'];
    $role=in_array($_POST['new_role'],['admin','agent'])?$_POST['new_role']:'agent';
    if($rid!==$me['id']){
        $db->prepare('UPDATE users SET role=? WHERE id=?')->execute([$role,$rid]);
        $msg='Papel do usuário alterado.';
    }
}

// Carregar usuários com contagem de pesquisas
$users = $db->query("
    SELECT u.*, COUNT(r.id) cnt
    FROM users u
    LEFT JOIN responses r ON r.agent_id=u.id
    GROUP BY u.id
    ORDER BY u.role DESC, u.created_at DESC
")->fetchAll();

$totalUsers  = count($users);
$adminCount  = count(array_filter($users,fn($u)=>$u['role']==='admin'));
$agentCount  = count(array_filter($users,fn($u)=>$u['role']==='agent'));
$activeCount = count(array_filter($users,fn($u)=>$u['is_active']));

include 'includes/header.php';
?>

<div class="page-hdr">
  <div>
    <h1 class="page-title"><i class="fas fa-user-cog"></i>Gestão de Usuários</h1>
    <p class="page-sub"><?=$totalUsers?> usuário<?=$totalUsers!==1?'s':''?> cadastrado<?=$totalUsers!==1?'s':''?></p>
  </div>
  <div class="page-acts">
    <a href="/register.php" class="btn btn-primary"><i class="fas fa-user-plus"></i>Novo usuário</a>
  </div>
</div>

<?php if($msg): ?><div class="alert alert-success auto-dismiss"><i class="fas fa-check-circle"></i><?=htmlspecialchars($msg)?></div><?php endif; ?>

<!-- Stats mini -->
<div class="kpi-grid" style="grid-template-columns:repeat(4,1fr);margin-bottom:24px">
  <div class="kpi" style="--kpi-color:#E53935">
    <div class="kpi-icon"><i class="fas fa-users"></i></div>
    <span class="kpi-val"><?=$totalUsers?></span>
    <span class="kpi-lbl">Total de Usuários</span>
  </div>
  <div class="kpi" style="--kpi-color:#FB8C00">
    <div class="kpi-icon"><i class="fas fa-user-shield"></i></div>
    <span class="kpi-val"><?=$adminCount?></span>
    <span class="kpi-lbl">Administradores</span>
  </div>
  <div class="kpi" style="--kpi-color:#43A047">
    <div class="kpi-icon"><i class="fas fa-user-tie"></i></div>
    <span class="kpi-val"><?=$agentCount?></span>
    <span class="kpi-lbl">Agentes</span>
  </div>
  <div class="kpi" style="--kpi-color:#1565C0">
    <div class="kpi-icon"><i class="fas fa-circle-check"></i></div>
    <span class="kpi-val"><?=$activeCount?></span>
    <span class="kpi-lbl">Usuários Ativos</span>
  </div>
</div>

<div class="section-card">
  <div class="table-wrap">
    <table class="dtable">
      <thead><tr>
        <th>Usuário</th>
        <th>E-mail</th>
        <th>Papel</th>
        <th>Pesquisas</th>
        <th>Status</th>
        <th>Cadastro</th>
        <th>Ações</th>
      </tr></thead>
      <tbody>
      <?php foreach($users as $usr): ?>
      <tr class="<?=!$usr['is_active']?'row-inactive':''?>">
        <td>
          <div class="table-user">
            <div class="t-avatar" style="background:<?=htmlspecialchars($usr['avatar_color'])?>">
              <?=getInitials($usr['name'])?>
            </div>
            <div>
              <span class="t-name">
                <?=htmlspecialchars($usr['name'])?>
                <?php if($usr['id']===$me['id']): ?><span class="badge-you">Você</span><?php endif; ?>
              </span>
            </div>
          </div>
        </td>
        <td style="font-size:.83rem"><?=htmlspecialchars($usr['email'])?></td>
        <td>
          <?php if($usr['id']!==$me['id']): ?>
          <form method="POST" class="inline-form">
            <input type="hidden" name="role_id" value="<?=$usr['id']?>">
            <select name="new_role" class="role-sel" onchange="this.form.submit()">
              <option value="admin" <?=$usr['role']==='admin'?'selected':''?>>Admin</option>
              <option value="agent" <?=$usr['role']==='agent'?'selected':''?>>Agente</option>
            </select>
          </form>
          <?php else: ?>
          <span class="sb-role role-<?=$usr['role']?>" style="font-size:.75rem;padding:3px 10px">
            <?=$usr['role']==='admin'?'Admin':'Agente'?>
          </span>
          <?php endif; ?>
        </td>
        <td><span class="count-badge"><?=$usr['cnt']?></span></td>
        <td>
          <span class="status-badge <?=$usr['is_active']?'s-active':'s-inactive'?>">
            <i class="fas fa-circle"></i><?=$usr['is_active']?'Ativo':'Inativo'?>
          </span>
        </td>
        <td><span class="date-sm"><?=formatDate($usr['created_at'])?></span></td>
        <td>
          <?php if($usr['id']!==$me['id']): ?>
          <form method="POST" class="inline-form">
            <input type="hidden" name="toggle_id" value="<?=$usr['id']?>">
            <button type="submit"
                    class="act-btn <?=$usr['is_active']?'act-disable':'act-enable'?>"
                    title="<?=$usr['is_active']?'Desativar':'Ativar'?>">
              <i class="fas fa-<?=$usr['is_active']?'ban':'check'?>"></i>
            </button>
          </form>
          <?php else: ?>
          <span class="text-muted" style="font-size:.8rem;padding:6px">—</span>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include 'includes/footer.php'; ?>
