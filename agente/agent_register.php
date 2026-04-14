<?php
// agent_register.php – Passo 3: Dados do Entrevistado + GPS
session_start();
require_once 'includes/auth_check.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
requireLogin();

if (!isset($_SESSION['c_step1']) || !isset($_SESSION['c_step2'])) {
    header('Location: /agent_collect.php'); exit;
}
$pageTitle = 'Nova Pesquisa – Dados';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = sanitize($_POST['name']  ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $lat   = $_POST['latitude']  !== '' ? (float)$_POST['latitude']  : null;
    $lng   = $_POST['longitude'] !== '' ? (float)$_POST['longitude'] : null;
    $notes = sanitize($_POST['notes'] ?? '');

    if (!$name) {
        $error = 'O nome do entrevistado é obrigatório.';
    } else {
        $pref  = $_SESSION['c_step1'];
        $chals = $_SESSION['c_step2'];
        $risk  = calculateRisk($chals);
        $uid   = currentUser()['id'];
        $db    = getDB();

        $db->beginTransaction();
        try {
            $s = $db->prepare('INSERT INTO responses (agent_id,interviewee_name,interviewee_phone,financial_preference,risk_level,latitude,longitude,notes) VALUES (?,?,?,?,?,?,?,?)');
            $s->execute([$uid, $name, $phone, $pref, $risk, $lat, $lng, $notes]);
            $rid = $db->lastInsertId();

            $cs = $db->prepare('INSERT INTO response_challenges (response_id,challenge) VALUES (?,?)');
            foreach ($chals as $c) $cs->execute([$rid, $c]);

            $db->commit();
            unset($_SESSION['c_step1'], $_SESSION['c_step2']);
            $_SESSION['flash'] = 'Pesquisa registrada com sucesso!';
            header('Location: /history.php'); exit;
        } catch (Exception $e) {
            $db->rollBack();
            $error = 'Erro ao salvar. Tente novamente.';
        }
    }
}
include 'includes/header.php';
?>
<div class="page-hdr">
  <div>
    <h1 class="page-title"><i class="fas fa-plus-circle"></i>Nova Pesquisa</h1>
    <p class="page-sub">Dados do entrevistado</p>
  </div>
</div>

<div class="collect-steps">
  <div class="step-item done"><div class="step-num"><i class="fas fa-check"></i></div><span class="step-lbl">Preferência</span></div>
  <div class="step-conn done"></div>
  <div class="step-item done"><div class="step-num"><i class="fas fa-check"></i></div><span class="step-lbl">Desafios</span></div>
  <div class="step-conn done"></div>
  <div class="step-item active"><div class="step-num">3</div><span class="step-lbl">Dados</span></div>
</div>

<div class="collect-wrap">
  <div class="collect-card">
    <span class="step-badge">Passo 3 de 3</span>
    <h2 class="collect-ttl">Dados do entrevistado</h2>

    <!-- Resumo dos passos anteriores -->
    <div class="collect-summary">
      <div class="sum-item">
        <span class="sum-lbl">Preferência:</span>
        <span class="pref-badge">
          <?= getPreferenceIcon($_SESSION['c_step1']) ?>
          <?= getPreferenceLabel($_SESSION['c_step1']) ?>
        </span>
      </div>
      <div class="sum-item">
        <span class="sum-lbl">Desafios:</span>
        <div class="challenge-tags">
          <?php foreach($_SESSION['c_step2'] as $c): ?>
          <span class="tag"><?= getChallengeLabel($c) ?></span>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <?php if($error): ?><div class="alert alert-error"><i class="fas fa-exclamation-circle"></i><?=$error?></div><?php endif; ?>

    <form method="POST" id="regForm">
      <div class="form-row">
        <div class="form-group">
          <label class="form-label"><i class="fas fa-user"></i>Nome completo <span class="req">*</span></label>
          <input type="text" name="name" class="form-input" placeholder="Nome do entrevistado"
                 value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label"><i class="fas fa-phone"></i>Telefone</label>
          <input type="tel" id="phoneInput" name="phone" class="form-input" placeholder="(11) 99999-9999"
                 value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
        </div>
      </div>

      <!-- Geolocalização -->
      <div class="form-group">
        <label class="form-label">
          <i class="fas fa-map-marker-alt"></i>Localização GPS
          <button type="button" id="geoBtn" onclick="captureGPS()" class="btn btn-geo btn-sm" style="margin-left:auto">
            <i class="fas fa-crosshairs"></i> Capturar GPS
          </button>
        </label>
        <div class="geo-display" id="geoDisplay">
          <span class="geo-ico"><i class="fas fa-location-dot"></i></span>
          <span id="geoText" style="color:var(--text-sub)">Localização não capturada</span>
        </div>
        <input type="hidden" name="latitude"  id="latInput"  value="">
        <input type="hidden" name="longitude" id="lngInput"  value="">
      </div>

      <!-- Observações -->
      <div class="form-group">
        <label class="form-label"><i class="fas fa-note-sticky"></i>Observações</label>
        <textarea name="notes" class="form-input form-textarea" rows="3"
                  placeholder="Notas adicionais..."><?= htmlspecialchars($_POST['notes'] ?? '') ?></textarea>
      </div>

      <!-- Timestamp automático -->
      <div class="ts-display">
        <i class="fas fa-clock"></i>
        Data/hora registrada automaticamente: <strong id="tsNow"></strong>
      </div>

      <div class="collect-nav" style="margin-top:24px">
        <a href="/agent_challenges.php" class="btn btn-outline"><i class="fas fa-arrow-left"></i>Voltar</a>
        <button type="submit" class="btn btn-success">
          <i class="fas fa-check"></i> Salvar pesquisa
        </button>
      </div>
    </form>
  </div>
</div>

<script>
// Timestamp ao vivo
(function tick(){
  const now = new Date();
  const el  = document.getElementById('tsNow');
  if(el) el.textContent = now.toLocaleDateString('pt-BR') + ' às ' + now.toLocaleTimeString('pt-BR');
  setTimeout(tick, 1000);
})();

// Máscara de telefone
document.getElementById('phoneInput').addEventListener('input', function(){
  let v = this.value.replace(/\D/g,'');
  if(v.length<=10) v = v.replace(/(\d{2})(\d{4})(\d{0,4})/,'($1) $2-$3');
  else             v = v.replace(/(\d{2})(\d{5})(\d{0,4})/,'($1) $2-$3');
  this.value = v;
});

// GPS
function captureGPS(){
  const btn  = document.getElementById('geoBtn');
  const disp = document.getElementById('geoDisplay');
  const txt  = document.getElementById('geoText');

  if(!navigator.geolocation){ alert('Geolocalização não suportada.'); return; }

  btn.innerHTML = '<i class="fas fa-spinner fa-spin-fast"></i> Obtendo...';
  btn.disabled  = true;

  navigator.geolocation.getCurrentPosition(
    pos => {
      const lat = pos.coords.latitude.toFixed(6);
      const lng = pos.coords.longitude.toFixed(6);
      document.getElementById('latInput').value = lat;
      document.getElementById('lngInput').value = lng;
      txt.innerHTML = `<i class="fas fa-check-circle" style="color:var(--green)"></i>&nbsp;Lat: <strong>${lat}</strong> &nbsp;Lng: <strong>${lng}</strong>`;
      btn.innerHTML = '<i class="fas fa-check"></i> GPS capturado';
      btn.className = btn.className.replace('btn-geo','btn-success');
    },
    err => {
      alert('Erro ao obter localização: ' + err.message);
      btn.innerHTML = '<i class="fas fa-crosshairs"></i> Tentar novamente';
      btn.disabled  = false;
    },
    { enableHighAccuracy:true, timeout:12000 }
  );
}
</script>
<?php include 'includes/footer.php'; ?>
