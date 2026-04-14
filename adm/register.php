// register.php – Tela de Cadastro
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';

if (isset($_SESSION['user_id'])) { header('Location: /history.php'); exit; }

$error = $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';
    $conf  = $_POST['confirm']  ?? '';

    $colors = ['#E53935','#FB8C00','#FDD835','#43A047','#1565C0'];
    $color  = $colors[array_rand($colors)];

    if (!$name || !$email || !$pass)        $error = 'Preencha todos os campos.';
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $error = 'E-mail inválido.';
    elseif (strlen($pass) < 6)              $error = 'Senha mínima de 6 caracteres.';
    elseif ($pass !== $conf)                $error = 'As senhas não coincidem.';
    else {
        $db = getDB();
        if ($db->prepare('SELECT id FROM users WHERE email=?')->execute([$email]) &&
            $db->prepare('SELECT id FROM users WHERE email=? LIMIT 1')->execute([$email]) &&
            $db->query("SELECT id FROM users WHERE email='".addslashes($email)."' LIMIT 1")->fetchColumn()) {
            $error = 'E-mail já cadastrado.';
        } else {
            $db->prepare('INSERT INTO users (name,email,password,role,avatar_color) VALUES (?,?,?,?,?)')
               ->execute([$name, $email, password_hash($pass, PASSWORD_DEFAULT), 'agent', $color]);
            $success = 'Conta criada! Agora faça login.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>FinSight – Cadastro</title>
<link rel="stylesheet" href="/assets/css/style.css">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="auth-body">

<button onclick="toggleTheme()" class="auth-theme-btn" id="themeToggle">
  <i class="fas fa-moon" id="themeIco"></i> Tema
</button>

<div class="auth-container">
  <div class="auth-left">
    <div class="auth-brand">
      <div class="auth-logo"><span>FS</span></div>
      <h1 class="auth-brand-name">FinSight</h1>
      <p class="auth-brand-tag">Crie sua conta de agente</p>
    </div>
    <div class="register-info">
      <p>Como agente você poderá:</p>
      <ul>
        <li><i class="fas fa-check"></i> Realizar pesquisas de campo</li>
        <li><i class="fas fa-check"></i> Coletar dados financeiros com GPS</li>
        <li><i class="fas fa-check"></i> Visualizar histórico de pesquisas</li>
        <li><i class="fas fa-check"></i> Gerenciar lista de entrevistados</li>
      </ul>
    </div>
  </div>

  <div class="auth-right">
    <div class="auth-form-wrap">
      <h2 class="auth-title">Criar conta</h2>
      <p class="auth-sub">Preencha os dados abaixo</p>

      <?php if($error):   ?><div class="alert alert-error"><i class="fas fa-exclamation-circle"></i><?= $error ?></div><?php endif; ?>
      <?php if($success): ?>
      <div class="alert alert-success">
        <i class="fas fa-check-circle"></i><?= $success ?>
        <a href="/index.php" class="alert-link">Fazer login →</a>
      </div>
      <?php endif; ?>

      <form method="POST" action="/register.php" id="regForm">
        <div class="form-group">
          <label class="form-label"><i class="fas fa-user"></i>Nome completo</label>
          <input type="text" name="name" class="form-input" placeholder="Seu nome completo"
                 value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label"><i class="fas fa-envelope"></i>E-mail</label>
          <input type="email" name="email" class="form-input" placeholder="seu@email.com"
                 value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label"><i class="fas fa-lock"></i>Senha</label>
            <div class="pw-wrap">
              <input type="password" id="pw1" name="password" class="form-input" placeholder="Mín. 6 caracteres" required>
              <button type="button" class="pw-toggle" onclick="togglePassword('pw1','ico1')" tabindex="-1"><i class="fas fa-eye" id="ico1"></i></button>
            </div>
            <div class="pw-strength"><div class="pw-fill" id="pwFill"></div></div>
            <p class="pw-lbl" id="pwLbl"></p>
          </div>
          <div class="form-group">
            <label class="form-label"><i class="fas fa-lock"></i>Confirmar senha</label>
            <div class="pw-wrap">
              <input type="password" id="pw2" name="confirm" class="form-input" placeholder="Repita a senha" required>
              <button type="button" class="pw-toggle" onclick="togglePassword('pw2','ico2')" tabindex="-1"><i class="fas fa-eye" id="ico2"></i></button>
            </div>
            <p class="pw-lbl" id="matchLbl"></p>
          </div>
        </div>
        <button type="submit" class="btn btn-primary btn-full" style="margin-top:8px">
          <i class="fas fa-user-plus"></i> Criar conta
        </button>
      </form>
      <p class="auth-switch">Já tem conta? <a href="/index.php">Fazer login</a></p>
    </div>
  </div>
</div>

<script src="/assets/js/main.js"></script>
<script src="/assets/js/accessibility.js"></script>
<script>
const pw1  = document.getElementById('pw1');
const pw2  = document.getElementById('pw2');
const fill = document.getElementById('pwFill');
const lbl  = document.getElementById('pwLbl');
const mlbl = document.getElementById('matchLbl');

pw1.addEventListener('input', () => {
  const v = pw1.value;
  let s = 0;
  if(v.length>=6) s++;
  if(v.length>=10) s++;
  if(/[A-Z]/.test(v)) s++;
  if(/[0-9]/.test(v)) s++;
  if(/[^A-Za-z0-9]/.test(v)) s++;
  const lvls = ['','Muito fraca','Fraca','Média','Forte','Muito forte'];
  const cols = ['','#E53935','#FB8C00','#FDD835','#43A047','#1B5E20'];
  fill.style.width = (s*20)+'%';
  fill.style.background = cols[s]||'#ccc';
  lbl.textContent = lvls[s]||'';
  lbl.style.color = cols[s]||'#999';
});

pw2.addEventListener('input', () => {
  if(!pw2.value) { mlbl.textContent=''; return; }
  const ok = pw1.value === pw2.value;
  mlbl.textContent = ok ? '✓ Senhas coincidem' : '✗ Senhas diferentes';
  mlbl.style.color  = ok ? '#43A047' : '#E53935';
});
</script>
</body>
</html>
