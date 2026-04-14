// assets/js/main.js
// ── Theme ─────────────────────────────────────────────────
function toggleTheme() {
  const html  = document.documentElement;
  const isDark = html.getAttribute('data-theme') === 'dark';
  const next   = isDark ? 'light' : 'dark';
  html.setAttribute('data-theme', next);
  localStorage.setItem('fs-theme', next);
  updateThemeIcons(next);
  if (window.fsCharts) window.fsCharts.forEach(c => updateChartTheme(c));
}

function updateThemeIcons(theme) {
  const dark = theme === 'dark';
  ['themeIco','mobileThemeIco'].forEach(id => {
    const el = document.getElementById(id);
    if (!el) return;
    el.className = dark ? 'fas fa-sun' : 'fas fa-moon';
  });
  const lbl = document.getElementById('themeLbl');
  if (lbl) lbl.textContent = dark ? 'Modo Claro' : 'Modo Escuro';
}

// Apply saved theme on load
(function() {
  const saved = localStorage.getItem('fs-theme') || 'light';
  document.documentElement.setAttribute('data-theme', saved);
  document.addEventListener('DOMContentLoaded', () => updateThemeIcons(saved));
})();

// ── Sidebar ───────────────────────────────────────────────
function toggleSidebar() {
  const sb  = document.getElementById('sidebar');
  const ov  = document.getElementById('sbOverlay');
  const btn = document.getElementById('hamburgerBtn');
  if (!sb) return;
  const open = sb.classList.toggle('open');
  ov.classList.toggle('show', open);
  if (btn) { btn.classList.toggle('open', open); btn.setAttribute('aria-expanded', open); }
}

// Close sidebar on nav link click (mobile)
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.sb-link').forEach(link => {
    link.addEventListener('click', () => {
      if (window.innerWidth <= 900) toggleSidebar();
    });
  });
});

// ── Modals ────────────────────────────────────────────────
function openModal(id) {
  const m = document.getElementById(id);
  if (m) m.classList.add('open');
}
function closeModal(id) {
  const m = document.getElementById(id);
  if (m) m.classList.remove('open');
}
// Close on backdrop click
document.addEventListener('click', e => {
  if (e.target.classList.contains('modal-bg')) {
    e.target.classList.remove('open');
  }
});
// Close on Escape
document.addEventListener('keydown', e => {
  if (e.key === 'Escape') {
    document.querySelectorAll('.modal-bg.open').forEach(m => m.classList.remove('open'));
  }
});

// ── Charts Init (dashboard) ───────────────────────────────
window.fsCharts = [];

function getChartColors() {
  const dark = document.documentElement.getAttribute('data-theme') === 'dark';
  return {
    text:   dark ? '#EAEDF2' : '#1A1A2E',
    sub:    dark ? '#8B90A0' : '#6B7280',
    grid:   dark ? 'rgba(255,255,255,.06)' : 'rgba(0,0,0,.06)',
    bg:     dark ? '#1C1F26' : '#FFFFFF',
  };
}

function updateChartTheme(chart) {
  const c = getChartColors();
  if (chart.config.options.scales) {
    Object.values(chart.config.options.scales).forEach(scale => {
      if (scale.ticks)    scale.ticks.color = c.sub;
      if (scale.grid)     scale.grid.color  = c.grid;
    });
  }
  if (chart.config.options.plugins?.legend?.labels) {
    chart.config.options.plugins.legend.labels.color = c.text;
  }
  chart.update();
}

function buildCharts() {
  if (typeof Chart === 'undefined') return;
  if (typeof PREF_LABELS === 'undefined') return; // only on dashboard

  Chart.defaults.font.family = "'DM Sans', sans-serif";
  const c = getChartColors();

  const palette = ['#E53935','#FB8C00','#FDD835','#43A047','#1565C0','#6A1B9A'];

  const commonOpts = {
    responsive: true, maintainAspectRatio: false,
    plugins: {
      legend: { labels: { color: c.text, font: { size: 12 }, padding: 14 } },
      tooltip: {
        backgroundColor: '#0D0D0D', titleColor:'#fff', bodyColor:'rgba(255,255,255,.8)',
        borderColor:'rgba(255,255,255,.1)', borderWidth:1, padding:10, cornerRadius:10,
      }
    }
  };

  // ── Preferences Bar ──
  const prefCtx = document.getElementById('prefChart');
  if (prefCtx) {
    const ch = new Chart(prefCtx, {
      type: 'bar',
      data: {
        labels: PREF_LABELS,
        datasets: [{
          label: 'Respostas',
          data: PREF_VALUES,
          backgroundColor: palette,
          borderRadius: 8,
          borderSkipped: false,
        }]
      },
      options: { ...commonOpts,
        plugins: { ...commonOpts.plugins, legend:{display:false} },
        scales: {
          x: { ticks:{color:c.sub}, grid:{display:false} },
          y: { ticks:{color:c.sub,stepSize:1}, grid:{color:c.grid} }
        }
      }
    });
    window.fsCharts.push(ch);
  }

  // ── Risk Doughnut ──
  const riskCtx = document.getElementById('riskChart');
  if (riskCtx) {
    const ch = new Chart(riskCtx, {
      type: 'doughnut',
      data: {
        labels: ['Baixo','Médio','Alto'],
        datasets: [{ data: RISK_VALUES, backgroundColor:['#43A047','#FDD835','#E53935'], borderWidth:0, hoverOffset:8 }]
      },
      options: { ...commonOpts, cutout:'68%',
        plugins: { ...commonOpts.plugins, legend:{position:'bottom',labels:{color:c.text,font:{size:12}}} }
      }
    });
    window.fsCharts.push(ch);
  }

  // ── Monthly Trend Line ──
  const trendCtx = document.getElementById('trendChart');
  if (trendCtx) {
    const ch = new Chart(trendCtx, {
      type: 'line',
      data: {
        labels: MONTH_LABELS,
        datasets: [{
          label: 'Pesquisas', data: MONTH_VALUES,
          borderColor: '#E53935', backgroundColor: 'rgba(229,57,53,.08)',
          tension:.4, fill:true, pointBackgroundColor:'#E53935', pointRadius:4,
        }]
      },
      options: { ...commonOpts,
        scales: {
          x: { ticks:{color:c.sub}, grid:{display:false} },
          y: { ticks:{color:c.sub,stepSize:1}, grid:{color:c.grid} }
        }
      }
    });
    window.fsCharts.push(ch);
  }

  // ── Challenges Horizontal Bar ──
  const chalCtx = document.getElementById('chalChart');
  if (chalCtx) {
    const ch = new Chart(chalCtx, {
      type: 'bar',
      data: {
        labels: CHAL_LABELS,
        datasets: [{
          label: 'Ocorrências', data: CHAL_VALUES,
          backgroundColor: palette,
          borderRadius: 6,
        }]
      },
      options: { ...commonOpts,
        indexAxis: 'y',
        plugins: { ...commonOpts.plugins, legend:{display:false} },
        scales: {
          x: { ticks:{color:c.sub,stepSize:1}, grid:{color:c.grid} },
          y: { ticks:{color:c.sub}, grid:{display:false} }
        }
      }
    });
    window.fsCharts.push(ch);
  }
}

document.addEventListener('DOMContentLoaded', buildCharts);

// ── Delete helpers (history/dashboard) ───────────────────
let _deleteId = null;

function confirmDelete(id) {
  _deleteId = id;
  openModal('deleteModal');
}

function confirmDeleteAll() {
  openModal('deleteAllModal');
}

function deleteRow() {
  if (!_deleteId) return;
  fetch('/api/responses.php', {
    method:'DELETE',
    headers:{'Content-Type':'application/json'},
    body:JSON.stringify({id:_deleteId})
  })
  .then(r => r.json())
  .then(d => { if(d.success) location.reload(); else alert('Erro ao excluir.'); })
  .catch(() => alert('Erro de conexão.'));
}

function deleteAllData() {
  fetch('/api/responses.php', {
    method:'DELETE',
    headers:{'Content-Type':'application/json'},
    body:JSON.stringify({all:true})
  })
  .then(r => r.json())
  .then(d => { if(d.success) location.reload(); else alert('Erro ao excluir.'); })
  .catch(() => alert('Erro de conexão.'));
}

// ── Alerts auto-dismiss ───────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.alert.auto-dismiss').forEach(el => {
    setTimeout(() => el.style.opacity='0', 4000);
    setTimeout(() => el.remove(), 4400);
  });
});

// ── Password toggle ───────────────────────────────────────
function togglePassword(inputId, iconId) {
  const inp = document.getElementById(inputId);
  const ico = document.getElementById(iconId);
  if (!inp) return;
  inp.type = inp.type === 'password' ? 'text' : 'password';
  if (ico) ico.className = inp.type === 'password' ? 'fas fa-eye' : 'fas fa-eye-slash';
}
