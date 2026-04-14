<?php
// dashboard.php – Painel Administrativo com Gráficos
session_start();
require_once 'includes/auth_check.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
requireAdmin('/history.php');

$pageTitle = 'Dashboard';
$db = getDB();

// ── KPIs ──────────────────────────────────────────────────
$total   = (int)$db->query('SELECT COUNT(*) FROM responses')->fetchColumn();
$agents  = (int)$db->query("SELECT COUNT(DISTINCT agent_id) FROM responses")->fetchColumn();
$highRisk= (int)$db->query("SELECT COUNT(*) FROM responses WHERE risk_level='high'")->fetchColumn();
$today   = (int)$db->query("SELECT COUNT(*) FROM responses WHERE DATE(created_at)=DATE('now')")->fetchColumn();
$week    = (int)$db->query("SELECT COUNT(*) FROM responses WHERE created_at>=DATE('now','-7 days')")->fetchColumn();

$topPrefRow = $db->query("SELECT financial_preference, COUNT(*) c FROM responses GROUP BY financial_preference ORDER BY c DESC LIMIT 1")->fetch();
$topPref    = $topPrefRow ? getPreferenceLabel($topPrefRow['financial_preference']) : '—';
$topPrefIco = $topPrefRow ? getPreferenceIcon($topPrefRow['financial_preference'])  : '—';

// ── Chart data ─────────────────────────────────────────────
$prefRows  = $db->query("SELECT financial_preference, COUNT(*) c FROM responses GROUP BY financial_preference ORDER BY c DESC")->fetchAll();
$chalRows  = $db->query("SELECT challenge, COUNT(*) c FROM response_challenges GROUP BY challenge ORDER BY c DESC")->fetchAll();
$riskRows  = $db->query("SELECT risk_level, COUNT(*) c FROM responses GROUP BY risk_level")->fetchAll();
$monthRows = $db->query("
    SELECT strftime('%m/%Y',created_at) lbl, COUNT(*) c
    FROM responses
    WHERE created_at >= DATE('now','-6 months')
    GROUP BY strftime('%Y-%m',created_at)
    ORDER BY created_at ASC
")->fetchAll();
$agentRows = $db->query("
    SELECT u.name, COUNT(r.id) c
    FROM users u LEFT JOIN responses r ON r.agent_id=u.id
    WHERE u.role='agent'
    GROUP BY u.id ORDER BY c DESC LIMIT 5
")->fetchAll();

// ── Risk map ───────────────────────────────────────────────
$riskMap = ['low'=>0,'medium'=>0,'high'=>0];
foreach($riskRows as $r) $riskMap[$r['risk_level']] = (int)$r['c'];

// ── Recent ─────────────────────────────────────────────────
$recent = $db->query("
    SELECT r.*, u.name agent_name
    FROM responses r JOIN users u ON r.agent_id=u.id
    ORDER BY r.created_at DESC LIMIT 6
")->fetchAll();
foreach($recent as &$row){
    $ch=$db->prepare('SELECT challenge FROM response_challenges WHERE response_id=?');
    $ch->execute([$row['id']]);
    $row['challenges']=$ch->fetchAll(PDO::FETCH_COLUMN);
}
unset($row);

// ── JS vars ────────────────────────────────────────────────
$jsVars = "
const PREF_LABELS  = ".json_encode(array_map(fn($r)=>getPreferenceLabel($r['financial_preference']),$prefRows)).";
const PREF_VALUES  = ".json_encode(array_column($prefRows,'c')).";
const CHAL_LABELS  = ".json_encode(array_map(fn($r)=>getChallengeLabel($r['challenge']),$chalRows)).";
const CHAL_VALUES  = ".json_encode(array_column($chalRows,'c')).";
const RISK_VALUES  = [".$riskMap['low'].",".$riskMap['medium'].",".$riskMap['high']."];
const MONTH_LABELS = ".json_encode(array_column($monthRows,'lbl')).";
const MONTH_VALUES = ".json_encode(array_column($monthRows,'c')).";
const AGENT_LABELS = ".json_encode(array_column($agentRows,'name')).";
const AGENT_VALUES = ".json_encode(array_column($agentRows,'c')).";
";

include 'includes/header.php';
?>

<!-- ── Page Header ── -->
<div class="page-hdr">
  <div>
    <h1 class="page-title"><i class="fas fa-chart-pie"></i>Dashboard</h1>
    <p class="page-sub">Visão geral das pesquisas financeiras</p>
  </div>
  <div class="page-acts">
    <a href="/agent_collect.php" class="btn btn-primary"><i class="fas fa-plus"></i>Nova Pesquisa</a>
    <button onclick="confirmDeleteAll()" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i>Limpar dados</button>
  </div>
</div>

<!-- ── KPI Grid ── -->
<div class="kpi-grid">
  <div class="kpi" style="--kpi-color:#E53935">
    <div class="kpi-icon"><i class="fas fa-clipboard-list"></i></div>
    <span class="kpi-val"><?=$total?></span>
    <span class="kpi-lbl">Total de Pesquisas</span>
    <span class="kpi-trend"><i class="fas fa-calendar-day"></i> +<?=$today?> hoje &nbsp;|&nbsp; +<?=$week?> esta semana</span>
  </div>
  <div class="kpi" style="--kpi-color:#FB8C00">
    <div class="kpi-icon"><i class="fas fa-users"></i></div>
    <span class="kpi-val"><?=$agents?></span>
    <span class="kpi-lbl">Agentes Ativos</span>
  </div>
  <div class="kpi" style="--kpi-color:#FDD835">
    <div class="kpi-icon"><?=$topPrefIco?></div>
    <span class="kpi-val" style="font-size:1.3rem"><?=$topPref?></span>
    <span class="kpi-lbl">Preferência Mais Comum</span>
  </div>
  <div class="kpi" style="--kpi-color:#E53935">
    <div class="kpi-icon"><i class="fas fa-triangle-exclamation"></i></div>
    <span class="kpi-val"><?=$highRisk?></span>
    <span class="kpi-lbl">Casos Alto Risco</span>
    <span class="kpi-trend" style="color:var(--red)"><?=$total>0?round($highRisk/$total*100):0?>% do total</span>
  </div>
</div>

<!-- ── Risk Overview Row ── -->
<div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;margin-bottom:24px">
  <div class="card" style="border-top:3px solid var(--green);padding:18px;text-align:center">
    <div style="font-size:2rem;font-family:'Syne',sans-serif;font-weight:800;color:var(--green)"><?=$riskMap['low']?></div>
    <div style="font-size:.78rem;color:var(--text-sub);font-weight:600;text-transform:uppercase;letter-spacing:.06em;margin-top:4px">🟢 Baixo Risco</div>
  </div>
  <div class="card" style="border-top:3px solid var(--yellow);padding:18px;text-align:center">
    <div style="font-size:2rem;font-family:'Syne',sans-serif;font-weight:800;color:#F57F17"><?=$riskMap['medium']?></div>
    <div style="font-size:.78rem;color:var(--text-sub);font-weight:600;text-transform:uppercase;letter-spacing:.06em;margin-top:4px">🟡 Médio Risco</div>
  </div>
  <div class="card" style="border-top:3px solid var(--red);padding:18px;text-align:center">
    <div style="font-size:2rem;font-family:'Syne',sans-serif;font-weight:800;color:var(--red)"><?=$riskMap['high']?></div>
    <div style="font-size:.78rem;color:var(--text-sub);font-weight:600;text-transform:uppercase;letter-spacing:.06em;margin-top:4px">🔴 Alto Risco</div>
  </div>
</div>

<!-- ── Charts Row 1 ── -->
<div class="charts-grid" style="margin-bottom:24px">
  <div class="chart-card">
    <div class="card-header">
      <h3 class="card-title"><i class="fas fa-chart-bar"></i>Preferências Financeiras</h3>
    </div>
    <div class="chart-canvas-wrap"><canvas id="prefChart"></canvas></div>
  </div>
  <div class="chart-card">
    <div class="card-header">
      <h3 class="card-title"><i class="fas fa-chart-pie"></i>Distribuição de Risco</h3>
    </div>
    <div class="chart-canvas-wrap"><canvas id="riskChart"></canvas></div>
    <div class="risk-legend" style="margin-top:12px">
      <div class="rl-item"><span class="rl-dot" style="background:#43A047"></span>Baixo: <?=$riskMap['low']?></div>
      <div class="rl-item"><span class="rl-dot" style="background:#FDD835"></span>Médio: <?=$riskMap['medium']?></div>
      <div class="rl-item"><span class="rl-dot" style="background:#E53935"></span>Alto: <?=$riskMap['high']?></div>
    </div>
  </div>
</div>

<!-- ── Charts Row 2 ── -->
<div class="charts-grid" style="margin-bottom:24px">
  <div class="chart-card">
    <div class="card-header">
      <h3 class="card-title"><i class="fas fa-chart-line"></i>Tendência Mensal</h3>
    </div>
    <div class="chart-canvas-wrap"><canvas id="trendChart"></canvas></div>
  </div>
  <div class="chart-card">
    <div class="card-header">
      <h3 class="card-title"><i class="fas fa-user-tie"></i>Pesquisas por Agente</h3>
    </div>
    <div class="chart-canvas-wrap"><canvas id="agentChart"></canvas></div>
  </div>
</div>

<!-- ── Challenges Full Width ── -->
<div class="chart-card" style="margin-bottom:24px">
  <div class="card-header">
    <h3 class="card-title"><i class="fas fa-exclamation-circle"></i>Principais Desafios Reportados</h3>
  </div>
  <div class="chart-canvas-wrap" style="height:200px"><canvas id="chalChart"></canvas></div>
</div>

<!-- ── Recent Table ── -->
<div class="section-card">
  <div class="section-hdr">
    <h3 class="section-title"><i class="fas fa-clock"></i>Pesquisas Recentes</h3>
    <a href="/history.php" class="btn btn-sm btn-outline">Ver todas</a>
  </div>
  <div class="table-wrap">
    <table class="dtable">
      <thead><tr>
        <th>#</th><th>Entrevistado</th><th>Preferência</th>
        <th>Desafios</th><th>Risco</th><th>Agente</th><th>Data</th><th>Ações</th>
      </tr></thead>
      <tbody>
      <?php foreach($recent as $row): ?>
        <?php $risk=getRiskLabel($row['risk_level']); ?>
        <tr>
          <td><span class="table-id">#<?=$row['id']?></span></td>
          <td>
            <div class="table-user">
              <div class="t-avatar"><?=getInitials($row['interviewee_name'])?></div>
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
          <td style="font-size:.85rem"><?=htmlspecialchars($row['agent_name'])?></td>
          <td><span class="date-sm"><?=formatDate($row['created_at'])?></span></td>
          <td>
            <div class="act-btns">
              <a href="/edit_research.php?id=<?=$row['id']?>" class="act-btn act-edit" title="Editar"><i class="fas fa-edit"></i></a>
              <button onclick="confirmDelete(<?=$row['id']?>)" class="act-btn act-del" title="Excluir"><i class="fas fa-trash"></i></button>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if(empty($recent)): ?><tr><td colspan="8" class="empty"><i class="fas fa-inbox"></i>Nenhuma pesquisa ainda</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- ── Delete Modals ── -->
<div class="modal-bg" id="deleteModal">
  <div class="modal-box">
    <div class="modal-ico modal-ico-danger"><i class="fas fa-trash"></i></div>
    <h3 class="modal-ttl">Excluir pesquisa?</h3>
    <p class="modal-desc">Esta pesquisa será removida permanentemente.</p>
    <div class="modal-acts">
      <button onclick="closeModal('deleteModal')" class="btn btn-outline">Cancelar</button>
      <button onclick="deleteRow()" class="btn btn-danger"><i class="fas fa-trash"></i> Excluir</button>
    </div>
  </div>
</div>

<div class="modal-bg" id="deleteAllModal">
  <div class="modal-box">
    <div class="modal-ico modal-ico-danger"><i class="fas fa-triangle-exclamation"></i></div>
    <h3 class="modal-ttl">Excluir TODOS os dados?</h3>
    <p class="modal-desc">Todos os registros de pesquisa serão removidos permanentemente. Essa ação não pode ser desfeita.</p>
    <div class="modal-acts">
      <button onclick="closeModal('deleteAllModal')" class="btn btn-outline">Cancelar</button>
      <button onclick="deleteAllData()" class="btn btn-danger"><i class="fas fa-trash"></i> Confirmar exclusão</button>
    </div>
  </div>
</div>

<?php
$inlineScript = $jsVars . "
// Agent chart (extra, not in main.js buildCharts)
document.addEventListener('DOMContentLoaded', function() {
  buildCharts();
  const ac = document.getElementById('agentChart');
  if(!ac || typeof AGENT_LABELS==='undefined') return;
  const c = getChartColors();
  window.fsCharts.push(new Chart(ac, {
    type:'bar',
    data:{
      labels:AGENT_LABELS,
      datasets:[{label:'Pesquisas',data:AGENT_VALUES,backgroundColor:['#E53935','#FB8C00','#FDD835','#43A047','#1565C0'],borderRadius:6}]
    },
    options:{
      responsive:true,maintainAspectRatio:false,
      plugins:{legend:{display:false},tooltip:{backgroundColor:'#0D0D0D',titleColor:'#fff',bodyColor:'rgba(255,255,255,.8)',cornerRadius:10,padding:10}},
      scales:{x:{ticks:{color:c.sub},grid:{display:false}},y:{ticks:{color:c.sub,stepSize:1},grid:{color:c.grid}}}
    }
  }));
});
";
include 'includes/footer.php';
?>
