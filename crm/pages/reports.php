<?php
/* CRM — reports: conversion by source, monthly trend, value analysis */
$statuses = crm_statuses();
$sources  = crm_sources();

/* Conversion by source */
$srcStats = db_all(
    "SELECT source,
        COUNT(*) total,
        SUM(status='won') won,
        SUM(status='lost') lost,
        SUM(CASE WHEN status='won' THEN deal_value ELSE 0 END) won_val
     FROM customers GROUP BY source ORDER BY total DESC"
);

/* Monthly new customers — last 12 months */
$months = db_all(
    "SELECT DATE_FORMAT(created_at,'%Y-%m') m, COUNT(*) c,
        SUM(status='won') won
     FROM customers WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 11 MONTH)
     GROUP BY m ORDER BY m"
);
$mmap = [];
foreach ($months as $r) $mmap[$r['m']] = $r;
$mLbl = $mNew = $mWon = [];
$cur = new DateTime((db_row("SELECT DATE_FORMAT(CURDATE(),'%Y-%m-01') d")['d'] ?? date('Y-m-01')));
$cur->modify('-11 months');
for ($i = 0; $i < 12; $i++) { $k = $cur->format('Y-m'); $mLbl[] = $cur->format('Y-m'); $mNew[] = (int)($mmap[$k]['c'] ?? 0); $mWon[] = (int)($mmap[$k]['won'] ?? 0); $cur->modify('+1 month'); }

/* Value by currency */
$valByCur = db_all(
    "SELECT currency,
        SUM(CASE WHEN status NOT IN('won','lost') THEN deal_value ELSE 0 END) open_val,
        SUM(CASE WHEN status='won' THEN deal_value ELSE 0 END) won_val,
        COUNT(*) cnt
     FROM customers GROUP BY currency ORDER BY won_val DESC"
);

/* Status distribution */
$statDist = array_column(db_all("SELECT status, COUNT(*) c FROM customers GROUP BY status"), 'c', 'status');

crm_layout_start('التقارير', 'reports', 'fas fa-chart-column');
?>
<div class="c-grid c-2" style="margin-bottom:1.4rem">
  <div class="c-card" style="margin:0">
    <div class="c-card-h"><h2><i class="fas fa-chart-line"></i> العملاء الجدد والصفقات الناجحة (12 شهر)</h2></div>
    <div class="c-card-b"><div style="height:300px;position:relative"><canvas id="chMonths"></canvas></div></div>
  </div>
  <div class="c-card" style="margin:0">
    <div class="c-card-h"><h2><i class="fas fa-chart-pie"></i> توزيع الحالات</h2></div>
    <div class="c-card-b"><div style="height:300px;position:relative"><canvas id="chStat"></canvas></div></div>
  </div>
</div>

<div class="c-card">
  <div class="c-card-h"><h2><i class="fas fa-tags"></i> الأداء حسب المصدر (معدل التحويل)</h2></div>
  <div class="c-card-b p0"><div class="c-scroll"><table class="c-tbl">
    <thead><tr><th>المصدر</th><th>إجمالي</th><th>ناجح</th><th>خسارة</th><th>معدل التحويل</th><th>قيمة الصفقات الناجحة</th></tr></thead>
    <tbody>
    <?php if ($srcStats): foreach ($srcStats as $r):
      $rate = $r['total'] > 0 ? round($r['won'] / $r['total'] * 100) : 0; ?>
      <tr>
        <td data-label="المصدر" style="font-weight:700"><?= e($sources[$r['source']] ?? $r['source']) ?></td>
        <td data-label="إجمالي"><?= number_format($r['total']) ?></td>
        <td data-label="ناجح" style="color:var(--success);font-weight:700"><?= number_format($r['won']) ?></td>
        <td data-label="خسارة" style="color:var(--danger)"><?= number_format($r['lost']) ?></td>
        <td data-label="معدل التحويل">
          <div class="c-funnel-bar-wrap" style="flex:1;max-width:140px;height:18px">
            <div class="c-funnel-bar" style="width:<?= max(4,$rate) ?>%;background:var(--navy-700);font-size:.68rem"><?= $rate ?>%</div>
          </div>
        </td>
        <td data-label="قيمة الناجحة" style="font-weight:700"><?= $r['won_val'] > 0 ? number_format($r['won_val']) : '—' ?></td>
      </tr>
    <?php endforeach; else: ?>
      <tr><td colspan="6" class="empty"><i class="fas fa-inbox"></i> لا بيانات كافية</td></tr>
    <?php endif; ?>
    </tbody>
  </table></div></div>
</div>

<div class="c-card">
  <div class="c-card-h"><h2><i class="fas fa-coins"></i> تحليل القيمة حسب العملة</h2></div>
  <div class="c-card-b p0"><div class="c-scroll"><table class="c-tbl">
    <thead><tr><th>العملة</th><th>عدد العملاء</th><th>قيمة الصفقات المفتوحة</th><th>قيمة الصفقات الناجحة (إيرادات)</th></tr></thead>
    <tbody>
    <?php if ($valByCur): foreach ($valByCur as $r): $sym = crm_currency_symbol($r['currency']); ?>
      <tr>
        <td data-label="العملة" style="font-weight:700"><?= e($r['currency']) ?> (<?= e($sym) ?>)</td>
        <td data-label="عدد العملاء"><?= number_format($r['cnt']) ?></td>
        <td data-label="صفقات مفتوحة" style="color:var(--warning);font-weight:700"><?= number_format($r['open_val']) ?> <?= e($sym) ?></td>
        <td data-label="إيرادات ناجحة" style="color:var(--success);font-weight:900"><?= number_format($r['won_val']) ?> <?= e($sym) ?></td>
      </tr>
    <?php endforeach; else: ?>
      <tr><td colspan="4" class="empty"><i class="fas fa-inbox"></i> لا بيانات</td></tr>
    <?php endif; ?>
    </tbody>
  </table></div></div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>
(function(){
  if (typeof Chart === 'undefined') return;
  Chart.defaults.font.family = "'Cairo', sans-serif"; Chart.defaults.color = '#6b7280';
  var m = document.getElementById('chMonths');
  if (m) new Chart(m,{type:'bar',data:{labels:<?= json_encode($mLbl) ?>,datasets:[
    {label:'عملاء جدد',data:<?= json_encode($mNew) ?>,backgroundColor:'#0E2150',borderRadius:5,maxBarThickness:22},
    {label:'صفقات ناجحة',data:<?= json_encode($mWon) ?>,backgroundColor:'#10b981',borderRadius:5,maxBarThickness:22}]},
    options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'top',labels:{usePointStyle:true,boxWidth:8}}},
      scales:{x:{grid:{display:false}},y:{beginAtZero:true,grid:{color:'#eef2f6'},ticks:{precision:0}}}}});
  var s = document.getElementById('chStat');
  if (s) new Chart(s,{type:'doughnut',data:{labels:<?= json_encode(array_map(fn($k)=>$statuses[$k]['label'],array_keys($statuses))) ?>,
    datasets:[{data:<?= json_encode(array_map(fn($k)=>(int)($statDist[$k]??0),array_keys($statuses))) ?>,
      backgroundColor:<?= json_encode(array_map(fn($k)=>$statuses[$k]['color'],array_keys($statuses))) ?>,borderWidth:0}]},
    options:{responsive:true,maintainAspectRatio:false,cutout:'55%',plugins:{legend:{position:'bottom',labels:{usePointStyle:true,boxWidth:8,padding:10}}}}});
})();
</script>
<?php crm_layout_end(); ?>
