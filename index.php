<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

$slug  = trim($_GET['store'] ?? '');
$store = $slug ? dbFetch("SELECT * FROM stores WHERE slug=?", [$slug]) : null;

if (!$store) {
    http_response_code(404);
    die('<!DOCTYPE html><html><body style="background:#000;color:#fff;display:flex;align-items:center;justify-content:center;height:100vh;font-family:sans-serif;text-align:center"><div><div style="font-size:4rem">📺</div><h2>Ponto não encontrado</h2><p>Verifique a URL</p></div></body></html>');
}

$items = dbFetchAll("
    SELECT t.id, t.media_id, t.position,
           COALESCE(t.duration, m.duration) as duration,
           m.original_name, m.type, m.filename, m.thumb
    FROM timeline_items t
    JOIN media m ON t.media_id = m.id
    WHERE t.store_id = ?
    ORDER BY t.position
", [$store['id']]);

$singleVideo = null;
if ($store['single_video_mode'] && $store['single_video_id']) {
    $singleVideo = dbFetch("SELECT * FROM media WHERE id=?", [$store['single_video_id']]);
}

$baseUrl  = BASE_URL . '/uploads/';
$playlist = [];

if ($singleVideo) {
    $playlist[] = [
        'media_id' => $singleVideo['id'],
        'type'     => $singleVideo['type'],
        'url'      => $baseUrl . 'videos/' . $singleVideo['filename'],
        'duration' => (float)$singleVideo['duration'],
        'name'     => $singleVideo['original_name'],
    ];
} else {
    foreach ($items as $item) {
        $subdir = $item['type'] === 'video' ? 'videos' : 'images';
        $playlist[] = [
            'media_id' => (int)$item['media_id'],
            'type'     => $item['type'],
            'url'      => $baseUrl . $subdir . '/' . $item['filename'],
            'duration' => (float)$item['duration'],
            'name'     => $item['original_name'],
        ];
    }
}
if ($store['playback_order'] === 'random') shuffle($playlist);

$isPortrait = $store['orientation'] === 'portrait';
$brandIcon  = BASE_URL . '/assets/' . BRAND_ICON_DARK;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<title><?= sanitize($store['name']) ?></title>
<link rel="icon" type="image/png" href="<?= $brandIcon ?>">
<link rel="manifest" href="<?= BASE_URL ?>/pwa/manifest.json">

<style>
/* ============================================================
   Player — Universal TV (LG, Samsung, Android TV, Fire Stick)
   ============================================================ */
html, body {
    margin:0; padding:0;
    width:100%; height:100%;
    overflow:hidden;
    background:#000;
    font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;
    cursor:none;
    /* Previne scroll em TVs com webOS/Tizen */
    -webkit-overflow-scrolling: touch;
    touch-action: none;
}

/* Container principal sempre preenche tudo */
#player {
    position:fixed;
    top:0; left:0; right:0; bottom:0;
    background:#000;
    display:flex;
    align-items:center;
    justify-content:center;
    overflow:hidden;
}

/* Área de mídia — ocupa 100% da janela */
#mediaWrap {
    position:absolute;
    top:0; left:0;
    width:100%; height:100%;
    background:#000;
    overflow:hidden;
}

/* ---- VÍDEO: preenche tela sem barras ---- */
#videoEl {
    position:absolute;
    /* Centralizado */
    top:50%; left:50%;
    transform:translate(-50%,-50%);
    /* Tamanho mínimo para cobrir a tela inteira */
    min-width:100%;
    min-height:100%;
    /* Mantém proporção mas GARANTE cobertura total */
    width:auto;
    height:auto;
    /* Fallback para TVs que não suportam object-fit */
    display:none;
    background:#000;
}
/* Browsers modernos: object-fit garante cobertura */
@supports (object-fit: cover) {
    #videoEl {
        width:100%;
        height:100%;
        top:0; left:0;
        transform:none;
        object-fit:cover;
        object-position:center center;
    }
}

/* ---- IMAGEM: preenche tela ---- */
#imageEl {
    position:absolute;
    top:0; left:0;
    width:100%; height:100%;
    object-fit:cover;
    object-position:center center;
    display:none;
    background:#000;
}

/* Ativo */
#videoEl.active, #imageEl.active { display:block; }

/* ---- Modo retrato (vertical) ---- */
<?php if ($isPortrait): ?>
#mediaWrap {
    width:100vh;
    height:100vw;
    top:50%; left:50%;
    transform:translate(-50%,-50%) rotate(90deg);
    transform-origin:center center;
}
<?php endif; ?>

/* ---- Loading ---- */
#loadScreen {
    position:fixed; inset:0;
    background:#000;
    display:flex; flex-direction:column;
    align-items:center; justify-content:center;
    z-index:200; transition:opacity .5s;
}
#loadScreen.hidden { opacity:0; pointer-events:none; }
.spinner {
    width:50px; height:50px;
    border:3px solid rgba(255,255,255,.12);
    border-top-color:rgba(255,255,255,.7);
    border-radius:50%;
    animation:spin .8s linear infinite;
    margin-bottom:1.2rem;
}
@keyframes spin { to { transform:rotate(360deg); } }
.load-txt { color:rgba(255,255,255,.4); font-size:.9rem; }

/* ---- Empty ---- */
#emptyState {
    position:fixed; inset:0;
    display:none; flex-direction:column;
    align-items:center; justify-content:center;
    color:rgba(255,255,255,.3); gap:1rem;
}
#emptyState .ico { font-size:4rem; }

/* ---- Controls overlay ---- */
#controls {
    position:fixed; bottom:0; left:0; right:0;
    background:linear-gradient(transparent, rgba(0,0,0,.88));
    padding:3rem 2rem 1.8rem;
    display:flex; align-items:center; justify-content:space-between;
    transform:translateY(100%);
    transition:transform .3s ease;
    z-index:50;
}
#controls.visible { transform:translateY(0); }

.ctrl-btns { display:flex; gap:.85rem; flex-wrap:wrap; }
.ctrl-btn {
    background:rgba(255,255,255,.14);
    border:2px solid rgba(255,255,255,.28);
    color:#fff; padding:.75rem 1.4rem;
    border-radius:12px; font-size:.95rem;
    cursor:pointer;
    display:flex; align-items:center; gap:.5rem;
    backdrop-filter:blur(8px);
    transition:background .2s, border-color .2s, transform .1s;
    -webkit-tap-highlight-color:transparent;
}
.ctrl-btn:hover,.ctrl-btn.focused {
    background:rgba(255,255,255,.28);
    border-color:#fff;
    transform:scale(1.04);
}
.ctrl-btn.focused { outline:3px solid #6366f1; }
.ctrl-info { color:rgba(255,255,255,.65); font-size:.85rem; text-align:right; line-height:1.7; }
.ctrl-store { font-size:1rem; color:#fff; font-weight:600; }

/* ---- Barra de progresso ---- */
#progressBar {
    position:fixed; bottom:0; left:0;
    height:3px;
    background:linear-gradient(90deg,#6366f1,#a855f7);
    width:0%; z-index:60;
    transition:width .1s linear;
}

/* ---- Now playing ---- */
#nowPlaying {
    position:fixed; top:1.2rem; right:1.2rem;
    background:rgba(0,0,0,.55);
    color:rgba(255,255,255,.55);
    padding:.35rem .85rem; border-radius:999px;
    font-size:.78rem; backdrop-filter:blur(6px);
    opacity:0; transition:opacity .3s; z-index:50;
    max-width:38%; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;
}
#nowPlaying.visible { opacity:1; }

/* ---- Offline banner ---- */
#offlineBanner {
    position:fixed; top:1rem; left:50%; transform:translateX(-50%);
    background:#ef4444cc; color:#fff; padding:.45rem 1.3rem;
    border-radius:999px; font-size:.88rem; display:none; z-index:300;
}

/* ---- PWA Add to Home (instrução) ---- */
#pwaHint {
    position:fixed; top:50%; left:50%; transform:translate(-50%,-50%);
    background:rgba(0,0,0,.9); border:1px solid rgba(255,255,255,.15);
    color:#fff; padding:2rem 2.5rem; border-radius:16px;
    text-align:center; max-width:380px; z-index:400;
    display:none;
    backdrop-filter:blur(12px);
}
#pwaHint h3 { margin-bottom:.8rem; font-size:1.1rem; }
#pwaHint p  { font-size:.85rem; color:rgba(255,255,255,.65); line-height:1.6; margin-bottom:1.2rem; }
#pwaHint button { background:#6366f1; border:none; color:#fff; padding:.65rem 1.5rem; border-radius:8px; font-size:.9rem; cursor:pointer; }
</style>
</head>
<body>

<div id="loadScreen">
    <div class="spinner"></div>
    <div class="load-txt">Carregando...</div>
</div>

<div id="emptyState">
    <div class="ico">📺</div>
    <div>Nenhuma mídia na programação</div>
    <div style="font-size:.85rem">Configure a timeline no painel de administração</div>
</div>

<div id="player">
    <div id="mediaWrap">
        <video id="videoEl" playsinline muted autoplay preload="auto"></video>
        <img   id="imageEl" alt="">
    </div>
</div>

<div id="nowPlaying"></div>
<div id="controls">
    <div class="ctrl-btns" id="ctrlBtns">
        <button class="ctrl-btn" onclick="togglePlay()"><span id="playIcon">⏸</span> <span id="playLabel">Pausar</span></button>
        <button class="ctrl-btn" onclick="nextItem()">⏭ Próximo</button>
        <button class="ctrl-btn" onclick="reloadPlayer()">🔄 Atualizar</button>
        <button class="ctrl-btn" onclick="toggleFullscreen()">⛶ Tela Cheia</button>
    </div>
    <div class="ctrl-info">
        <div class="ctrl-store"><?= sanitize($store['name']) ?></div>
        <div id="ctrlCurrent"></div>
        <div id="ctrlTotal"></div>
    </div>
</div>
<div id="progressBar"></div>
<div id="offlineBanner">⚠ Sem conexão</div>

<script>
const CONFIG = {
    storeId      : <?= (int)$store['id'] ?>,
    storeName    : <?= json_encode($store['name']) ?>,
    orientation  : <?= json_encode($store['orientation']) ?>,
    showControls : <?= $store['show_controls'] ? 'true' : 'false' ?>,
    order        : <?= json_encode($store['playback_order']) ?>,
    baseUrl      : <?= json_encode(BASE_URL) ?>,
    reloadInterval: 300,
};

let playlist   = <?= json_encode($playlist) ?>;
let idx        = 0;
let paused     = false;
let imgTimer   = null;
let ctrlTimer  = null;
let ctrlFocus  = 0;
let progRaf    = null;
let progDur    = 0;

const videoEl  = document.getElementById('videoEl');
const imageEl  = document.getElementById('imageEl');
const controls = document.getElementById('controls');
const ctrlBtns = Array.from(document.querySelectorAll('.ctrl-btn'));
const nowPlay  = document.getElementById('nowPlaying');
const progBar  = document.getElementById('progressBar');
const loadScr  = document.getElementById('loadScreen');
const emptyEl  = document.getElementById('emptyState');
const ctrlCurr = document.getElementById('ctrlCurrent');
const ctrlTot  = document.getElementById('ctrlTotal');
const playIcon = document.getElementById('playIcon');
const playLbl  = document.getElementById('playLabel');

// ---- Boot ----
window.addEventListener('load', () => {
    if (!playlist.length) {
        loadScr.classList.add('hidden');
        emptyEl.style.display = 'flex';
        return;
    }
    tryFullscreen();
    setTimeout(() => { loadScr.classList.add('hidden'); playItem(0); }, 600);
    setInterval(refreshPlaylist, CONFIG.reloadInterval * 1000);
});

// ---- Play item ----
function playItem(i) {
    idx = ((i % playlist.length) + playlist.length) % playlist.length;
    const item = playlist[idx];

    ctrlCurr.textContent = `${idx+1}/${playlist.length} — ${item.name}`;
    ctrlTot.textContent  = `Total: ${fmtDur(totalDur())}`;
    showNowPlaying(item.name);
    logPlay(item.media_id);
    clearImgTimer();
    cancelAnimationFrame(progRaf);
    progBar.style.width = '0%';
    progDur = item.duration || 10;

    if (item.type === 'video') {
        imageEl.classList.remove('active');
        imageEl.src = '';
        videoEl.classList.add('active');

        // Abordagem robusta para LG/Samsung
        videoEl.pause();
        videoEl.src = '';
        videoEl.load();

        // Pequeno delay para limpar buffer no webOS
        setTimeout(() => {
            videoEl.src = item.url;
            videoEl.load();
            const playProm = videoEl.play();
            if (playProm !== undefined) {
                playProm.catch(() => {
                    videoEl.muted = true;
                    videoEl.play().catch(() => setTimeout(nextItem, 2000));
                });
            }
        }, 50);

        animateProg(item.duration);
    } else {
        videoEl.pause(); videoEl.src = ''; videoEl.classList.remove('active');
        imageEl.src = item.url;
        imageEl.classList.add('active');
        const dur = item.duration || 10;
        animateProg(dur);
        imgTimer = setTimeout(() => { if (!paused) nextItem(); }, dur * 1000);
    }
}

videoEl.addEventListener('ended',  () => { if (!paused) nextItem(); });
videoEl.addEventListener('error',  () => { console.warn('video error, skip'); setTimeout(nextItem, 1500); });
// Em TVs com webOS, stalled pode acontecer — tentar retomar
videoEl.addEventListener('stalled', () => { setTimeout(() => { if (!videoEl.ended) videoEl.play().catch(()=>{}); }, 2000); });

function nextItem() { playItem(idx + 1); }
function prevItem() { playItem(idx - 1); }

function togglePlay() {
    paused = !paused;
    if (paused) {
        videoEl.pause(); clearImgTimer(); cancelAnimationFrame(progRaf);
        playIcon.textContent = '▶'; playLbl.textContent = 'Continuar';
    } else {
        if (playlist[idx]?.type === 'video') videoEl.play().catch(()=>{});
        else { const rem = progDur*(1-parseFloat(progBar.style.width)/100); imgTimer=setTimeout(()=>{if(!paused)nextItem();},rem*1000); }
        animateProg(progDur*(1-parseFloat(progBar.style.width)/100));
        playIcon.textContent = '⏸'; playLbl.textContent = 'Pausar';
    }
}

// ---- Progress ----
function animateProg(dur) {
    cancelAnimationFrame(progRaf);
    const start = performance.now();
    const from  = parseFloat(progBar.style.width) || 0;
    function tick(now) {
        if (paused) return;
        const pct = Math.min(from + ((now-start)/1000/dur)*(100-from), 100);
        progBar.style.width = pct + '%';
        if (pct < 100) progRaf = requestAnimationFrame(tick);
    }
    progRaf = requestAnimationFrame(tick);
}

function clearImgTimer() { if(imgTimer){ clearTimeout(imgTimer); imgTimer=null; } }

// ---- Controls ----
function showControls() {
    if (!CONFIG.showControls) return;
    controls.classList.add('visible');
    clearTimeout(ctrlTimer);
    ctrlTimer = setTimeout(hideControls, 5000);
}
function hideControls() { controls.classList.remove('visible'); ctrlFocus=0; ctrlBtns.forEach(b=>b.classList.remove('focused')); }

function showNowPlaying(name) {
    nowPlay.textContent = '▶ '+name; nowPlay.classList.add('visible');
    setTimeout(()=>nowPlay.classList.remove('visible'), 3000);
}

// ---- Fullscreen ----
function tryFullscreen() {
    const el = document.documentElement;
    const fn = el.requestFullscreen||el.webkitRequestFullscreen||el.mozRequestFullScreen||el.msRequestFullscreen;
    if (fn) fn.call(el).catch(()=>{});
}
function toggleFullscreen() {
    const isFs = document.fullscreenElement||document.webkitFullscreenElement;
    if (isFs) (document.exitFullscreen||document.webkitExitFullscreen||function(){}).call(document);
    else tryFullscreen();
}

function reloadPlayer() { location.reload(); }

// ---- Refresh playlist ----
function refreshPlaylist() {
    fetch(CONFIG.baseUrl+'/api/timeline.php?store='+CONFIG.storeId)
    .then(r=>r.json()).then(d=>{
        if (!d.items||!d.items.length) return;
        const nl = d.items.map(i=>({media_id:i.media_id,type:i.type,url:i.url,duration:i.duration,name:i.original_name}));
        if (d.store.playback_order==='random') shuffle(nl);
        playlist = nl;
    }).catch(()=>{});
}

// ---- Log ----
function logPlay(mediaId) {
    const body = JSON.stringify({store_id:CONFIG.storeId, media_id:mediaId});
    if (navigator.sendBeacon) navigator.sendBeacon(CONFIG.baseUrl+'/api/log.php', body);
    else fetch(CONFIG.baseUrl+'/api/log.php',{method:'POST',headers:{'Content-Type':'application/json'},body,keepalive:true}).catch(()=>{});
}

// ---- Keyboard / Remote (Samsung Tizen, LG webOS, Android TV, Fire TV) ----
const KEY_MAP = {
    ArrowLeft:  'prev',  ArrowRight: 'next',
    ArrowUp:    'up',    ArrowDown:  'down',
    Enter:      'ok',    ' ':        'ok',
    MediaPlayPause:'pp', MediaPlay:  'play', MediaPause:'pause',
    MediaTrackNext:'next', MediaTrackPrevious:'prev',
    Escape:'back', Backspace:'back', F5:'reload',
    // Samsung / LG numeric keycodes
    179:'pp', 176:'next', 177:'prev', 178:'stop',
    // Colored buttons
    403:'red', 404:'green', 405:'yellow', 406:'blue',
    // LG special
    461:'back',
};

document.addEventListener('keydown', e => {
    const action = KEY_MAP[e.key] || KEY_MAP[e.keyCode];
    showControls();
    document.body.style.cursor = 'default';
    if (!action) return;
    e.preventDefault();

    if (action==='left'||action==='prev') {
        controls.classList.contains('visible') ? (ctrlFocus=Math.max(0,ctrlFocus-1),updateFocus()) : prevItem();
    } else if (action==='right'||action==='next') {
        controls.classList.contains('visible') ? (ctrlFocus=Math.min(ctrlBtns.length-1,ctrlFocus+1),updateFocus()) : nextItem();
    } else if (action==='ok') {
        controls.classList.contains('visible') ? ctrlBtns[ctrlFocus]?.click() : togglePlay();
    } else if (action==='pp') { togglePlay();
    } else if (action==='play') { if(paused) togglePlay();
    } else if (action==='pause') { if(!paused) togglePlay();
    } else if (action==='reload') { reloadPlayer();
    } else if (action==='back') { hideControls();
    } else if (action==='red')    { prevItem();
    } else if (action==='green')  { togglePlay();
    } else if (action==='yellow') { nextItem();
    } else if (action==='blue')   { reloadPlayer();
    }
});

document.addEventListener('mousemove', () => {
    document.body.style.cursor = 'default';
    showControls();
    setTimeout(()=>document.body.style.cursor='none', 3000);
});

function updateFocus() { ctrlBtns.forEach((b,i)=>b.classList.toggle('focused',i===ctrlFocus)); }

// ---- Offline ----
window.addEventListener('offline', ()=>document.getElementById('offlineBanner').style.display='block');
window.addEventListener('online',  ()=>{ document.getElementById('offlineBanner').style.display='none'; refreshPlaylist(); });

// ---- Helpers ----
function totalDur() { return playlist.reduce((s,i)=>s+(i.duration||0),0); }
function fmtDur(s) {
    const h=Math.floor(s/3600),m=Math.floor((s%3600)/60),sc=Math.floor(s%60);
    return h>0 ? `${h}:${String(m).padStart(2,'0')}:${String(sc).padStart(2,'0')}` : `${m}:${String(sc).padStart(2,'0')}`;
}
function shuffle(a){ for(let i=a.length-1;i>0;i--){const j=Math.floor(Math.random()*(i+1));[a[i],a[j]]=[a[j],a[i]];} }
</script>
</body>
</html>
