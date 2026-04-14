<?php
// includes/header.php
if (session_status() === PHP_SESSION_NONE) session_start();
$user = currentUser();
$cp   = basename($_SERVER['PHP_SELF'], '.php');
?><!DOCTYPE html>
<html lang="pt-BR" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>FinSight – <?= htmlspecialchars($pageTitle ?? 'App') ?></title>
<link rel="stylesheet" href="/assets/css/style.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:ital,wght@0,300;0,400;0,500;0,600;1,400&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body>

<!-- ░░ BARRA DE ACESSIBILIDADE ░░ -->
<div class="access-bar" id="accessBar">
  <div class="access-inner">
    <span class="access-title"><i class="fas fa-universal-access"></i> Acessibilidade</span>
    <div class="access-btns">
      <button onclick="changeFontSize(-1)" class="acc-btn" title="Diminuir fonte" aria-label="Diminuir fonte">A<sup>-</sup></button>
      <button onclick="changeFontSize(0)"  class="acc-btn" title="Fonte padrão"   aria-label="Fonte padrão">A</button>
      <button onclick="changeFontSize(1)"  class="acc-btn" title="Aumentar fonte" aria-label="Aumentar fonte">A<sup>+</sup></button>
      <button onclick="toggleTTS()"  class="acc-btn" id="ttsBtn"   title="Leitura de texto" aria-label="Ativar leitura"><i class="fas fa-microphone"></i></button>
      <button onclick="toggleContrast()" class="acc-btn" id="contrastBtn" title="Alto contraste" aria-label="Alto contraste"><i class="fas fa-circle-half-stroke"></i></button>
    </div>
  </div>
</div>

<!-- ░░ SIDEBAR ░░ -->
<nav class="sidebar" id="sidebar" role="navigation" aria-label="Menu principal">
  <div class="sb-logo">
    <div class="sb-logo-mark"><span>F</span></div>
    <div class="sb-logo-text"><strong>FinSight</strong><small>Insights</small></div>
  </div>

  <div class="sb-user">
    <div class="sb-avatar" style="background:<?= htmlspecialchars($user['avatar_color']) ?>">
      <?= getInitials($user['name']) ?>
    </div>
    <div class="sb-user-info">
      <span class="sb-name"><?= htmlspecialchars($user['name']) ?></span>
      <span class="sb-role role-<?= $user['role'] ?>"><?= $user['role']==='admin'?'Administrador':'Agente' ?></span>
    </div>
  </div>

  <ul class="sb-menu">
    <?php if($user['role']==='admin'): ?>
    <li><a href="/dashboard.php"     class="sb-link <?= $cp==='dashboard'?'active':'' ?>"><i class="fas fa-chart-pie"></i><span>Dashboard</span></a></li>
    <?php endif; ?>
    <li><a href="/history.php"       class="sb-link <?= $cp==='history'?'active':'' ?>"><i class="fas fa-history"></i><span>Histórico</span></a></li>
    <li><a href="/interviewees.php"  class="sb-link <?= $cp==='interviewees'?'active':'' ?>"><i class="fas fa-users"></i><span>Entrevistados</span></a></li>
    <li><a href="/agent_collect.php" class="sb-link <?= $cp==='agent_collect'?'active':'' ?>"><i class="fas fa-plus-circle"></i><span>Nova Pesquisa</span></a></li>
    <?php if($user['role']==='admin'): ?>
    <li class="sb-divider"></li>
    <li><a href="/manage_users.php"  class="sb-link <?= $cp==='manage_users'?'active':'' ?>"><i class="fas fa-user-cog"></i><span>Usuários</span></a></li>
    <?php endif; ?>
  </ul>

  <div class="sb-foot">
    <button onclick="toggleTheme()" class="sb-theme-btn" id="themeBtn" aria-label="Alternar tema">
      <i class="fas fa-moon" id="themeIco"></i>
      <span id="themeLbl">Modo Escuro</span>
    </button>
    <a href="/logout.php" class="sb-logout"><i class="fas fa-sign-out-alt"></i><span>Sair</span></a>
  </div>
</nav>

<!-- ░░ MOBILE TOPBAR ░░ -->
<header class="topbar" role="banner">
  <button onclick="toggleSidebar()" class="hamburger" aria-label="Abrir menu" aria-expanded="false" id="hamburgerBtn">
    <span></span><span></span><span></span>
  </button>
  <span class="topbar-title"><?= htmlspecialchars($pageTitle ?? 'FinSight') ?></span>
  <button onclick="toggleTheme()" class="topbar-theme" aria-label="Alternar tema">
    <i class="fas fa-moon" id="mobileThemeIco"></i>
  </button>
</header>
<div class="sb-overlay" id="sbOverlay" onclick="toggleSidebar()"></div>

<!-- ░░ TTS BAR ░░ -->
<div class="tts-bar" id="ttsBar" role="status" aria-live="polite">
  <span class="tts-pulse"><i class="fas fa-waveform-lines"></i></span>
  <span>Leitura ativa — clique em qualquer texto para ouvir</span>
  <button onclick="stopTTS()" class="tts-stop-btn"><i class="fas fa-stop"></i> Parar</button>
</div>

<main class="main" id="mainContent" role="main">
