// ============================================================
//  Coop TV — Admin JS  v1.2
// ============================================================

// Aplicar tema salvo assim que o JS carrega (reforço após CSS)
(function(){
  var t = localStorage.getItem('cooptv_theme') || 'dark';
  applyTheme(t, false);
})();

function applyTheme(theme, animate) {
  var html      = document.documentElement;
  var iconDark  = document.getElementById('iconDark');
  var iconLight = document.getElementById('iconLight');
  var favicon   = document.getElementById('favicon');
  var brandLogo = document.getElementById('brandLogo');

  if (!animate) {
    html.style.transition = 'none';
    setTimeout(function(){ html.style.transition = ''; }, 50);
  }

  html.setAttribute('data-theme', theme);
  localStorage.setItem('cooptv_theme', theme);

  // Ícone toggle
  if (iconDark)  iconDark.style.display  = theme === 'dark'  ? '' : 'none';
  if (iconLight) iconLight.style.display = theme === 'light' ? '' : 'none';

  // Troca src da logo (método confiável — sem CSS display toggle)
  if (brandLogo && typeof LOGOS !== 'undefined') {
    brandLogo.src = LOGOS.brand[theme];
  }

  // Favicon dinâmico
  if (favicon && typeof LOGOS !== 'undefined') {
    favicon.href = LOGOS.icon[theme] + '?v=' + Date.now();
  }
}

function toggleTheme() {
  var current = document.documentElement.getAttribute('data-theme') || 'dark';
  applyTheme(current === 'dark' ? 'light' : 'dark', true);
}

// Toast
function showToast(msg, duration) {
  duration = duration || 3000;
  var t = document.getElementById('toast');
  if (!t) return;
  t.textContent = msg;
  t.classList.remove('hidden');
  clearTimeout(t._timer);
  t._timer = setTimeout(function(){ t.classList.add('hidden'); }, duration);
}

// Auto-dismiss alerts
document.querySelectorAll('.alert').forEach(function(el){
  setTimeout(function(){
    el.style.transition = 'opacity .5s';
    el.style.opacity = '0';
    setTimeout(function(){ el.remove(); }, 500);
  }, 4000);
});
