<?php
/* CRM — dashboard: KPIs, pipeline funnel, trends, follow-ups */

$statuses = crm_statuses();
$emp = current_emp();

$total    = (int)(db_row("SELECT COUNT(*) c FROM customers")['c'] ?? 0);
$won      = (int)(db_row("SELECT COUNT(*) c FROM customers WHERE status='won'")['c'] ?? 0);
$lost     = (int)(db_row("SELECT COUNT(*) c FROM customers WHERE status='lost'")['c'] ?? 0);
$open     = max(0, $total - $won - $lost);
$newMonth = (int)(db_row("SELECT COUNT(*) c FROM customers WHERE DATE_FORMAT(created_at,'%Y-%m')=DATE_FORMAT(CURDATE(),'%Y-%m')")['c'] ?? 0);
$conv     = $total > 0 ? round($won / $total * 100) : 0;
$dueCount = (int)(db_row("SELECT COUNT(*) c FROM customers WHERE next_followup IS NOT NULL AND next_followup <= CURDATE() AND status NOT IN('won','lost')")['c'] ?? 0);

/* Pipeline value grouped by currency */
$fmtCur = function (array $rows): string {
    $parts = [];
    foreach ($rows as $r) if ((float)$r['s'] > 0) $parts[] = crm_money((float)$r['s'], $r['currency']);
    return $parts ? implode(' · ', $parts) : '0';
};
$openVal = $fmtCur(db_all("SELECT currency, SUM(deal_value) s FROM customers WHERE status NOT IN('won','lost') GROUP BY currency"));
$wonVal  = $fmtCur(db_all("SELECT currency, SUM(deal_value) s FROM customers WHERE status='won' GROUP BY currency"));

/* Funnel */
$counts = db_all("SELECT status, COUNT(*) c FROM customers GROUP BY status");
$byStatus = array_column($counts, 'c', 'status');
$funnelMax = $counts ? max(array_column($counts, 'c')) : 1;

/* By source */
$sources = db_all("SELECT source, COUNT(*) c FROM customers GROUP BY source ORDER BY c DESC");

/* New customers — last 30 days */
$daily = db_all("SELECT DATE(created_at) d, COUNT(*) c FROM customers WHERE created_at >= CURDATE() - INTERVAL 29 DAY GROUP BY DATE(created_at)");
$dmap = array_column($daily, 'c', 'd');
$dLbl = $dVal = [];
$today = db_row("SELECT CURDATE() d")['d'] ?? date('Y-m-d');
$cur = new DateTime($today); $cur->modify('-29 days');
for ($i = 0; $i < 30; $i++) { $k = $cur->format('Y-m-d'); $dLbl[] = $cur->format('d/m'); $dVal[] = (int)($dmap[$k] ?? 0); $cur->modify('+1 day'); }

/* Follow-ups due */
$due = db_all("SELECT id, name, company, phone, whatsapp, status, next_followup
               FROM customers WHERE next_followup IS NOT NULL AND next_followup <= CURDATE()
               AND status NOT IN('won','lost') ORDER BY next_followup ASC LIMIT 12");

/* Recent activity */
$recent = db_all("SELECT a.type, a.content, a.new_status, a.created_at, c.id cid, c.name cname, e.name ename
                  FROM customer_activities a
                  JOIN customers c ON c.id = a.customer_id
                  LEFT JOIN employees e ON e.id = a.employee_id
                  ORDER BY a.id DESC LIMIT 10");
$actTypes = crm_activity_types();

/* Leaderboard */
$board = db_all("SELECT e.name,
                   (SELECT COUNT(*) FROM customer_activities a WHERE a.employee_id=e.id) acts,
                   (SELECT COUNT(*) FROM customers c WHERE c.created_by=e.id) custs
                 FROM employees e WHERE e.status='active' ORDER BY acts DESC LIMIT 8");

crm_layout_start('لوحة المعلومات', 'dashboard', 'fas fa-chart-pie');
?>
<div class="c-kpis">
  <div class="c-kpi" style="border-color:#0E2150">
    <div class="t"><span class="l">إجمالي العملاء</span><span class="ico" style="background:#0E2150"><i class="fas fa-users"></i></span></div>
    <div class="v"><?= number_format($total) ?></div><div class="s"><i class="fas fa-user-plus"></i> <?= number_format($newMonth) ?> هذا الشهر</div>
  </div>
  <div class="c-kpi" style="border-color:#FF7A1A">
    <div class="t"><span class="l">صفقات مفتوحة</span><span class="ico" style="background:#FF7A1A"><i class="fas fa-spinner"></i></span></div>
    <div class="v"><?= number_format($open) ?></div><div class="s"><i class="fas fa-coins"></i> <?= e($openVal) ?></div>
  </div>
  <div class="c-kpi" style="border-color:#10b981">
    <div class="t"><span class="l">عملاء ناجحون</span><span class="ico" style="background:#10b981"><i class="fas fa-trophy"></i></span></div>
    <div class="v"><?= number_format($won) ?></div><div class="s"><i class="fas fa-sack-dollar"></i> <?= e($wonVal) ?></div>
  </div>
  <div class="c-kpi" style="border-color:#16306e">
    <div class="t"><span class="l">معدل التحويل</span><span class="ico" style="background:#16306e"><i class="fas fa-percent"></i></span></div>
    <div class="v"><?= $conv ?>%</div><div class="s"><i class="fas fa-circle-xmark"></i> <?= number_format($lost) ?> خسارة</div>
  </div>
  <div class="c-kpi" style="border-color:#ef4444">
    <div class="t"><span class="l">متابعات مستحقة</span><span class="ico" style="background:#ef4444"><i class="fas fa-bell"></i></span></div>
    <div class="v"><?= number_format($dueCount) ?></div><div class="s"><a href="<?= crm_url('followups') ?>" style="color:var(--primary)">عرض القائمة ←</a></div>
  </div>
</div>

<div class="c-grid c-3" style="margin-bottom:1.4rem">
  <div class="c-card" style="margin:0">
    <div class="c-card-h"><h2><i class="fas fa-chart-area"></i> العملاء الجدد (آخر 30 يوم)</h2></div>
    <div class="c-card-b"><div style="height:280px;position:relative"><canvas id="chNew"></canvas></div></div>
  </div>
  <div class="c-card" style="margin:0">
    <div class="c-card-h"><h2><i class="fas fa-filter"></i> قمع المبيعات</h2></div>
    <div class="c-card-b">
      <div class="c-funnel">
        <?php foreach ($statuses as $k => $st): $cnt = (int)($byStatus[$k] ?? 0); ?>
          <div class="c-funnel-row">
            <span class="nm"><?= e($st['label']) ?></span>
            <div class="c-funnel-bar-wrap">
              <div class="c-funnel-bar" style="width:<?= $funnelMax ? max(6, round($cnt / $funnelMax * 100)) : 6 ?>%;background:<?= $st['color'] ?>"><?= $cnt ?></div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>

<div class="c-grid c-2" style="margin-bottom:1.4rem">
  <div class="c-card" style="margin:0">
    <div class="c-card-h"><h2><i class="fas fa-bell"></i> متابعات مستحقة اليوم / متأخرة</h2><a class="c-btn c-btn-outline c-btn-sm" href="<?= crm_url('followups') ?>">الكل</a></div>
    <div class="c-card-b p0"><div class="c-scroll"><table class="c-tbl">
      <thead><tr><th>العميل</th><th>الحالة</th><th>الموعد</th><th></th></tr></thead>
      <tbody>
      <?php if ($due): foreach ($due as $d):
        $od = $d['next_followup'] < $today; $st = $statuses[$d['status']] ?? ['label'=>$d['status'],'color'=>'#6b7280']; ?>
        <tr>
          <td data-label="العميل"><a href="<?= crm_url('customer',['id'=>$d['id']]) ?>" style="font-weight:700;color:var(--primary)"><?= e($d['name']) ?></a></td>
          <td data-label="الحالة"><span class="c-badge" style="background:<?= $st['color'] ?>"><?= e($st['label']) ?></span></td>
          <td data-label="الموعد" style="<?= $od ? 'color:var(--danger);font-weight:700' : '' ?>"><?= e($d['next_followup']) ?><?= $od ? ' (متأخرة)' : '' ?></td>
          <td class="c-actions"><a class="c-btn c-btn-outline c-btn-sm" href="<?= crm_url('customer',['id'=>$d['id']]) ?>"><i class="fas fa-arrow-left"></i> عرض</a></td>
        </tr>
      <?php endforeach; else: ?>
        <tr><td colspan="4" class="empty"><i class="fas fa-check-circle"></i> لا متابعات مستحقة. أحسنت!</td></tr>
      <?php endif; ?>
      </tbody>
    </table></div></div>
  </div>

  <div class="c-card" style="margin:0">
    <div class="c-card-h"><h2><i class="fas fa-tags"></i> مصادر العملاء</h2></div>
    <div class="c-card-b"><div style="height:250px;position:relative"><canvas id="chSrc"></canvas></div></div>
  </div>
</div>

<div class="c-grid c-2">
  <div class="c-card" style="margin:0">
    <div class="c-card-h"><h2><i class="fas fa-timeline"></i> آخر النشاطات</h2></div>
    <div class="c-card-b">
      <?php if ($recent): ?>
        <div class="c-timeline">
          <?php foreach ($recent as $a): $at = $actTypes[$a['type']] ?? ['label'=>$a['type'],'icon'=>'fa-circle','color'=>'#6b7280']; ?>
            <div class="c-tl-item">
              <div class="c-tl-head">
                <i class="fas <?= $at['icon'] ?>" style="color:<?= $at['color'] ?>"></i>
                <b><?= e($at['label']) ?></b> —
                <a href="<?= crm_url('customer',['id'=>$a['cid']]) ?>" style="color:var(--primary);font-weight:700"><?= e($a['cname']) ?></a>
                <span class="c-tl-time">· <?= e(date('m-d H:i', strtotime($a['created_at']))) ?> · <?= e($a['ename'] ?: 'النظام') ?></span>
              </div>
              <?php if ($a['type']==='status_change'): ?>
                <div class="c-tl-body">→ <span class="c-badge" style="background:<?= crm_status_color($a['new_status']??'') ?>"><?= e(crm_status_label($a['new_status']??'')) ?></span></div>
              <?php elseif (!empty($a['content'])): ?>
                <div class="c-tl-body"><?= e(mb_strimwidth($a['content'],0,120,'…','UTF-8')) ?></div>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?><p class="text-muted text-c" style="padding:1.5rem"><i class="fas fa-inbox"></i> لا نشاطات بعد.</p><?php endif; ?>
    </div>
  </div>

  <div class="c-card" style="margin:0">
    <div class="c-card-h"><h2><i class="fas fa-ranking-star"></i> أداء الموظفين</h2></div>
    <div class="c-card-b p0"><div class="c-scroll"><table class="c-tbl">
      <thead><tr><th>الموظف</th><th>النشاطات</th><th>عملاء أضافهم</th></tr></thead>
      <tbody>
      <?php if ($board): foreach ($board as $b): ?>
        <tr><td data-label="الموظف" style="font-weight:700"><?= e($b['name']) ?></td><td data-label="النشاطات"><?= number_format($b['acts']) ?></td><td data-label="عملاء أضافهم"><?= number_format($b['custs']) ?></td></tr>
      <?php endforeach; else: ?>
        <tr><td colspan="3" class="empty"><i class="fas fa-user-slash"></i> لا موظفين نشطين</td></tr>
      <?php endif; ?>
      </tbody>
    </table></div></div>
  </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>
(function(){
  if (typeof Chart === 'undefined') return;
  Chart.defaults.font.family = "'Cairo', sans-serif"; Chart.defaults.color = '#6b7280';
  var pal = ['#0E2150','#16306e','#24437f','#6f7fa6','#FF7A1A','#ff924a','#ffb37a','#10b981'];

  var n = document.getElementById('chNew');
  if (n) {
    var g = n.getContext('2d').createLinearGradient(0,0,0,280);
    g.addColorStop(0,'rgba(255,122,26,.28)'); g.addColorStop(1,'rgba(255,122,26,0)');
    new Chart(n,{type:'line',data:{labels:<?= json_encode($dLbl) ?>,datasets:[{label:'عملاء جدد',
      data:<?= json_encode($dVal) ?>,borderColor:'#FF7A1A',backgroundColor:g,fill:true,tension:.35,borderWidth:2.5,pointRadius:0,pointHoverRadius:5}]},
      options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},
        scales:{x:{grid:{display:false},ticks:{maxTicksLimit:10}},y:{beginAtZero:true,grid:{color:'#eef2f6'},ticks:{precision:0}}}}});
  }
  var s = document.getElementById('chSrc');
  if (s) new Chart(s,{type:'doughnut',data:{labels:<?= json_encode(array_map(fn($r)=>crm_source_label($r['source']),$sources)) ?>,
    datasets:[{data:<?= json_encode(array_map(fn($r)=>(int)$r['c'],$sources)) ?>,backgroundColor:pal,borderWidth:0}]},
    options:{responsive:true,maintainAspectRatio:false,cutout:'58%',plugins:{legend:{position:'bottom',labels:{usePointStyle:true,boxWidth:8,padding:12}}}}});
})();
</script>
<?php crm_layout_end(); ?>
