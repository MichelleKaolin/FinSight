<?php
// index.php – Tela de Login
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';

if (isset($_SESSION['user_id'])) {
    header('Location: ' . ($_SESSION['user_role']==='admin' ? '/dashboard.php' : '/history.php'));
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';

    if (!$email || !$pass) {
        $error = 'Preencha todos os campos.';
    } else {
        $db   = getDB();
        $stmt = $db->prepare('SELECT * FROM users WHERE email=? AND is_active=1 LIMIT 1');
        $stmt->execute([$email]);
        $u = $stmt->fetch();
        if ($u && password_verify($pass, $u['password'])) {
            $_SESSION['user_id']           = $u['id'];
            $_SESSION['user_name']         = $u['name'];
            $_SESSION['user_role']         = $u['role'];
            $_SESSION['user_email']        = $u['email'];
            $_SESSION['user_avatar_color'] = $u['avatar_color'];
            header('Location: ' . ($u['role']==='admin' ? '/dashboard.php' : '/history.php'));
            exit;
        }
        $error = 'E-mail ou senha incorretos.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>FinSight – Login</title>
<link rel="stylesheet" href="/assets/css/style.css">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="auth-body">

<button onclick="toggleTheme()" class="auth-theme-btn" id="themeToggle">
  <i class="fas fa-moon" id="themeIco"></i> Tema
</button>

<div class="auth-container">
  <!-- ░ LEFT PANEL ░ -->
  <div class="auth-left">
    <div class="auth-brand">
      <div class="auth-logo"><span>FS</span></div>
      <h1 class="auth-brand-name">FinSight</h1>
      <p class="auth-brand-tag">Financial Behavior Insights</p>
    </div>
    <div class="auth-features">
      <div class="auth-feat">
        <div class="auth-feat-ico" style="background:rgba(229,57,53,.2)"><i class="fas fa-chart-pie" style="color:#E53935"></i></div>
        <div><strong>Dashboard analítico</strong><p>Visualize dados em tempo real com gráficos interativos</p></div>
      </div>
      <div class="auth-feat">
        <div class="auth-feat-ico" style="background:rgba(251,140,0,.2)"><i class="fas fa-map-marker-alt" style="color:#FB8C00"></i></div>
        <div><strong>Geolocalização</strong><p>Mapeie pesquisas de campo com coordenadas GPS</p></div>
      </div>
      <div class="auth-feat">
        <div class="auth-feat-ico" style="background:rgba(67,160,71,.2)"><i class="fas fa-shield-alt" style="color:#43A047"></i></div>
        <div><strong>Classificação de risco</strong><p>Identifique perfis de risco financeiro automaticamente</p></div>
      </div>
    </div>
  </div>

  <!-- ░ RIGHT PANEL ░ -->
  <div class="auth-right">
    <div class="auth-form-wrap">
      <h2 class="auth-title">Bem-vindo de volta</h2>
      <p class="auth-sub">Entre com suas credenciais para continuar</p>

      <?php if($error): ?>
      <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i><?= $error ?></div>
      <?php endif; ?>

      <form method="POST" action="/index.php">
        <div class="form-group">
          <label class="form-label" for="email"><i class="fas fa-envelope"></i>E-mail</label>
          <input type="email" id="email" name="email" class="form-input"
                 placeholder="seu@email.com" autocomplete="email"
                 value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label" for="password"><i class="fas fa-lock"></i>Senha</label>
          <div class="pw-wrap">
            <input type="password" id="password" name="password" class="form-input"
                   placeholder="••••••••" autocomplete="current-password" required>
            <button type="button" class="pw-toggle" onclick="togglePassword('password','pwIco')" tabindex="-1">
              <i class="fas fa-eye" id="pwIco"></i>
            </button>
          </div>
        </div>
        <button type="submit" class="btn btn-primary btn-full" style="margin-top:8px">
          <i class="fas fa-sign-in-alt"></i> Entrar
        </button>
      </form>

      <div class="auth-divider">ou</div>

      <a href="/register.php" class="btn btn-outline btn-full">
        <i class="fas fa-user-plus"></i> Criar conta
      </a>

      <div class="auth-demo-hint">
        <p><strong>Contas de demonstração (senha: <code>password</code>)</strong></p>
        <p>Admin: <code>admin@finsight.com</code></p>
        <p>Agente: <code>agent@finsight.com</code></p>
      </div>
    </div>
  </div>
</div>

<script src="/assets/js/main.js"></script>
<script src="/assets/js/accessibility.js"></script>
</body>
</html>
