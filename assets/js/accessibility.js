// assets/js/accessibility.js
// ── Font Size ─────────────────────────────────────────────
const FS_KEY     = 'fs-fontsize';
const FS_DEFAULT = 16;
const FS_MIN     = 12;
const FS_MAX     = 22;
const FS_STEP    = 2;

function getFontSize() {
  return parseInt(localStorage.getItem(FS_KEY) || FS_DEFAULT, 10);
}

function changeFontSize(dir) {
  let size = getFontSize();
  if (dir === 0)       size = FS_DEFAULT;
  else if (dir === 1)  size = Math.min(size + FS_STEP, FS_MAX);
  else if (dir === -1) size = Math.max(size - FS_STEP, FS_MIN);
  localStorage.setItem(FS_KEY, size);
  document.documentElement.style.fontSize = size + 'px';
  showAccessToast('Fonte: ' + size + 'px');
}

// Apply saved font size on load
(function() {
  const size = getFontSize();
  if (size !== FS_DEFAULT) {
    document.documentElement.style.fontSize = size + 'px';
  }
})();

// ── High Contrast ─────────────────────────────────────────
function toggleContrast() {
  const active = document.documentElement.classList.toggle('high-contrast');
  localStorage.setItem('fs-contrast', active ? '1' : '0');
  const btn = document.getElementById('contrastBtn');
  if (btn) btn.classList.toggle('active', active);
  showAccessToast(active ? 'Alto contraste: ativado' : 'Alto contraste: desativado');
}

// High contrast styles injected dynamically
(function() {
  if (localStorage.getItem('fs-contrast') === '1') {
    document.documentElement.classList.add('high-contrast');
  }
  const style = document.createElement('style');
  style.textContent = `
    .high-contrast { filter: contrast(1.35) saturate(1.2); }
    .high-contrast .sb-link { outline: 1px solid rgba(255,255,255,.3); }
    .high-contrast .form-input { border-width: 2.5px; }
  `;
  document.head.appendChild(style);
})();

// ── TTS (Text-to-Speech) ──────────────────────────────────
let ttsActive  = false;
let ttsSpeech  = null;

function toggleTTS() {
  ttsActive = !ttsActive;
  const bar = document.getElementById('ttsBar');
  const btn = document.getElementById('ttsBtn');

  if (ttsActive) {
    if (!window.speechSynthesis) {
      alert('Seu navegador não suporta síntese de voz.');
      ttsActive = false;
      return;
    }
    bar.classList.add('show');
    if (btn) btn.classList.add('active');
    document.body.style.cursor = 'text';
    document.addEventListener('click', ttsClickHandler);
    showAccessToast('Leitura ativada — clique em um texto');
  } else {
    stopTTS();
  }
}

function stopTTS() {
  ttsActive = false;
  if (window.speechSynthesis) window.speechSynthesis.cancel();
  const bar = document.getElementById('ttsBar');
  const btn = document.getElementById('ttsBtn');
  if (bar) bar.classList.remove('show');
  if (btn) btn.classList.remove('active');
  document.body.style.cursor = '';
  document.removeEventListener('click', ttsClickHandler);
}

function ttsClickHandler(e) {
  if (!ttsActive) return;
  // Ignore clicks on access bar itself
  if (e.target.closest('.access-bar') || e.target.closest('.tts-bar')) return;

  const el = e.target.closest('p,h1,h2,h3,h4,h5,h6,li,td,th,label,span,a,button,div[class*="kpi-val"],div[class*="kpi-lbl"],.risk-badge,.pref-badge,.tag,.dtable td,.iv-name,.iv-phone,.sb-name');
  if (!el) return;

  const text = el.innerText?.trim();
  if (!text || text.length < 2) return;

  e.preventDefault();
  e.stopPropagation();

  window.speechSynthesis.cancel();

  const utter = new SpeechSynthesisUtterance(text);
  utter.lang = 'pt-BR';
  utter.rate = 0.95;
  utter.pitch = 1;

  // Visual highlight
  el.style.outline = '2px solid var(--red)';
  el.style.borderRadius = '4px';
  utter.onend = () => {
    el.style.outline = '';
    el.style.borderRadius = '';
    const ttsText = document.getElementById('ttsBarText');
    if (ttsText) ttsText.textContent = 'Leitura ativa — clique em qualquer texto para ouvir';
  };

  const ttsText = document.getElementById('ttsBarText');
  if (ttsText) ttsText.textContent = '🔊 ' + text.substring(0, 60) + (text.length > 60 ? '…' : '');

  window.speechSynthesis.speak(utter);
}

// ── Toast notification ────────────────────────────────────
function showAccessToast(msg) {
  let toast = document.getElementById('accessToast');
  if (!toast) {
    toast = document.createElement('div');
    toast.id = 'accessToast';
    toast.style.cssText = `
      position:fixed; bottom:70px; left:50%; transform:translateX(-50%);
      background:#0D0D0D; color:#FDD835; padding:8px 20px; border-radius:30px;
      font-size:.8rem; font-weight:600; z-index:9999;
      transition:opacity .3s; pointer-events:none;
      font-family:'DM Sans',sans-serif; white-space:nowrap;
      border:1px solid rgba(253,216,53,.3);
    `;
    document.body.appendChild(toast);
  }
  toast.textContent = msg;
  toast.style.opacity = '1';
  clearTimeout(toast._timer);
  toast._timer = setTimeout(() => { toast.style.opacity = '0'; }, 2200);
}
