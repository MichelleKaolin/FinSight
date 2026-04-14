<?php
// edit_research.php – Editar Pesquisa
session_start();
require_once 'includes/auth_check.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
requireLogin();

$pageTitle = 'Editar Pesquisa';
$u  = currentUser();
$db = getDB();
$id = (int)($_GET['id'] ?? 0);
if(!$id){ header('Location: /history.php'); exit; }

$stmt = $db->prepare('SELECT * FROM responses WHERE id=?');
$stmt->execute([$id]); $response = $stmt->fetch();
if(!$response){ header('Location: /history.php'); exit; }

// Agente só edita o próprio
if($u['role']!=='admin' && $response['agent_id']!=$u['id']){
    header('Location: /history.php'); exit;
}

$chStmt = $db->prepare('SELECT challenge FROM response_challenges WHERE response_id=?');
$chStmt->execute([$id]);
$curChals = $chStmt->fetchAll(PDO::FETCH_COLUMN);

$error = $success = '';

if($_SERVER['REQUEST_METHOD']==='POST'){
    $name  = sanitize($_POST['name']  ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $pref  = sanitize($_POST['preference'] ?? '');
    $lat   = $_POST['latitude']  !== '' ? (float)$_POST['latitude']  : null;
    $lng   = $_POST['longitude'] !== '' ? (float)$_POST['longitude'] : null;
    $notes = sanitize($_POST['notes'] ?? '');
    $rawC  = $_POST['challenges'] ?? [];

    $okP = ['credit','investment','savings','loan','card'];
    $okC = ['debt','lack_control','low_income','no_credit','fraud_risk','illiteracy'];
    $chals = array_values(array_filter($rawC, fn($c)=>in_array($c,$okC)));

    if(!$name || !in_array($pref,$okP) || empty($chals))
        $error = 'Preencha todos os campos obrigatórios.';
    elseif(count($chals)>3)
        $error = 'Selecione no máximo 3 desafios.';
    else {
        $risk = calculateRisk($chals);
        $db->beginTransaction();
        try {
            $db->prepare('UPDATE responses SET interviewee_name=?,interviewee_phone=?,financial_preference=?,risk_level=?,latitude=?,longitude=?,notes=?,updated_at=CURRENT_TIMESTAMP WHERE id=?')
               ->execute([$name,$phone,$pref,$risk,$lat,$lng,$notes,$id]);
            $db->prepare('DELETE FROM response_challenges WHERE response_id=?')->execute([$id]);
            $ins=$db->prepare('INSERT INTO response_challenges(response_id,challenge)VALUES(?,?)');
            foreach($chals as $c) $ins->execute([$id,$c]);
            $db->commit();
            $success = 'Pesquisa atualizada com sucesso!';
            // Reload
            $stmt->execute([$id]); $response=$stmt->fetch();
            $chStmt->execute([$id]); $curChals=$chStmt->fetchAll(PDO::FETCH_COLUMN);
        } catch(Exception $e){
            $db->rollBack(); $error='Erro ao salvar. Tente novamente.';
        }
    }
}

$allPrefs = [
    ['key'=>'credit',     'label'=>'Crédito',     'ico'=>'fas fa-credit-card'],
    ['key'=>'investment', 'label'=>'Investimento', 'ico'=>'fas fa-chart-line'],
    ['key'=>'savings',    'label'=>'Poupança',     'ico'=>'fas fa-piggy-bank'],
    ['key'=>'loan',       'label'=>'Empréstimo',   'ico'=>'fas fa-hand-holding-dollar'],
    ['key'=>'card',       'label'=>'Cartão',       'ico'=>'fas fa-wallet'],
];
$allChals = [
    ['key'=>'debt',         'label'=>'Dívidas'],
    ['key'=>'lack_control', 'label'=>'Falta de controle financeiro'],
    ['key'=>'low_income',   'label'=>'Renda baixa'],
    ['key'=>'no_credit',    'label'=>'Sem acesso a crédito'],
    ['key'=>'fraud_risk',   'label'=>'Risco de fraude'],
    ['key'=>'illiteracy',   'label'=>'Analfabetismo financeiro'],
];

include 'includes/header.php';
?>

<div class="page-hdr">
  <div>
    <h1 class="page-title"><i class="fas fa-edit"></i>Editar Pesquisa <span style="color:var(--text-sub);font-size:1.1rem">#<?=$id?></span></h1>
    <p class="page-sub">Atualize os dados coletados</p>
  </div>
  <div class="page-acts">
    <a href="/history.php" class="btn btn-outline"><i class="fas fa-arrow-left"></i>Voltar</a>
  </div>
</div>

<?php if($error):   ?><div class="alert alert-error"><i class="fas fa-exclamation-circle"></i><?=$error?></div><?php endif; ?>
<?php if($success): ?><div class="alert alert-success auto-dismiss"><i class="fas fa-check-circle"></i><?=$success?></div><?php endif; ?>

<div class="edit-wrap">
  <form method="POST" action="/edit_research.php?id=<?=$id?>">

    <!-- Dados do entrevistado -->
    <div class="edit-section">
      <h3 class="edit-section-ttl"><i class="fas fa-user"></i>Dados do Entrevistado</h3>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Nome completo <span class="req">*</span></label>
          <input type="text" name="name" class="form-input" required
                 value="<?=htmlspecialchars($response['interviewee_name'])?>">
        </div>
        <div class="form-group">
          <label class="form-label">Telefone</label>
          <input type="tel" id="phoneEdit" name="phone" class="form-input"
                 value="<?=htmlspecialchars($response['interviewee_phone']??'')?>">
        </div>
      </div>
    </div>

    <!-- Preferência -->
    <div class="edit-section">
      <h3 class="edit-section-ttl"><i class="fas fa-star"></i>Preferência Financeira</h3>
      <div class="pref-inline-grid">
        <?php foreach($allPrefs as $p): ?>
        <label class="pref-inline-lbl">
          <input type="radio" name="preference" value="<?=$p['key']?>"
                 <?=$response['financial_preference']===$p['key']?'checked':''?> required>
          <div class="pref-inline-btn"><i class="<?=$p['ico']?>"></i><?=$p['label']?></div>
        </label>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Desafios -->
    <div class="edit-section">
      <h3 class="edit-section-ttl">
        <i class="fas fa-triangle-exclamation"></i>Desafios Financeiros
        <span id="editCnt" class="count-disp" style="margin-left:8px">(<?=count($curChals)?>/3)</span>
      </h3>
      <div class="chal-inline-grid">
        <?php foreach($allChals as $ch): ?>
        <label class="chal-inline-lbl">
          <input type="checkbox" name="challenges[]" value="<?=$ch['key']?>"
                 <?=in_array($ch['key'],$curChals)?'checked':''?>
                 onchange="updEditCnt()">
          <div class="chal-inline-btn"><i class="fas fa-check" style="font-size:.7rem;opacity:.5"></i><?=$ch['label']?></div>
        </label>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Localização -->
    <div class="edit-section">
      <h3 class="edit-section-ttl"><i class="fas fa-map-marker-alt"></i>Localização GPS</h3>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Latitude</label>
          <input type="number" name="latitude" step="0.000001" class="form-input"
                 placeholder="Ex: -23.550520"
                 value="<?=$response['latitude']??''?>" id="latEdit">
        </div>
        <div class="form-group">
          <label class="form-label">Longitude</label>
          <input type="number" name="longitude" step="0.000001" class="form-input"
                 placeholder="Ex: -46.633308"
                 value="<?=$response['longitude']??''?>" id="lngEdit">
        </div>
      </div>
      <button type="button" onclick="updateGPS()" class="btn btn-geo btn-sm">
        <i class="fas fa-crosshairs"></i> Atualizar via GPS
      </button>
      <?php if($response['latitude']&&$response['longitude']): ?>
      <a href="https://maps.google.com/?q=<?=$response['latitude']?>,<?=$response['longitude']?>"
         target="_blank" class="btn btn-sm btn-outline" style="margin-left:8px">
        <i class="fas fa-map"></i>Ver no mapa
      </a>
      <?php endif; ?>
    </div>

    <!-- Observações -->
    <div class="edit-section">
      <h3 class="edit-section-ttl"><i class="fas fa-note-sticky"></i>Observações</h3>
      <textarea name="notes" class="form-input form-textarea" rows="3"
                placeholder="Anotações adicionais..."><?=htmlspecialchars($response['notes']??'')?></textarea>
    </div>

    <!-- Timestamps -->
    <div class="edit-meta">
      <span><i class="fas fa-calendar-plus"></i>Criado: <?=formatDate($response['created_at'])?></span>
      <span><i class="fas fa-calendar-check"></i>Atualizado: <?=formatDate($response['updated_at'])?></span>
      <?php $riskInfo=getRiskLabel($response['risk_level']); ?>
      <span>Risco atual: <span class="risk-badge <?=$riskInfo['class']?>"><?=$riskInfo['icon']?> <?=$riskInfo['label']?></span></span>
    </div>

    <div class="form-actions">
      <a href="/history.php" class="btn btn-outline">Cancelar</a>
      <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i>Salvar alterações</button>
    </div>
  </form>
</div>

<script>
// Máscara telefone
document.getElementById('phoneEdit').addEventListener('input',function(){
  let v=this.value.replace(/\D/g,'');
  if(v.length<=10) v=v.replace(/(\d{2})(\d{4})(\d{0,4})/,'($1) $2-$3');
  else v=v.replace(/(\d{2})(\d{5})(\d{0,4})/,'($1) $2-$3');
  this.value=v;
});

// Contador desafios
function updEditCnt(){
  const cbs=[...document.querySelectorAll('input[name="challenges[]"]')];
  const cnt=cbs.filter(c=>c.checked).length;
  document.getElementById('editCnt').textContent=`(${cnt}/3)`;
  document.getElementById('editCnt').className='count-disp '+(cnt>=3?'count-max':cnt>0?'count-ok':'');
  cbs.forEach(c=>{ if(!c.checked) c.disabled=cnt>=3; });
  document.querySelectorAll('.chal-inline-lbl').forEach(l=>{
    const i=l.querySelector('input');
    l.querySelector('.chal-inline-btn').style.opacity=i.disabled?'.4':'';
  });
}

// GPS
function updateGPS(){
  if(!navigator.geolocation){alert('Não suportado.');return;}
  navigator.geolocation.getCurrentPosition(
    p=>{
      document.getElementById('latEdit').value=p.coords.latitude.toFixed(6);
      document.getElementById('lngEdit').value=p.coords.longitude.toFixed(6);
      alert('Localização atualizada com sucesso!');
    },
    e=>alert('Erro: '+e.message)
  );
}
</script>
<?php include 'includes/footer.php'; ?>
