<?php
// agent_collect.php – Passo 1: Preferência Financeira
session_start();
require_once 'includes/auth_check.php';
require_once 'includes/functions.php';
requireLogin();
$pageTitle = 'Nova Pesquisa';

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $p = sanitize($_POST['preference'] ?? '');
    if (in_array($p, ['credit','investment','savings','loan','card'])) {
        $_SESSION['c_step1'] = $p;
        header('Location: /agent_challenges.php'); exit;
    }
}

$prefs = [
    ['key'=>'credit',     'label'=>'Crédito',     'ico'=>'fas fa-credit-card',       'desc'=>'Crédito pessoal',             'color'=>'#E53935'],
    ['key'=>'investment', 'label'=>'Investimento', 'ico'=>'fas fa-chart-line',         'desc'=>'Perfil de investidor',        'color'=>'#43A047'],
    ['key'=>'savings',    'label'=>'Poupança',     'ico'=>'fas fa-piggy-bank',          'desc'=>'Hábitos de economia',         'color'=>'#1565C0'],
    ['key'=>'loan',       'label'=>'Empréstimo',   'ico'=>'fas fa-hand-holding-dollar', 'desc'=>'Necessidade de financiamento','color'=>'#FB8C00'],
    ['key'=>'card',       'label'=>'Cartão',       'ico'=>'fas fa-wallet',              'desc'=>'Cartão de débito/crédito',    'color'=>'#FDD835'],
];
include 'includes/header.php';
?>
<div class="page-hdr">
  <div>
    <h1 class="page-title"><i class="fas fa-plus-circle"></i>Nova Pesquisa</h1>
    <p class="page-sub">Coleta de comportamento financeiro</p>
  </div>
</div>

<div class="collect-steps">
  <div class="step-item active"><div class="step-num">1</div><span class="step-lbl">Preferência</span></div>
  <div class="step-conn"></div>
  <div class="step-item"><div class="step-num">2</div><span class="step-lbl">Desafios</span></div>
  <div class="step-conn"></div>
  <div class="step-item"><div class="step-num">3</div><span class="step-lbl">Dados</span></div>
</div>

<div class="collect-wrap">
  <div class="collect-card">
    <span class="step-badge">Passo 1 de 3</span>
    <h2 class="collect-ttl">Qual é a preferência financeira do entrevistado?</h2>
    <p class="collect-hint">Selecione uma categoria principal</p>

    <form method="POST" id="prefForm">
      <div class="pref-grid">
      <?php foreach($prefs as $p): ?>
        <label class="pref-card-lbl" for="p_<?=$p['key']?>">
          <input type="radio" name="preference" id="p_<?=$p['key']?>"
                 value="<?=$p['key']?>"
                 <?=(($_SESSION['c_step1']??'')===$p['key']?'checked':'')?>
                 onchange="document.getElementById('prefForm').submit()">
          <div class="pref-card-inner" style="--pref-color:<?=$p['color']?>">
            <div class="pref-ico-wrap"><i class="<?=$p['ico']?>"></i></div>
            <strong><?=$p['label']?></strong>
            <small><?=$p['desc']?></small>
            <div class="pref-chk"><i class="fas fa-check"></i></div>
          </div>
        </label>
      <?php endforeach; ?>
      </div>
      <button type="submit" class="btn btn-primary btn-full" id="nextBtn">
        <i class="fas fa-arrow-right"></i> Próximo passo
      </button>
    </form>
  </div>
</div>
<?php include 'includes/footer.php'; ?>
