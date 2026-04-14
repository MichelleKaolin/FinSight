<?php
// agent_challenges.php – Passo 2: Desafios Financeiros
session_start();
require_once 'includes/auth_check.php';
require_once 'includes/functions.php';
requireLogin();
if (!isset($_SESSION['c_step1'])) { header('Location: /agent_collect.php'); exit; }
$pageTitle = 'Nova Pesquisa – Desafios';
$error = '';

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $ok  = ['debt','lack_control','low_income','no_credit','fraud_risk','illiteracy'];
    $raw = array_filter($_POST['challenges'] ?? [], fn($c)=>in_array($c,$ok));
    if (!$raw)          $error = 'Selecione ao menos 1 desafio.';
    elseif(count($raw)>3) $error = 'Selecione no máximo 3 desafios.';
    else {
        $_SESSION['c_step2'] = array_values($raw);
        header('Location: /agent_register.php'); exit;
    }
}

$challenges = [
    ['key'=>'debt',         'label'=>'Dívidas',                    'ico'=>'fas fa-file-invoice-dollar', 'color'=>'#E53935'],
    ['key'=>'lack_control', 'label'=>'Falta de controle financeiro','ico'=>'fas fa-sliders',             'color'=>'#FB8C00'],
    ['key'=>'low_income',   'label'=>'Renda baixa',                'ico'=>'fas fa-arrow-trend-down',    'color'=>'#FDD835'],
    ['key'=>'no_credit',    'label'=>'Sem acesso a crédito',       'ico'=>'fas fa-ban',                 'color'=>'#1565C0'],
    ['key'=>'fraud_risk',   'label'=>'Risco de fraude',            'ico'=>'fas fa-user-secret',         'color'=>'#6A1B9A'],
    ['key'=>'illiteracy',   'label'=>'Analfabetismo financeiro',   'ico'=>'fas fa-book-open',           'color'=>'#43A047'],
];
$selected = $_SESSION['c_step2'] ?? [];
include 'includes/header.php';
?>
<div class="page-hdr">
  <div>
    <h1 class="page-title"><i class="fas fa-plus-circle"></i>Nova Pesquisa</h1>
    <p class="page-sub">Desafios financeiros do entrevistado</p>
  </div>
</div>

<div class="collect-steps">
  <div class="step-item done"><div class="step-num"><i class="fas fa-check"></i></div><span class="step-lbl">Preferência</span></div>
  <div class="step-conn done"></div>
  <div class="step-item active"><div class="step-num">2</div><span class="step-lbl">Desafios</span></div>
  <div class="step-conn"></div>
  <div class="step-item"><div class="step-num">3</div><span class="step-lbl">Dados</span></div>
</div>

<div class="collect-wrap">
  <div class="collect-card">
    <span class="step-badge">Passo 2 de 3</span>
    <h2 class="collect-ttl">Quais desafios o entrevistado enfrenta?</h2>
    <p class="collect-hint">Selecione até <strong>3 desafios</strong> &nbsp;<span id="cntDisp" class="count-disp">(0/3 selecionados)</span></p>

    <?php if($error): ?><div class="alert alert-error"><i class="fas fa-exclamation-circle"></i><?=$error?></div><?php endif; ?>

    <form method="POST" id="chalForm">
      <div class="chal-grid">
      <?php foreach($challenges as $ch): ?>
        <label class="chal-lbl" for="c_<?=$ch['key']?>">
          <input type="checkbox" name="challenges[]" id="c_<?=$ch['key']?>"
                 value="<?=$ch['key']?>"
                 <?=in_array($ch['key'],$selected)?'checked':''?>
                 onchange="updCount()">
          <div class="chal-inner" style="--ch-color:<?=$ch['color']?>">
            <div class="chal-ico-wrap"><i class="<?=$ch['ico']?>"></i></div>
            <span class="chal-lbl-text"><?=$ch['label']?></span>
          </div>
        </label>
      <?php endforeach; ?>
      </div>

      <div class="collect-nav">
        <a href="/agent_collect.php" class="btn btn-outline"><i class="fas fa-arrow-left"></i>Voltar</a>
        <button type="submit" class="btn btn-primary" id="nxtBtn" disabled>
          Próximo passo <i class="fas fa-arrow-right"></i>
        </button>
      </div>
    </form>
  </div>
</div>

<script>
function updCount() {
  const cbs  = document.querySelectorAll('input[name="challenges[]"]');
  const chk  = [...cbs].filter(c=>c.checked);
  const cnt  = chk.length;
  document.getElementById('cntDisp').textContent = `(${cnt}/3 selecionados)`;
  document.getElementById('cntDisp').className = 'count-disp ' + (cnt>=3?'count-max':cnt>0?'count-ok':'');
  document.getElementById('nxtBtn').disabled = cnt===0;
  cbs.forEach(c=>{ if(!c.checked) c.disabled=(cnt>=3); });
  // update visual
  document.querySelectorAll('.chal-lbl').forEach(lbl=>{
    lbl.querySelector('.chal-inner').style.opacity = lbl.querySelector('input').disabled ? '.4' : '';
  });
}
document.addEventListener('DOMContentLoaded', updCount);
</script>
<?php include 'includes/footer.php'; ?>
