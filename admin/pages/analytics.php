<?php
/* ============================================================
   ITX Admin — Visitor Analytics
   Reads from `page_visits` and renders a professional dashboard
   (KPIs, trend charts, geo, devices, sources, top pages, live feed).
   ============================================================ */

/* ── Reset action ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'reset') {
    try {
        // Zero everything: page views AND the cached IP→location table.
        getDB()->exec("TRUNCATE TABLE page_visits");
        try { getDB()->exec("TRUNCATE TABLE ip_geo"); } catch (\Throwable $ex2) {}
        redirect_admin('analytics', 'تم مسح جميع بيانات الزيارات وذاكرة المواقع بالكامل.', 'success');
    } catch (\Throwable $ex) {
        redirect_admin('analytics', 'تعذّر المسح: ' . $ex->getMessage(), 'danger');
    }
}

/* ── Filters ── */
$range = (string)($_GET['range'] ?? '30');
if (!in_array($range, ['7', '30', '90', '365', 'all'], true)) $range = '30';
$includeBots = (($_GET['bots'] ?? '0') === '1');

$rangeLabels = ['7' => 'آخر 7 أيام', '30' => 'آخر 30 يوم', '90' => 'آخر 90 يوم', '365' => 'آخر سنة', 'all' => 'كل الفترة'];

/* ── WHERE builder ── */
$where  = [];
$params = [];
if (!$includeBots) $where[] = 'is_bot = 0';
$startDate = null;
$q = function (string $sql, array $p = []) {
    $s = getDB()->prepare($sql);
    $s->execute($p);
    return $s;
};

// Use MySQL's clock as "today" so the range/labels match stored rows
// even when PHP and MySQL run in different timezones.
$dbToday = date('Y-m-d');
try { $dbToday = getDB()->query("SELECT CURDATE()")->fetchColumn() ?: $dbToday; } catch (\Throwable $ex) {}

if ($range !== 'all') {
    $days      = (int)$range;
    $startDate = (new DateTime($dbToday))->modify('-' . ($days - 1) . ' days')->format('Y-m-d');
    $where[]   = 'visit_date >= ?';
    $params[]  = $startDate;
}
$W    = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
$botW = $includeBots ? '' : ' AND is_bot = 0';

$analyticsError = '';
$hasData = false;
$tot = ['v' => 0, 'u' => 0, 's' => 0, 'n' => 0];
$today = ['v' => 0, 'u' => 0];
$newU = 0; $botCount = 0;
$daily = $hourlyRaw = $wkRaw = $topPages = $countries = $devices = $browsers = $osList = $refs = $recent = [];

try {
    $tot   = $q("SELECT COUNT(*) v, COUNT(DISTINCT visitor_id) u, COUNT(DISTINCT session_id) s,
                        COALESCE(SUM(is_new_visitor),0) n FROM page_visits $W", $params)->fetch();
    $today = $q("SELECT COUNT(*) v, COUNT(DISTINCT visitor_id) u
                 FROM page_visits WHERE visit_date = CURDATE() $botW")->fetch();

    $wn = $where; $pn = $params; $wn[] = 'is_new_visitor = 1';
    $newU = (int)$q("SELECT COUNT(DISTINCT visitor_id) FROM page_visits WHERE " . implode(' AND ', $wn), $pn)->fetchColumn();

    $botCount = (int)$q("SELECT COUNT(*) FROM page_visits WHERE is_bot = 1"
                        . ($range !== 'all' ? ' AND visit_date >= ?' : ''),
                        $range !== 'all' ? [$startDate] : [])->fetchColumn();

    $daily     = $q("SELECT visit_date d, COUNT(*) v, COUNT(DISTINCT visitor_id) u
                     FROM page_visits $W GROUP BY visit_date ORDER BY visit_date", $params)->fetchAll();
    $hourlyRaw = $q("SELECT visit_hour h, COUNT(*) v FROM page_visits $W GROUP BY visit_hour", $params)->fetchAll(PDO::FETCH_KEY_PAIR);
    $wkRaw     = $q("SELECT weekday w, COUNT(*) v FROM page_visits $W GROUP BY weekday", $params)->fetchAll(PDO::FETCH_KEY_PAIR);

    $topPages  = $q("SELECT page_path, MAX(page_title) title, MAX(page_type) ptype,
                            COUNT(*) v, COUNT(DISTINCT visitor_id) u
                     FROM page_visits $W GROUP BY page_path ORDER BY v DESC LIMIT 15", $params)->fetchAll();
    $countries = $q("SELECT COALESCE(NULLIF(country,''),'غير معروف') country, country_code,
                            COUNT(*) v, COUNT(DISTINCT visitor_id) u
                     FROM page_visits $W GROUP BY country, country_code ORDER BY v DESC LIMIT 12", $params)->fetchAll();
    $devices   = $q("SELECT COALESCE(NULLIF(device_type,''),'أخرى') k, COUNT(*) v
                     FROM page_visits $W GROUP BY device_type ORDER BY v DESC", $params)->fetchAll();
    $browsers  = $q("SELECT COALESCE(NULLIF(browser,''),'أخرى') k, COUNT(*) v
                     FROM page_visits $W GROUP BY browser ORDER BY v DESC LIMIT 8", $params)->fetchAll();
    $osList    = $q("SELECT COALESCE(NULLIF(os,''),'أخرى') k, COUNT(*) v
                     FROM page_visits $W GROUP BY os ORDER BY v DESC LIMIT 8", $params)->fetchAll();
    $refs      = $q("SELECT CASE WHEN referrer_host IS NULL OR referrer_host = ''
                                 THEN 'مباشر / روابط محفوظة' ELSE referrer_host END src, COUNT(*) v
                     FROM page_visits $W GROUP BY src ORDER BY v DESC LIMIT 10", $params)->fetchAll();
    $recent    = $q("SELECT visited_at, page_title, page_path, country, country_code, city,
                            device_type, browser, os, referrer_host, is_bot
                     FROM page_visits " . ($includeBots ? '' : 'WHERE is_bot = 0') . "
                     ORDER BY id DESC LIMIT 25")->fetchAll();

    $hasData = ((int)$tot['v'] > 0) || $botCount > 0;
} catch (\Throwable $ex) {
    $analyticsError = $ex->getMessage();
}

/* ── Helpers ── */
$flag = function (?string $cc): string {
    $cc = strtoupper(trim((string)$cc));
    if ($cc === 'LO') return '🏠';
    if (strlen($cc) !== 2 || !ctype_alpha($cc)) return '🌐';
    $a = 0x1F1E6;
    return mb_convert_encoding('&#' . ($a + ord($cc[0]) - 65) . ';&#' . ($a + ord($cc[1]) - 65) . ';', 'UTF-8', 'HTML-ENTITIES');
};
$typeLabels = ['home' => 'الرئيسية', 'blog' => 'المدونة', 'blog_post' => 'مقال', 'page' => 'صفحة ثابتة', 'other' => 'أخرى'];
$devLabels  = ['desktop' => 'حاسوب', 'mobile' => 'جوال', 'tablet' => 'لوحي', 'أخرى' => 'أخرى'];
$fmt = fn($n) => number_format((int)$n);

/* ── Build daily series (fill gaps) ── */
$startLoop = $startDate;
if ($range === 'all') {
    $minD = null;
    try { $minD = $q("SELECT MIN(visit_date) FROM page_visits $W", $params)->fetchColumn(); } catch (\Throwable $ex) {}
    $startLoop = $minD ?: date('Y-m-d');
}
$dmap = [];
foreach ($daily as $r) $dmap[$r['d']] = $r;
$dLabels = $dV = $dU = [];
$cur = new DateTime($startLoop);
$end = new DateTime($dbToday);
$guard = 0;
while ($cur <= $end && $guard < 1200) {
    $k = $cur->format('Y-m-d');
    $dLabels[] = $cur->format('d/m');
    $dV[] = (int)($dmap[$k]['v'] ?? 0);
    $dU[] = (int)($dmap[$k]['u'] ?? 0);
    $cur->modify('+1 day');
    $guard++;
}

/* Hours 0-23 */
$hV = [];
for ($h = 0; $h < 24; $h++) $hV[$h] = (int)($hourlyRaw[$h] ?? 0);
$peakHour = $hV ? array_keys($hV, max($hV))[0] : 0;
$peakHourVal = $hV ? max($hV) : 0;

/* Weekdays (0=Sun..6=Sat) */
$wkNames = ['الأحد', 'الاثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت'];
$wV = [];
for ($d = 0; $d < 7; $d++) $wV[$d] = (int)($wkRaw[$d] ?? 0);
$peakDay = $wV ? array_keys($wV, max($wV))[0] : 0;

$returningU = max(0, (int)$tot['u'] - $newU);
$perSession = ((int)$tot['s'] > 0) ? round((int)$tot['v'] / (int)$tot['s'], 1) : 0;

/* ── Analytics storage footprint (total rows + on-disk size) ── */
$storeRows = 0; $storeBytes = 0;
try {
    $storeRows = (int)($q("SELECT COUNT(*) c FROM page_visits")->fetch()['c'] ?? 0);
    $dbName = getDB()->query("SELECT DATABASE()")->fetchColumn();
    $sz = $q("SELECT COALESCE(SUM(data_length + index_length),0) b
              FROM information_schema.TABLES
              WHERE table_schema = ? AND table_name IN ('page_visits','ip_geo')", [$dbName])->fetch();
    $storeBytes = (int)($sz['b'] ?? 0);
} catch (\Throwable $ex) {}
$fmtBytes = function ($b) {
    $u = ['بايت', 'KB', 'MB', 'GB']; $i = 0; $b = (float)$b;
    while ($b >= 1024 && $i < 3) { $b /= 1024; $i++; }
    return (($b < 10 && $i > 0) ? number_format($b, 1) : number_format($b)) . ' ' . $u[$i];
};

/* ── Initial "online now" snapshot (server-rendered to avoid a 0-flash before JS polls) ── */
$onlineInit = [];
try {
    $onlineInit = $q(
        "SELECT pv.page_type, pv.page_title, pv.page_path, pv.country, pv.country_code, pv.city,
                pv.device_type, pv.browser, pv.os, pv.referrer_host,
                TIMESTAMPDIFF(SECOND, pv.visited_at, NOW()) ago
         FROM page_visits pv
         JOIN (SELECT MAX(id) mid FROM page_visits
               WHERE visited_at >= NOW() - INTERVAL 5 MINUTE AND is_bot = 0 AND visitor_id IS NOT NULL
               GROUP BY visitor_id) t ON t.mid = pv.id
         ORDER BY pv.visited_at DESC"
    )->fetchAll();
} catch (\Throwable $ex) {}
$onlineCount = count($onlineInit);
$agoTxt = function ($s) {
    $s = (int)$s;
    if ($s < 10) return 'الآن';
    if ($s < 60) return 'منذ ' . $s . ' ث';
    return 'منذ ' . floor($s / 60) . ' د';
};

layout_start('تحليلات الزوّار', 'analytics');
?>
<style>
  .an-toolbar{display:flex;flex-wrap:wrap;gap:.6rem;align-items:center;justify-content:space-between;margin-bottom:1.4rem}
  .an-ranges{display:flex;flex-wrap:wrap;gap:.4rem}
  .an-chip{padding:.4rem 1rem;border:1.5px solid var(--border);border-radius:50px;background:#fff;
           color:var(--muted);font-size:.83rem;font-weight:700;text-decoration:none;transition:all .18s;white-space:nowrap}
  .an-chip:hover{border-color:var(--secondary);color:var(--secondary)}
  .an-chip.active{background:linear-gradient(135deg,var(--primary),var(--secondary));border-color:var(--primary);color:#fff}
  .an-toggle{display:flex;align-items:center;gap:.45rem;font-size:.82rem;color:var(--muted);font-weight:600}
  .an-kpis{display:grid;grid-template-columns:repeat(auto-fill,minmax(185px,1fr));gap:1.1rem;margin-bottom:1.6rem}
  .kpi{background:var(--card);border-radius:var(--radius);padding:1.3rem;box-shadow:var(--shadow);
       position:relative;overflow:hidden;border-inline-start:4px solid var(--secondary)}
  .kpi .k-top{display:flex;align-items:center;justify-content:space-between;margin-bottom:.6rem}
  .kpi .k-ico{width:38px;height:38px;border-radius:9px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:1rem}
  .kpi .k-val{font-size:1.9rem;font-weight:900;color:var(--primary);line-height:1}
  .kpi .k-lbl{font-size:.82rem;color:var(--muted);margin-top:.35rem}
  .kpi .k-sub{font-size:.74rem;color:var(--muted);margin-top:.5rem;display:flex;align-items:center;gap:.3rem}
  .an-grid{display:grid;gap:1.4rem}
  .an-2{grid-template-columns:1fr 1fr}
  .an-3{grid-template-columns:2fr 1fr}
  @media(max-width:900px){.an-2,.an-3{grid-template-columns:1fr}}
  .chart-box{position:relative;width:100%}
  .mini-list{list-style:none;margin:0;padding:0}
  .mini-list li{display:flex;align-items:center;gap:.6rem;padding:.55rem 0;border-bottom:1px solid var(--border);font-size:.86rem}
  .mini-list li:last-child{border-bottom:none}
  .mini-list .ml-name{flex:1;color:var(--txt);font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
  .mini-list .ml-val{font-weight:800;color:var(--primary)}
  .ml-bar{height:6px;border-radius:6px;background:linear-gradient(90deg,var(--secondary),var(--accent));min-width:6px}
  .ml-bar-wrap{flex:0 0 34%;background:#eef2f6;border-radius:6px;overflow:hidden}
  .badge-type{font-size:.7rem;padding:.12rem .5rem;border-radius:50px;background:#eef2f6;color:var(--muted);font-weight:700}
  .live-dot{width:8px;height:8px;border-radius:50%;background:var(--success);box-shadow:0 0 0 rgba(16,185,129,.6);animation:lp 1.6s infinite}
  @keyframes lp{0%{box-shadow:0 0 0 0 rgba(16,185,129,.5)}70%{box-shadow:0 0 0 8px rgba(16,185,129,0)}100%{box-shadow:0 0 0 0 rgba(16,185,129,0)}}
  .tbl-scroll{overflow-x:auto}
  .empty-an{text-align:center;padding:3rem 1rem;color:var(--muted)}
  .empty-an i{font-size:2.4rem;color:var(--border);display:block;margin-bottom:.8rem}
</style>

<?php if ($analyticsError): ?>
  <div class="alert alert-danger">
    <i class="fas fa-exclamation-triangle"></i>
    تعذّر قراءة بيانات التحليلات. تأكّد من تنفيذ ملف
    <code>database/migration_2026_09_17_analytics.sql</code>.
    <br><small><?= e($analyticsError) ?></small>
  </div>
<?php endif; ?>

<!-- Toolbar -->
<div class="an-toolbar">
  <div class="an-ranges">
    <?php foreach ($rangeLabels as $rk => $rl): ?>
      <a class="an-chip <?= $range === $rk ? 'active' : '' ?>"
         href="<?= admin_url('analytics') ?>&range=<?= $rk ?><?= $includeBots ? '&bots=1' : '' ?>"><?= e($rl) ?></a>
    <?php endforeach; ?>
  </div>
  <label class="an-toggle">
    <input type="checkbox" onchange="location.href='<?= admin_url('analytics') ?>&range=<?= e($range) ?><?= $includeBots ? '' : '&bots=1' ?>'"
           <?= $includeBots ? 'checked' : '' ?>>
    تضمين زيارات الروبوتات (<?= $fmt($botCount) ?>)
  </label>
</div>

<!-- Who is online now (live) -->
<div class="card" style="margin-bottom:1.4rem">
  <div class="card-head">
    <h2><span class="live-dot" style="display:inline-block;margin-inline-end:.45rem"></span>
      المتواجدون الآن
      <span id="onNowCount" class="badge-type" style="background:var(--success);color:#fff;font-size:.82rem;margin-inline-start:.45rem"><?= $onlineCount ?></span>
    </h2>
    <span style="font-size:.76rem;color:var(--muted)">
      <i class="fas fa-sync-alt" id="onSpin"></i>
      آخر تحديث <span id="onUpdated"><?= date('H:i:s') ?></span> · تلقائي كل 15 ثانية
    </span>
  </div>
  <div class="card-body">
    <div class="empty-an" id="onEmpty" style="padding:2rem 1rem;<?= $onlineCount ? 'display:none' : '' ?>">
      <i class="fas fa-user-clock"></i> لا يوجد زوّار نشطون في آخر 5 دقائق
    </div>
    <div class="tbl-scroll" id="onTableWrap" style="<?= $onlineCount ? '' : 'display:none' ?>">
      <table class="tbl">
        <thead><tr><th>منذ</th><th>الصفحة</th><th>المكان</th><th>الجهاز</th><th>المتصفح</th><th>المصدر</th></tr></thead>
        <tbody id="onRows">
        <?php foreach ($onlineInit as $o):
          $loc = $flag($o['country_code']) . ' ' . ($o['country'] ?: 'غير معروف');
          if (!empty($o['city']) && $o['city'] !== $o['country']) $loc .= ' — ' . $o['city'];
          $tech = trim(($o['browser'] ?: '') . ($o['os'] ? ' · ' . $o['os'] : '')); ?>
          <tr>
            <td style="white-space:nowrap;color:var(--success);font-weight:700;font-size:.8rem"><?= e($agoTxt($o['ago'])) ?></td>
            <td style="font-weight:600"><?= e($o['page_title'] ?: $o['page_path']) ?> <span class="badge-type"><?= e($typeLabels[$o['page_type']] ?? $o['page_type']) ?></span></td>
            <td><?= e($loc) ?></td>
            <td><?= e($devLabels[$o['device_type']] ?? $o['device_type'] ?? '—') ?></td>
            <td style="font-size:.82rem"><?= e($tech) ?></td>
            <td style="font-size:.8rem;color:var(--muted)"><?= e($o['referrer_host'] ?: 'مباشر') ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php if (!$hasData && !$analyticsError): ?>
  <div class="card"><div class="card-body empty-an">
    <i class="fas fa-chart-line"></i>
    <h3 style="color:var(--primary);margin-bottom:.4rem">لا توجد زيارات مسجّلة بعد</h3>
    <p>يتم تسجيل كل زيارة للموقع تلقائياً. تصفّح
       <a href="<?= site_url() ?>" target="_blank" style="color:var(--secondary);font-weight:700">الموقع</a>
       ثم عُد إلى هنا لرؤية البيانات.</p>
  </div></div>
<?php else: ?>

<!-- KPIs -->
<div class="an-kpis">
  <div class="kpi" style="border-color:var(--secondary)">
    <div class="k-top"><span class="k-lbl">إجمالي المشاهدات</span>
      <span class="k-ico" style="background:var(--secondary)"><i class="fas fa-eye"></i></span></div>
    <div class="k-val"><?= $fmt($tot['v']) ?></div>
    <div class="k-sub"><i class="fas fa-calendar-day"></i> <?= e($rangeLabels[$range]) ?></div>
  </div>
  <div class="kpi" style="border-color:var(--accent)">
    <div class="k-top"><span class="k-lbl">زوّار فريدون</span>
      <span class="k-ico" style="background:#0FBF9E"><i class="fas fa-users"></i></span></div>
    <div class="k-val"><?= $fmt($tot['u']) ?></div>
    <div class="k-sub"><i class="fas fa-user-plus"></i> <?= $fmt($newU) ?> جديد · <?= $fmt($returningU) ?> عائد</div>
  </div>
  <div class="kpi" style="border-color:var(--warning)">
    <div class="k-top"><span class="k-lbl">الجلسات</span>
      <span class="k-ico" style="background:var(--warning)"><i class="fas fa-mouse-pointer"></i></span></div>
    <div class="k-val"><?= $fmt($tot['s']) ?></div>
    <div class="k-sub"><i class="fas fa-layer-group"></i> <?= $perSession ?> صفحة / جلسة</div>
  </div>
  <div class="kpi" style="border-color:var(--success)">
    <div class="k-top"><span class="k-lbl">زيارات اليوم</span>
      <span class="k-ico" style="background:var(--success)"><i class="fas fa-bolt"></i></span></div>
    <div class="k-val"><?= $fmt($today['v']) ?></div>
    <div class="k-sub"><i class="fas fa-user"></i> <?= $fmt($today['u']) ?> زائر فريد اليوم</div>
  </div>
  <div class="kpi" style="border-color:var(--info)">
    <div class="k-top"><span class="k-lbl">وقت الذروة</span>
      <span class="k-ico" style="background:var(--info)"><i class="fas fa-clock"></i></span></div>
    <div class="k-val"><?= sprintf('%02d:00', $peakHour) ?></div>
    <div class="k-sub"><i class="fas fa-fire"></i> <?= $fmt($peakHourVal) ?> مشاهدة · <?= e($wkNames[$peakDay]) ?></div>
  </div>
</div>

<!-- Trend + New/Returning -->
<div class="an-grid an-3" style="margin-bottom:1.4rem">
  <div class="card" style="margin:0">
    <div class="card-head"><h2><i class="fas fa-chart-area"></i> المشاهدات والزوّار عبر الوقت</h2></div>
    <div class="card-body"><div class="chart-box" style="height:300px"><canvas id="chTrend"></canvas></div></div>
  </div>
  <div class="card" style="margin:0">
    <div class="card-head"><h2><i class="fas fa-user-check"></i> جدد مقابل عائدين</h2></div>
    <div class="card-body"><div class="chart-box" style="height:300px"><canvas id="chNewRet"></canvas></div></div>
  </div>
</div>

<!-- Hours + Weekdays -->
<div class="an-grid an-2" style="margin-bottom:1.4rem">
  <div class="card" style="margin:0">
    <div class="card-head"><h2><i class="fas fa-business-time"></i> أكثر ساعات النشاط</h2></div>
    <div class="card-body"><div class="chart-box" style="height:250px"><canvas id="chHours"></canvas></div></div>
  </div>
  <div class="card" style="margin:0">
    <div class="card-head"><h2><i class="fas fa-calendar-week"></i> النشاط حسب أيام الأسبوع</h2></div>
    <div class="card-body"><div class="chart-box" style="height:250px"><canvas id="chWeek"></canvas></div></div>
  </div>
</div>

<!-- Devices / Browsers / OS -->
<div class="an-grid" style="grid-template-columns:1fr 1fr 1fr;margin-bottom:1.4rem">
  <div class="card" style="margin:0">
    <div class="card-head"><h2><i class="fas fa-mobile-alt"></i> الأجهزة</h2></div>
    <div class="card-body"><div class="chart-box" style="height:220px"><canvas id="chDev"></canvas></div></div>
  </div>
  <div class="card" style="margin:0">
    <div class="card-head"><h2><i class="fas fa-globe-americas"></i> المتصفحات</h2></div>
    <div class="card-body">
      <?php $mx = $browsers ? max(array_column($browsers, 'v')) : 1; ?>
      <ul class="mini-list">
        <?php foreach ($browsers as $b): ?>
          <li><span class="ml-name"><?= e($b['k']) ?></span>
            <div class="ml-bar-wrap"><div class="ml-bar" style="width:<?= max(4, round($b['v'] / $mx * 100)) ?>%"></div></div>
            <span class="ml-val"><?= $fmt($b['v']) ?></span></li>
        <?php endforeach; ?>
        <?php if (!$browsers): ?><li><span class="ml-name" style="color:var(--muted)">لا بيانات</span></li><?php endif; ?>
      </ul>
    </div>
  </div>
  <div class="card" style="margin:0">
    <div class="card-head"><h2><i class="fas fa-desktop"></i> أنظمة التشغيل</h2></div>
    <div class="card-body">
      <?php $mxo = $osList ? max(array_column($osList, 'v')) : 1; ?>
      <ul class="mini-list">
        <?php foreach ($osList as $o): ?>
          <li><span class="ml-name"><?= e($o['k']) ?></span>
            <div class="ml-bar-wrap"><div class="ml-bar" style="width:<?= max(4, round($o['v'] / $mxo * 100)) ?>%"></div></div>
            <span class="ml-val"><?= $fmt($o['v']) ?></span></li>
        <?php endforeach; ?>
        <?php if (!$osList): ?><li><span class="ml-name" style="color:var(--muted)">لا بيانات</span></li><?php endif; ?>
      </ul>
    </div>
  </div>
</div>

<!-- Geo + Sources -->
<div class="an-grid an-2" style="margin-bottom:1.4rem">
  <div class="card" style="margin:0">
    <div class="card-head"><h2><i class="fas fa-map-marked-alt"></i> الدول / أماكن الزوّار</h2></div>
    <div class="card-body p0">
      <div class="tbl-scroll"><table class="tbl">
        <thead><tr><th>الدولة</th><th>مشاهدات</th><th>زوّار</th><th style="width:30%">النسبة</th></tr></thead>
        <tbody>
          <?php $mxc = $countries ? max(array_column($countries, 'v')) : 1; ?>
          <?php if ($countries): foreach ($countries as $c): ?>
            <tr>
              <td><?= $flag($c['country_code']) ?> <?= e($c['country']) ?></td>
              <td><?= $fmt($c['v']) ?></td>
              <td><?= $fmt($c['u']) ?></td>
              <td><div class="ml-bar-wrap"><div class="ml-bar" style="width:<?= max(4, round($c['v'] / $mxc * 100)) ?>%"></div></div></td>
            </tr>
          <?php endforeach; else: ?>
            <tr class="empty-row"><td colspan="4"><i class="fas fa-inbox"></i> لا بيانات</td></tr>
          <?php endif; ?>
        </tbody>
      </table></div>
    </div>
  </div>
  <div class="card" style="margin:0">
    <div class="card-head"><h2><i class="fas fa-project-diagram"></i> مصادر الزيارات</h2></div>
    <div class="card-body">
      <?php $mxr = $refs ? max(array_column($refs, 'v')) : 1; ?>
      <ul class="mini-list">
        <?php foreach ($refs as $r): ?>
          <li>
            <i class="fas <?= strpos($r['src'], 'مباشر') !== false ? 'fa-link' : 'fa-external-link-alt' ?>" style="color:var(--secondary)"></i>
            <span class="ml-name" title="<?= e($r['src']) ?>"><?= e($r['src']) ?></span>
            <div class="ml-bar-wrap"><div class="ml-bar" style="width:<?= max(4, round($r['v'] / $mxr * 100)) ?>%"></div></div>
            <span class="ml-val"><?= $fmt($r['v']) ?></span>
          </li>
        <?php endforeach; ?>
        <?php if (!$refs): ?><li><span class="ml-name" style="color:var(--muted)">لا بيانات</span></li><?php endif; ?>
      </ul>
    </div>
  </div>
</div>

<!-- Top pages -->
<div class="card">
  <div class="card-head"><h2><i class="fas fa-fire-alt"></i> أكثر الصفحات زيارة</h2></div>
  <div class="card-body p0"><div class="tbl-scroll"><table class="tbl">
    <thead><tr><th>#</th><th>الصفحة</th><th>النوع</th><th>المسار</th><th>مشاهدات</th><th>زوّار</th></tr></thead>
    <tbody>
      <?php if ($topPages): $i = 1; foreach ($topPages as $p): ?>
        <tr>
          <td><?= $i++ ?></td>
          <td style="font-weight:700"><?= e($p['title'] ?: '—') ?></td>
          <td><span class="badge-type"><?= e($typeLabels[$p['ptype']] ?? $p['ptype']) ?></span></td>
          <td style="direction:ltr;text-align:right;color:var(--muted);font-size:.8rem;max-width:260px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
            <?= e($p['page_path']) ?></td>
          <td style="font-weight:800;color:var(--primary)"><?= $fmt($p['v']) ?></td>
          <td><?= $fmt($p['u']) ?></td>
        </tr>
      <?php endforeach; else: ?>
        <tr class="empty-row"><td colspan="6"><i class="fas fa-inbox"></i> لا بيانات</td></tr>
      <?php endif; ?>
    </tbody>
  </table></div></div>
</div>

<!-- Live feed -->
<div class="card">
  <div class="card-head">
    <h2><span class="live-dot" style="display:inline-block;margin-inline-end:.4rem"></span> أحدث الزيارات</h2>
    <div style="display:flex;align-items:center;gap:.7rem;flex-wrap:wrap">
      <div title="الحجم الكلي لبيانات الإحصائيات في قاعدة البيانات"
           style="display:flex;align-items:center;gap:.9rem;background:var(--bg);border:1px solid var(--border);border-radius:10px;padding:.45rem .95rem">
        <div style="text-align:center">
          <div style="font-weight:900;color:var(--primary);font-size:1.05rem;line-height:1"><?= number_format($storeRows) ?></div>
          <div style="font-size:.68rem;color:var(--muted);margin-top:2px">سجل زيارة</div>
        </div>
        <div style="width:1px;align-self:stretch;background:var(--border)"></div>
        <div style="text-align:center">
          <div style="font-weight:900;color:<?= $storeBytes > 20971520 ? 'var(--danger)' : 'var(--accent)' ?>;font-size:1.05rem;line-height:1"><i class="fas fa-database" style="font-size:.8rem"></i> <?= e($fmtBytes($storeBytes)) ?></div>
          <div style="font-size:.68rem;color:var(--muted);margin-top:2px">حجم البيانات</div>
        </div>
      </div>
      <form method="POST" onsubmit="return confirm('سيتم حذف كل بيانات الزيارات وذاكرة المواقع نهائياً وتصفير كل شيء. هل أنت متأكد؟')" style="margin:0">
        <input type="hidden" name="action" value="reset">
        <button class="btn btn-danger btn-sm"><i class="fas fa-trash-alt"></i> مسح كل البيانات</button>
      </form>
    </div>
  </div>
  <div class="card-body p0"><div class="tbl-scroll"><table class="tbl">
    <thead><tr><th>الوقت</th><th>الصفحة</th><th>الموقع</th><th>الجهاز</th><th>المتصفح</th><th>المصدر</th></tr></thead>
    <tbody>
      <?php if ($recent): foreach ($recent as $r): ?>
        <tr<?= $r['is_bot'] ? ' style="opacity:.55"' : '' ?>>
          <td style="white-space:nowrap;font-size:.8rem;color:var(--muted)"><?= e(date('H:i · d/m', strtotime($r['visited_at']))) ?></td>
          <td style="font-weight:600"><?= e($r['page_title'] ?: $r['page_path']) ?><?= $r['is_bot'] ? ' <span class="badge-type">روبوت</span>' : '' ?></td>
          <td><?= $flag($r['country_code']) ?> <?= e($r['country'] ?: '—') ?><?= $r['city'] ? ' <span style="color:var(--muted);font-size:.78rem">('.e($r['city']).')</span>' : '' ?></td>
          <td><?= e($devLabels[$r['device_type']] ?? $r['device_type'] ?? '—') ?></td>
          <td style="font-size:.82rem"><?= e($r['browser'] ?: '—') ?> · <?= e($r['os'] ?: '') ?></td>
          <td style="font-size:.8rem;color:var(--muted)"><?= e($r['referrer_host'] ?: 'مباشر') ?></td>
        </tr>
      <?php endforeach; else: ?>
        <tr class="empty-row"><td colspan="6"><i class="fas fa-inbox"></i> لا زيارات</td></tr>
      <?php endif; ?>
    </tbody>
  </table></div></div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>
(function(){
  if (typeof Chart === 'undefined') return;
  Chart.defaults.font.family = "'Cairo', sans-serif";
  Chart.defaults.color = '#6b7280';
  var C = {primary:'#3F4D60', secondary:'#2FA8B9', accent:'#0FECC1', warning:'#f59e0b',
           info:'#3b82f6', success:'#10b981', danger:'#ef4444', purple:'#8b5cf6', pink:'#ec4899'};
  var palette = [C.secondary, C.accent, C.warning, C.info, C.purple, C.pink, C.success, C.danger, C.primary];

  var trendGrad;
  var trendCtx = document.getElementById('chTrend');
  if (trendCtx) {
    var g = trendCtx.getContext('2d').createLinearGradient(0,0,0,300);
    g.addColorStop(0,'rgba(47,168,185,.35)'); g.addColorStop(1,'rgba(47,168,185,0)');
    new Chart(trendCtx, {
      type:'line',
      data:{labels:<?= json_encode($dLabels) ?>,
        datasets:[
          {label:'المشاهدات', data:<?= json_encode($dV) ?>, borderColor:C.secondary, backgroundColor:g,
           fill:true, tension:.35, borderWidth:2.5, pointRadius:0, pointHoverRadius:5},
          {label:'زوّار فريدون', data:<?= json_encode($dU) ?>, borderColor:C.accent, backgroundColor:'transparent',
           fill:false, tension:.35, borderWidth:2, borderDash:[5,4], pointRadius:0, pointHoverRadius:5}
        ]},
      options:{responsive:true, maintainAspectRatio:false, interaction:{mode:'index',intersect:false},
        plugins:{legend:{position:'top',labels:{usePointStyle:true,boxWidth:8,padding:16}}},
        scales:{x:{grid:{display:false},ticks:{maxTicksLimit:12}},
                y:{beginAtZero:true,grid:{color:'#eef2f6'},ticks:{precision:0}}}}
    });
  }

  var nr = document.getElementById('chNewRet');
  if (nr) new Chart(nr, {
    type:'doughnut',
    data:{labels:['زوّار جدد','زوّار عائدون'],
      datasets:[{data:[<?= (int)$newU ?>, <?= (int)$returningU ?>],
        backgroundColor:[C.secondary, C.accent], borderWidth:0}]},
    options:{responsive:true, maintainAspectRatio:false, cutout:'62%',
      plugins:{legend:{position:'bottom',labels:{usePointStyle:true,boxWidth:8,padding:14}}}}
  });

  var hrs = document.getElementById('chHours');
  if (hrs) new Chart(hrs, {
    type:'bar',
    data:{labels:<?= json_encode(array_map(fn($h) => sprintf('%02d', $h), array_keys($hV))) ?>,
      datasets:[{label:'مشاهدات', data:<?= json_encode(array_values($hV)) ?>,
        backgroundColor:C.secondary, borderRadius:5, maxBarThickness:16}]},
    options:{responsive:true, maintainAspectRatio:false,
      plugins:{legend:{display:false}, tooltip:{callbacks:{title:function(t){return 'الساعة '+t[0].label+':00';}}}},
      scales:{x:{grid:{display:false}}, y:{beginAtZero:true,grid:{color:'#eef2f6'},ticks:{precision:0}}}}
  });

  var wk = document.getElementById('chWeek');
  if (wk) new Chart(wk, {
    type:'bar',
    data:{labels:<?= json_encode($wkNames) ?>,
      datasets:[{label:'مشاهدات', data:<?= json_encode(array_values($wV)) ?>,
        backgroundColor:C.accent, borderRadius:5, maxBarThickness:34}]},
    options:{responsive:true, maintainAspectRatio:false,
      plugins:{legend:{display:false}},
      scales:{x:{grid:{display:false}}, y:{beginAtZero:true,grid:{color:'#eef2f6'},ticks:{precision:0}}}}
  });

  var dv = document.getElementById('chDev');
  if (dv) new Chart(dv, {
    type:'doughnut',
    data:{labels:<?= json_encode(array_map(fn($d) => $devLabels[$d['k']] ?? $d['k'], $devices)) ?>,
      datasets:[{data:<?= json_encode(array_map(fn($d) => (int)$d['v'], $devices)) ?>,
        backgroundColor:palette, borderWidth:0}]},
    options:{responsive:true, maintainAspectRatio:false, cutout:'58%',
      plugins:{legend:{position:'bottom',labels:{usePointStyle:true,boxWidth:8,padding:12}}}}
  });
})();
</script>

<?php endif; ?>

<script>
(function(){
  var url = '<?= admin_prefix() ?>/online.php';
  var elCount = document.getElementById('onNowCount'),
      elUpd   = document.getElementById('onUpdated'),
      elEmpty = document.getElementById('onEmpty'),
      elWrap  = document.getElementById('onTableWrap'),
      elRows  = document.getElementById('onRows'),
      elSpin  = document.getElementById('onSpin');
  if (!elCount) return;

  function ago(s){
    s = parseInt(s,10) || 0;
    if (s < 10)  return 'الآن';
    if (s < 60)  return 'منذ ' + s + ' ث';
    var m = Math.floor(s/60);
    return 'منذ ' + m + ' د';
  }
  function esc(t){ var d=document.createElement('div'); d.textContent = t==null?'':t; return d.innerHTML; }

  function render(d){
    elCount.textContent = d.count || 0;
    elUpd.textContent   = d.updated || '—';
    var v = d.visitors || [];
    if (!v.length){ elEmpty.style.display='block'; elWrap.style.display='none'; elRows.innerHTML=''; return; }
    elEmpty.style.display='none'; elWrap.style.display='block';
    elRows.innerHTML = v.map(function(x){
      return '<tr>'+
        '<td style="white-space:nowrap;color:var(--success);font-weight:700;font-size:.8rem">'+esc(ago(x.ago))+'</td>'+
        '<td style="font-weight:600">'+esc(x.page)+' <span class="badge-type">'+esc(x.type)+'</span></td>'+
        '<td>'+esc(x.loc)+'</td>'+
        '<td>'+esc(x.device)+'</td>'+
        '<td style="font-size:.82rem">'+esc(x.tech)+'</td>'+
        '<td style="font-size:.8rem;color:var(--muted)">'+esc(x.src)+'</td>'+
      '</tr>';
    }).join('');
  }

  function poll(){
    if (elSpin) elSpin.style.opacity = '.4';
    fetch(url, {credentials:'same-origin', headers:{'X-Requested-With':'fetch'}})
      .then(function(r){ return r.json(); })
      .then(render)
      .catch(function(){})
      .finally(function(){ if (elSpin) elSpin.style.opacity = '1'; });
  }
  poll();
  setInterval(poll, 15000);
})();
</script>

<?php layout_end(); ?>
