<?php
// interviewees.php – Lista de Entrevistados com Situação de Risco
session_start();
require_once 'includes/auth_check.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
requireLogin();

$pageTitle = 'Entrevistados';
$u  = currentUser();
$db = getDB();

$fRisk   = sanitize($_GET['risk']   ?? '');
$fSearch = sanitize($_GET['search'] ?? '');

$where  = [];
$params = [];
if($u['role']!=='admin') { $where[]='r.agent_id=?'; $params[]=$u['id']; }
if($fRisk)   { $where[]='r.risk_level=?'; $params[]=$fRisk; }
if($fSearch) { $where[]='(r.interviewee_name LIKE ? OR r.interviewee_phone LIKE ?)'; $params[]="%$fSearch%"; $params[]="%$fSearch%"; }

$wStr = $where ? 'WHERE '.implode(' AND ',$where) : '';

$stmt = $db->prepare("
    SELECT r.*, u.name agent_name,
           GROUP_CONCAT(rc.challenge,',') chals
    FROM responses r
    JOIN users u ON r.agent_id=u.id
    LEFT JOIN response_challenges rc ON rc.response_id=r.id
    $wStr
    GROUP BY r.id
    ORDER BY r.risk_level DESC, r.created_at DESC
");
$stmt->execute($params);
$rows = $stmt->fetchAll();

foreach($rows as &$row){
    $row['challenges'] = $row['chals'] ? explode(',', $row['chals']) : [];
}
unset($row);

// Counts
$total  = count($rows);
$high   = count(array_filter($rows, fn($r)=>$r['risk_level']==='high'));
$medium = count(array_filter($rows, fn($r)=>$r['risk_level']==='medium'));
$low    = count(array_filter($rows, fn($r)=>$r['risk_level']==='low'));

$avatarColors = ['#E53935','#FB8C00','#43A047','#1565C0','#6A1B9A','#FDD835'];

include 'includes/header.php';
?>

<div class="page-hdr">
  <div>
    <h1 class="page-title"><i class="fas fa-users"></i>Entrevistados</h1>
    <p class="page-sub"><?=$total?> entrevistado<?=$total!==1?'s':''?> registrado<?=$total!==1?'s':''?></p>
  </div>
  <div class="page-acts">
    <a href="/agent_collect.php" class="btn btn-primary"><i class="fas fa-plus"></i>Novo</a>
  </div>
</div>

<!-- Risk summary cards -->
<div class="risk-summary">
  <button onclick="setFilter('')"       class="rs-card rs-all    <?=!$fRisk?'active':''?>">
    <span class="rs-ico"><i class="fas fa-users"></i></span>
    <span class="rs-val"><?=$total?></span>
    <span class="rs-lbl">Total</span>
  </button>
  <button onclick="setFilter('high')"   class="rs-card rs-high   <?=$fRisk==='high'  ?'active':''?>">
    <span class="rs-ico">🔴</span>
    <span class="rs-val"><?=$high?></span>
    <span class="rs-lbl">Alto Risco</span>
  </button>
  <button onclick="setFilter('medium')" class="rs-card rs-medium <?=$fRisk==='medium'?'active':''?>">
    <span class="rs-ico">🟡</span>
    <span class="rs-val"><?=$medium?></span>
    <span class="rs-lbl">Médio Risco</span>
  </button>
  <button onclick="setFilter('low')"    class="rs-card rs-low    <?=$fRisk==='low'   ?'active':''?>">
    <span class="rs-ico">🟢</span>
    <span class="rs-val"><?=$low?></span>
    <span class="rs-lbl">Baixo Risco</span>
  </button>
</div>

<!-- Busca ao vivo -->
<div class="filter-bar">
  <div class="filter-grp">
    <i class="fas fa-search filter-ico"></i>
    <input type="text" id="liveSearch" class="filter-in"
           placeholder="Buscar entrevistado..."
           value="<?=htmlspecialchars($fSearch)?>"
           oninput="doLiveSearch(this.value)">
  </div>
  <?php if($fRisk||$fSearch): ?>
  <a href="/interviewees.php" class="btn btn-ghost btn-sm"><i class="fas fa-times"></i>Limpar</a>
  <?php endif; ?>
</div>

<!-- Cards grid -->
<div class="iv-grid" id="ivGrid">
<?php foreach($rows as $i=>$row): ?>
  <?php
    $risk  = getRiskLabel($row['risk_level']);
    $color = $avatarColors[$row['id'] % count($avatarColors)];
  ?>
  <div class="iv-card" data-name="<?=strtolower(htmlspecialchars($row['interviewee_name']))?>">
    <div class="iv-card-hdr border-<?=$row['risk_level']?>">
      <div class="iv-avatar" style="background:<?=$color?>"><?=getInitials($row['interviewee_name'])?></div>
      <div style="flex:1;min-width:0">
        <div class="iv-name"><?=htmlspecialchars($row['interviewee_name'])?></div>
        <div class="iv-phone">
          <?php if($row['interviewee_phone']): ?>
          <i class="fas fa-phone" style="font-size:.65rem"></i>
          <?=htmlspecialchars($row['interviewee_phone'])?>
          <?php else: ?><span style="color:var(--text-sub)">—</span><?php endif; ?>
        </div>
      </div>
      <span class="risk-badge <?=$risk['class']?>"><?=$risk['icon']?> <?=$risk['label']?></span>
    </div>

    <div class="iv-body">
      <div class="iv-field">
        <span class="iv-field-lbl">Preferência</span>
        <span class="pref-badge"><?=getPreferenceIcon($row['financial_preference'])?> <?=getPreferenceLabel($row['financial_preference'])?></span>
      </div>
      <div class="iv-field">
        <span class="iv-field-lbl">Desafios</span>
        <div class="challenge-tags">
          <?php foreach($row['challenges'] as $c): ?>
          <span class="tag"><?=getChallengeLabel($c)?></span>
          <?php endforeach; ?>
          <?php if(empty($row['challenges'])): ?><span style="color:var(--text-sub);font-size:.8rem">Nenhum</span><?php endif; ?>
        </div>
      </div>
      <?php if($u['role']==='admin'): ?>
      <div class="iv-field">
        <span class="iv-field-lbl">Agente</span>
        <span style="font-size:.83rem"><?=htmlspecialchars($row['agent_name'])?></span>
      </div>
      <?php endif; ?>
      <?php if($row['latitude']&&$row['longitude']): ?>
      <div class="iv-field">
        <span class="iv-field-lbl">Localização</span>
        <a href="https://maps.google.com/?q=<?=$row['latitude']?>,<?=$row['longitude']?>"
           target="_blank" class="geo-link">
          <i class="fas fa-map-marker-alt"></i>Ver no mapa
        </a>
      </div>
      <?php endif; ?>
      <div class="iv-field">
        <span class="iv-field-lbl">Pesquisa realizada</span>
        <span class="date-sm"><?=formatDate($row['created_at'])?></span>
      </div>
    </div>

    <div class="iv-foot">
      <a href="/edit_research.php?id=<?=$row['id']?>" class="btn btn-sm btn-outline">
        <i class="fas fa-edit"></i>Editar
      </a>
      <?php if($u['role']==='admin'): ?>
      <button onclick="confirmDelete(<?=$row['id']?>)" class="btn btn-sm btn-danger">
        <i class="fas fa-trash"></i>
      </button>
      <?php endif; ?>
    </div>
  </div>
<?php endforeach; ?>

<?php if(empty($rows)): ?>
<div class="empty-state" style="grid-column:1/-1">
  <i class="fas fa-user-slash empty-ico"></i>
  <p>Nenhum entrevistado encontrado</p>
  <a href="/agent_collect.php" class="btn btn-primary btn-sm" style="margin-top:12px">Realizar pesquisa</a>
</div>
<?php endif; ?>
</div>

<!-- Delete modal -->
<div class="modal-bg" id="deleteModal">
  <div class="modal-box">
    <div class="modal-ico modal-ico-danger"><i class="fas fa-trash"></i></div>
    <h3 class="modal-ttl">Excluir pesquisa?</h3>
    <p class="modal-desc">O registro deste entrevistado será removido permanentemente.</p>
    <div class="modal-acts">
      <button onclick="closeModal('deleteModal')" class="btn btn-outline">Cancelar</button>
      <button onclick="deleteRow()" class="btn btn-danger"><i class="fas fa-trash"></i>Excluir</button>
    </div>
  </div>
</div>

<script>
function setFilter(risk){
  const url = new URL(window.location);
  if(risk) url.searchParams.set('risk',risk);
  else url.searchParams.delete('risk');
  url.searchParams.delete('search');
  window.location=url.toString();
}

function doLiveSearch(val){
  const t = val.toLowerCase().trim();
  document.querySelectorAll('#ivGrid .iv-card').forEach(c=>{
    c.style.display = (!t || c.dataset.name.includes(t)) ? '' : 'none';
  });
}
</script>

<?php include 'includes/footer.php'; ?>
