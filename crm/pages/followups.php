<?php
/* CRM — follow-ups queue */
$statuses = crm_statuses();
$today = db_row("SELECT CURDATE() d")['d'] ?? date('Y-m-d');

$filter = $_GET['f'] ?? 'due';
$where = "next_followup IS NOT NULL AND status NOT IN('won','lost')";
if     ($filter === 'overdue')  $where .= " AND next_followup < CURDATE()";
elseif ($filter === 'today')    $where .= " AND next_followup = CURDATE()";
elseif ($filter === 'upcoming') $where .= " AND next_followup > CURDATE()";
elseif ($filter === 'due')      $where .= " AND next_followup <= CURDATE()";

$rows = db_all("SELECT * FROM customers WHERE $where ORDER BY next_followup ASC LIMIT 300");

$tabs = ['due' => 'مستحقة (اليوم + متأخرة)', 'overdue' => 'متأخرة', 'today' => 'اليوم', 'upcoming' => 'قادمة', 'all' => 'الكل'];

crm_layout_start('المتابعات', 'followups', 'fas fa-bell');
?>
<div class="c-toolbar">
  <div class="c-filters">
    <?php foreach ($tabs as $k => $l): ?>
      <a class="c-chip <?= $filter === $k ? 'active' : '' ?>" href="<?= crm_url('followups', ['f' => $k]) ?>"><?= e($l) ?></a>
    <?php endforeach; ?>
  </div>
</div>

<div class="c-card">
  <div class="c-card-h"><h2><i class="fas fa-calendar-check"></i> قائمة المتابعات (<?= count($rows) ?>)</h2></div>
  <div class="c-card-b p0"><div class="c-scroll"><table class="c-tbl">
    <thead><tr><th>الموعد</th><th>العميل</th><th>الحالة</th><th>الهاتف</th><th>القيمة</th><th></th></tr></thead>
    <tbody>
    <?php if ($rows): foreach ($rows as $r):
      $od = $r['next_followup'] < $today; $td = $r['next_followup'] === $today;
      $st = $statuses[$r['status']] ?? ['label'=>$r['status'],'color'=>'#6b7280'];
      $wa = preg_replace('/[^0-9]/', '', $r['whatsapp'] ?: $r['phone'] ?: ''); ?>
      <tr>
        <td data-label="الموعد" style="<?= $od ? 'color:var(--danger);font-weight:700' : ($td ? 'color:var(--warning);font-weight:700' : '') ?>">
          <span><i class="fas fa-<?= $od ? 'triangle-exclamation' : 'calendar' ?>"></i> <?= e($r['next_followup']) ?><?= $od ? ' · متأخرة' : ($td ? ' · اليوم' : '') ?></span>
        </td>
        <td data-label="العميل"><div><a href="<?= crm_url('customer',['id'=>$r['id']]) ?>" style="font-weight:700;color:var(--primary)"><?= e($r['name']) ?></a>
          <?php if ($r['company']): ?><div class="text-muted" style="font-size:.78rem"><?= e($r['company']) ?></div><?php endif; ?></div></td>
        <td data-label="الحالة"><span class="c-badge" style="background:<?= $st['color'] ?>"><?= e($st['label']) ?></span></td>
        <td data-label="الهاتف" style="font-size:.85rem"><?= e($r['phone'] ?: '—') ?></td>
        <td data-label="القيمة" style="font-weight:700"><?= $r['deal_value'] > 0 ? e(crm_money((float)$r['deal_value'], $r['currency'])) : '—' ?></td>
        <td class="c-actions">
          <?php if ($wa): ?><a class="c-btn c-btn-wa c-btn-sm" target="_blank" href="https://wa.me/<?= e($wa) ?>"><i class="fab fa-whatsapp"></i> واتساب</a><?php endif; ?>
          <a class="c-btn c-btn-outline c-btn-sm" href="<?= crm_url('customer',['id'=>$r['id']]) ?>"><i class="fas fa-eye"></i> عرض</a>
        </td>
      </tr>
    <?php endforeach; else: ?>
      <tr><td colspan="6" class="empty"><i class="fas fa-check-circle"></i> لا توجد متابعات في هذا التصنيف.</td></tr>
    <?php endif; ?>
    </tbody>
  </table></div></div>
</div>
<?php crm_layout_end(); ?>
