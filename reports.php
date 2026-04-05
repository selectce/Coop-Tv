<?php
$currentPage = 'reports';
$pageTitle   = 'Relatórios';
require_once __DIR__ . '/../includes/header.php';

// Filters
$period  = $_GET['period']  ?? '7';
$storeId = (int)($_GET['store'] ?? 0);
$stores  = dbFetchAll("SELECT id, name FROM stores ORDER BY name");

$wherePeriod = "played_at >= DATE_SUB(NOW(), INTERVAL ? DAY)";
$params      = [(int)$period];
if ($storeId) { $wherePeriod .= " AND l.store_id = ?"; $params[] = $storeId; }

// Total plays
$totalPlays = dbFetch("SELECT COUNT(*) as c FROM playback_logs l WHERE $wherePeriod", $params)['c'];

// Plays per day
$playsPerDay = dbFetchAll("
    SELECT DATE(l.played_at) as day, COUNT(*) as plays
    FROM playback_logs l
    WHERE $wherePeriod
    GROUP BY DATE(l.played_at)
    ORDER BY day ASC
", $params);

// Top media
$topMedia = dbFetchAll("
    SELECT m.original_name, m.type, COUNT(*) as plays,
           SEC_TO_TIME(SUM(m.duration)) as total_time
    FROM playback_logs l
    JOIN media m ON l.media_id = m.id
    WHERE $wherePeriod
    GROUP BY l.media_id
    ORDER BY plays DESC
    LIMIT 20
", $params);

// By store
$byStore = dbFetchAll("
    SELECT s.name, s.type as stype, COUNT(l.id) as plays
    FROM stores s
    LEFT JOIN playback_logs l ON s.id = l.store_id AND $wherePeriod
    GROUP BY s.id
    ORDER BY plays DESC
", $params);

// By hour of day
$byHour = dbFetchAll("
    SELECT HOUR(l.played_at) as hour, COUNT(*) as plays
    FROM playback_logs l
    WHERE $wherePeriod
    GROUP BY HOUR(l.played_at)
    ORDER BY hour
", $params);

// Fill missing hours
$hourData = array_fill(0, 24, 0);
foreach ($byHour as $h) $hourData[(int)$h['hour']] = (int)$h['plays'];

// Top unique days
$uniqueDays = count($playsPerDay);
$avgPerDay  = $uniqueDays ? round($totalPlays / $uniqueDays) : 0;
?>

<!-- Filter Bar -->
<div class="report-filters card" style="margin-bottom:1.5rem">
  <form method="GET" class="filter-form">
    <div class="field">
      <label>Período</label>
      <select name="period" onchange="this.form.submit()">
        <option value="1"  <?= $period=='1'?'selected':'' ?>>Hoje</option>
        <option value="7"  <?= $period=='7'?'selected':'' ?>>Últimos 7 dias</option>
        <option value="30" <?= $period=='30'?'selected':'' ?>>Últimos 30 dias</option>
        <option value="90" <?= $period=='90'?'selected':'' ?>>Últimos 90 dias</option>
      </select>
    </div>
    <div class="field">
      <label>Loja</label>
      <select name="store" onchange="this.form.submit()">
        <option value="0">Todas as lojas</option>
        <?php foreach ($stores as $s): ?>
          <option value="<?= $s['id'] ?>" <?= $storeId==$s['id']?'selected':'' ?>>
            <?= sanitize($s['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-filter"></i> Filtrar</button>
    <a href="<?= BASE_URL ?>/admin/reports.php" class="btn">Limpar</a>
  </form>
</div>

<!-- Stats summary -->
<div class="stats-grid" style="margin-bottom:1.5rem">
  <div class="stat-card blue">
    <div class="stat-icon"><i class="fa-solid fa-play"></i></div>
    <div class="stat-info">
      <div class="stat-value"><?= number_format($totalPlays) ?></div>
      <div class="stat-label">Total de Reproduções</div>
      <div class="stat-sub">nos últimos <?= $period ?> dia(s)</div>
    </div>
  </div>
  <div class="stat-card green">
    <div class="stat-icon"><i class="fa-solid fa-calendar-day"></i></div>
    <div class="stat-info">
      <div class="stat-value"><?= number_format($avgPerDay) ?></div>
      <div class="stat-label">Média por Dia</div>
      <div class="stat-sub"><?= $uniqueDays ?> dia(s) com dados</div>
    </div>
  </div>
  <div class="stat-card purple">
    <div class="stat-icon"><i class="fa-solid fa-store"></i></div>
    <div class="stat-info">
      <div class="stat-value"><?= count(array_filter($byStore, fn($s)=>$s['plays']>0)) ?></div>
      <div class="stat-label">Lojas Ativas</div>
      <div class="stat-sub">com reproduções</div>
    </div>
  </div>
  <div class="stat-card orange">
    <div class="stat-icon"><i class="fa-solid fa-trophy"></i></div>
    <div class="stat-info">
      <div class="stat-value"><?= sanitize($topMedia[0]['original_name'] ?? '—') ?></div>
      <div class="stat-label">Mais Reproduzido</div>
      <div class="stat-sub"><?= $topMedia[0]['plays'] ?? 0 ?> reproduções</div>
    </div>
  </div>
</div>

<div class="dash-grid">
  <!-- Reproduções por dia -->
  <div class="card">
    <div class="card-header"><h3><i class="fa-solid fa-chart-line"></i> Reproduções por Dia</h3></div>
    <div class="card-body"><canvas id="chartDay" height="200"></canvas></div>
  </div>

  <!-- Por hora -->
  <div class="card">
    <div class="card-header"><h3><i class="fa-solid fa-clock"></i> Distribuição por Hora</h3></div>
    <div class="card-body"><canvas id="chartHour" height="200"></canvas></div>
  </div>
</div>

<div class="dash-grid" style="margin-top:1.5rem">
  <!-- Top mídias -->
  <div class="card">
    <div class="card-header"><h3><i class="fa-solid fa-trophy"></i> Top Mídias</h3></div>
    <div class="card-body p0">
      <?php if (empty($topMedia)): ?>
        <p class="empty-msg">Nenhum dado para o período selecionado.</p>
      <?php else: ?>
      <table class="table">
        <thead><tr><th>#</th><th>Mídia</th><th>Tipo</th><th>Reproduções</th><th>Barra</th></tr></thead>
        <tbody>
        <?php
        $maxP = max(array_column($topMedia, 'plays'));
        foreach ($topMedia as $i => $m):
            $pct = $maxP ? round($m['plays']/$maxP*100) : 0;
        ?>
        <tr>
          <td><?= $i+1 ?></td>
          <td><?= sanitize($m['original_name']) ?></td>
          <td><span class="badge <?= $m['type']==='video'?'badge-purple':'badge-blue' ?>"><?= ucfirst($m['type']) ?></span></td>
          <td><strong><?= number_format($m['plays']) ?></strong></td>
          <td><div class="bar-mini"><div style="width:<?= $pct ?>%"></div></div></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>
  </div>

  <!-- Por loja -->
  <div class="card">
    <div class="card-header"><h3><i class="fa-solid fa-store"></i> Reproduções por Ponto</h3></div>
    <div class="card-body p0">
      <table class="table">
        <thead><tr><th>Loja</th><th>Tipo</th><th>Reproduções</th><th>Barra</th></tr></thead>
        <tbody>
        <?php
        $maxS = max(array_column($byStore, 'plays') ?: [0]);
        foreach ($byStore as $s):
            $pct = $maxS ? round($s['plays']/$maxS*100) : 0;
        ?>
        <tr>
          <td><?= sanitize($s['name']) ?></td>
          <td><span class="badge <?= $s['stype']==='own'?'badge-blue':'badge-orange' ?>"><?= $s['stype']==='own'?'Própria':'Franquia' ?></span></td>
          <td><strong><?= number_format($s['plays']) ?></strong></td>
          <td><div class="bar-mini"><div style="width:<?= $pct ?>%"></div></div></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
<script>
const chartColor = '#6366f1';

// Plays per day chart
const dayLabels = <?= json_encode(array_column($playsPerDay, 'day')) ?>;
const dayValues = <?= json_encode(array_map(fn($d)=>(int)$d['plays'], $playsPerDay)) ?>;
new Chart(document.getElementById('chartDay'), {
    type: 'bar',
    data: {
        labels: dayLabels.map(d => {
            const [y,m,day] = d.split('-');
            return `${day}/${m}`;
        }),
        datasets: [{ label: 'Reproduções', data: dayValues, backgroundColor: chartColor + 'cc', borderRadius: 4 }]
    },
    options: { plugins:{legend:{display:false}}, scales:{y:{beginAtZero:true,ticks:{precision:0}}} }
});

// By hour chart
const hourLabels = Array.from({length:24}, (_,i) => i + 'h');
const hourValues = <?= json_encode(array_values($hourData)) ?>;
new Chart(document.getElementById('chartHour'), {
    type: 'line',
    data: {
        labels: hourLabels,
        datasets: [{
            label: 'Reproduções',
            data: hourValues,
            backgroundColor: chartColor + '33',
            borderColor: chartColor,
            fill: true,
            tension: 0.3
        }]
    },
    options: { plugins:{legend:{display:false}}, scales:{y:{beginAtZero:true,ticks:{precision:0}}} }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
