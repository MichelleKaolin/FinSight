<?php
// history.php – Histórico de Pesquisas
session_start();
require_once 'includes/auth_check.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
requireLogin();

$pageTitle = 'Histórico';
$u  = currentUser();
$db = getDB();

$flash = $_SESSION['flash'] ?? ''; unset($_SESSION['flash']);

// ── Filtros ────────────────────────────────────────────────
$fPref   = sanitize($_GET['preference'] ?? '');
$fRisk   = sanitize($_GET['risk']       ?? '');
$fSearch = sanitize($_GET['search']     ?? '');
$page    = max(1,(int)($_GET['page']    ?? 1));
$perPage = 12;

$where  = [];
$params = [];
if($u['role'] !== 'admin') { $where[]='r.agent_id=?'; $params[]=$u['id']; }
if($fPref)   { $where[]='r.financial_preference=?'; $params[]=$fPref; }
if($fRisk)   { $where[]='r.risk_level=?';            $params[]=$fRisk; }
if($fSearch) {
    $where[]='(r.interviewee_name LIKE ? OR r.interviewee_phone LIKE ?)';
    $params[]="%$fSearch%"; $params[]="%$fSearch%";
}
$wStr = $where ? 'WHERE '.implode(' AND ',$where) : '';

$totalRows  = (int)$db->prepare("SELECT COUNT(*) FROM responses r $wStr")->execute($params) ? $db->prepare("SELECT COUNT(*) FROM responses r $wStr")->execute($params) && ($s=$db->prepare("SELECT COUNT(*) FROM responses r $wStr")) && $s->execute($params) ? (int)$s->fetchColumn() : 0 : 0;

// rerun cleanly
$cStmt = $db->prepare("SELECT COUNT(*) FROM responses r $wStr");
$cStmt->execute($params);
$totalRows  = (int)$cStmt->fetchColumn();
$totalPages = max(1, ceil($totalRows / $perPage));
$offset     = ($page - 1) * $perPage;

$lStmt = $db->prepare("
    SELECT r.*, u.name agent_name
    FROM responses r JOIN users u ON r.agent_id=u.id
    $wStr ORDER BY r.created_at DESC LIMIT ? OFFSET ?
");
$lStmt->execute(array_merge($params, [$perPage, $offset]));
$rows = $lStmt->fetchAll();

foreach($rows as &$row){
    $cs=$db->prepare('SELECT challenge FROM response_challenges WHERE response_id=?');
    $cs->execute([$row['id']]); $row['challenges']=$cs->fetchAll(PDO::FETCH_COLUMN);
}
unset($row);

include 'includes/header.php';
?>

<div class="page-hdr">
  <div>
    <h1 class="page-title"><i class="fas fa-history"></i>Histórico de Pesquisas</h1>
    <p class="page-sub"><?=$totalRows?> registro<?=$totalRows!==1?'s':''?> encontrado<?=$totalRows!==1?'s':''?></p>
  </div>
  <div class="page-acts">
    <a href="/agent_collect.php" class="btn btn-primary"><i class="fas fa-plus"></i>Nova Pesquisa</a>
    <?php if($u['role']==='admin'): ?>
    <button onclick="confirmDeleteAll()" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i>Limpar tudo</button>
    <?php endif; ?>
  </div>
</div>

<?php if($flash): ?>
<div class="alert alert-success auto-dismiss"><i class="fas fa-check-circle"></i><?=htmlspecialchars($flash)?></div>
<?php endif; ?>

<!-- ── Filtros ── -->
<div class="filter-bar">
  <form method="GET" style="display:contents">
    <div class="filter-grp">
      <i class="fas fa-search filter-ico"></i>
      <input type="text" name="search" class="filter-in"
             placeholder="Buscar por nome ou telefone..."
             value="<?=htmlspecialchars($fSearch)?>">
    </div>
    <select name="preference" class="filter-sel">
      <option value="">Todas as preferências</option>
      <?php foreach(['credit','investment','savings','loan','card'] as $p): ?>
      <option value="<?=$p?>" <?=$fPref===$p?'selected':''?>><?=getPreferenceLabel($p)?></option>
      <?php endforeach; ?>
    </select>
    <select name="risk" class="filter-sel">
      <option value="">Todos os riscos</option>
      <option value="low"    <?=$fRisk==='low'   ?'selected':''?>>🟢 Baixo Risco</option>
      <option value="medium" <?=$fRisk==='medium'?'selected':''?>>🟡 Médio Risco</option>
      <option value="high"   <?=$fRisk==='high'  ?'selected':''?>>🔴 Alto Risco</option>
    </select>
    <button type="submit" class="btn btn-outline btn-sm"><i class="fas fa-filter"></i>Filtrar</button>
    <?php if($fPref||$fRisk||$fSearch): ?>
    <a href="/history.php" class="btn btn-ghost btn-sm"><i class="fas fa-times"></i>Limpar</a>
    <?php endif; ?>
  </form>
</div>

<!-- ── Tabela ── -->
<div class="section-card">
  <div class="table-wrap">
    <table class="dtable">
      <thead><tr>
        <th>#</th>
        <th>Entrevistado</th>
        <th>Preferência</th>
        <th>Desafios</th>
        <th>Risco</th>
        <?php if($u['role']==='admin'): ?><th>Agente</th><?php endif; ?>
        <th>Localização</th>
        <th>Data</th>
        <th>Ações</th>
      </tr></thead>
      <tbody>
      <?php foreach($rows as $row): ?>
        <?php $risk=getRiskLabel($row['risk_level']); ?>
        <tr>
          <td><span class="table-id">#<?=$row['id']?></span></td>
          <td>
            <div class="table-user">
              <div class="t-avatar" style="background:<?=['#E53935','#FB8C00','#43A047','#1565C0'][($row['id']-1)%4]?>">
                <?=getInitials($row['interviewee_name'])?>
              </div>
              <div>
                <span class="t-name"><?=htmlspecialchars($row['interviewee_name'])?></span>
                <span class="t-phone"><?=htmlspecialchars($row['interviewee_phone']??'')?></span>
              </div>
            </div>
          </td>
          <td><span class="pref-badge"><?=getPreferenceIcon($row['financial_preference'])?> <?=getPreferenceLabel($row['financial_preference'])?></span></td>
          <td>
            <div class="challenge-tags">
              <?php foreach(array_slice($row['challenges'],0,2) as $c): ?><span class="tag"><?=getChallengeLabel($c)?></span><?php endforeach; ?>
              <?php if(count($row['challenges'])>2): ?><span class="tag tag-more">+<?=count($row['challenges'])-2?></span><?php endif; ?>
            </div>
          </td>
          <td><span class="risk-badge <?=$risk['class']?>"><?=$risk['icon']?> <?=$risk['label']?></span></td>
          <?php if($u['role']==='admin'): ?>
          <td style="font-size:.83rem"><?=htmlspecialchars($row['agent_name'])?></td>
          <?php endif; ?>
          <td>
            <?php if($row['latitude']&&$row['longitude']): ?>
            <a href="https://maps.google.com/?q=<?=$row['latitude']?>,<?=$row['longitude']?>"
               target="_blank" class="geo-link" title="Abrir no Google Maps">
              <i class="fas fa-map-marker-alt"></i>
              <small><?=number_format((float)$row['latitude'],3)?></small>
            </a>
            <?php else: ?><span class="text-muted" style="font-size:.8rem">—</span><?php endif; ?>
          </td>
          <td><span class="date-sm"><?=formatDate($row['created_at'])?></span></td>
          <td>
            <div class="act-btns">
              <a href="/edit_research.php?id=<?=$row['id']?>" class="act-btn act-edit" title="Editar"><i class="fas fa-edit"></i></a>
              <?php if($u['role']==='admin'): ?>
              <button onclick="confirmDelete(<?=$row['id']?>)" class="act-btn act-del" title="Excluir"><i class="fas fa-trash"></i></button>
              <?php endif; ?>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if(empty($rows)): ?>
      <tr><td colspan="<?=$u['role']==='admin'?9:8?>" class="empty">
        <i class="fas fa-magnifying-glass"></i>Nenhuma pesquisa encontrada
      </td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Paginação -->
  <?php if($totalPages>1): ?>
  <?php
    $qs = http_build_query(array_filter(['preference'=>$fPref,'risk'=>$fRisk,'search'=>$fSearch]));
    $base = '/history.php?'.($qs?"$qs&":'');
  ?>
  <div class="pagination">
    <?php if($page>1): ?><a href="<?=$base?>page=<?=$page-1?>" class="page-btn"><i class="fas fa-chevron-left"></i></a><?php endif; ?>
    <?php for($i=max(1,$page-2);$i<=min($totalPages,$page+2);$i++): ?>
    <a href="<?=$base?>page=<?=$i?>" class="page-btn <?=$i===$page?'active':''?>"><?=$i?></a>
    <?php endfor; ?>
    <?php if($page<$totalPages): ?><a href="<?=$base?>page=<?=$page+1?>" class="page-btn"><i class="fas fa-chevron-right"></i></a><?php endif; ?>
  </div>
  <?php endif; ?>
</div>

<!-- Modals -->
<div class="modal-bg" id="deleteModal">
  <div class="modal-box">
    <div class="modal-ico modal-ico-danger"><i class="fas fa-trash"></i></div>
    <h3 class="modal-ttl">Excluir pesquisa?</h3>
    <p class="modal-desc">Esta pesquisa será removida permanentemente.</p>
    <div class="modal-acts">
      <button onclick="closeModal('deleteModal')" class="btn btn-outline">Cancelar</button>
      <button onclick="deleteRow()" class="btn btn-danger"><i class="fas fa-trash"></i>Excluir</button>
    </div>
  </div>
</div>
<div class="modal-bg" id="deleteAllModal">
  <div class="modal-box">
    <div class="modal-ico modal-ico-danger"><i class="fas fa-triangle-exclamation"></i></div>
    <h3 class="modal-ttl">Excluir TODOS os dados?</h3>
    <p class="modal-desc">Todos os registros de pesquisa serão removidos permanentemente. Não é possível desfazer.</p>
    <div class="modal-acts">
      <button onclick="closeModal('deleteAllModal')" class="btn btn-outline">Cancelar</button>
      <button onclick="deleteAllData()" class="btn btn-danger"><i class="fas fa-trash"></i>Confirmar</button>
    </div>
  </div>
</div>

<?php include 'includes/footer.php'; ?>
