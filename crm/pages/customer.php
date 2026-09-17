<?php
/* CRM — single customer profile, timeline & quick actions */

$statuses   = crm_statuses();
$actTypes   = crm_activity_types();
$id = (int)($_GET['id'] ?? 0);

/* ── POST actions ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cid = (int)($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        try { db_exec("DELETE FROM customers WHERE id = ?", [$cid]); crm_redirect('customers', 'تم حذف العميل.', 'success'); }
        catch (\Throwable $ex) { crm_redirect('customer', 'تعذّر الحذف.', 'danger', ['id' => $cid]); }
    }

    if ($action === 'status') {
        $new = $_POST['status'] ?? '';
        if (crm_is_status($new)) {
            $old = db_row("SELECT status FROM customers WHERE id = ?", [$cid])['status'] ?? null;
            if ($old !== $new) {
                db_exec("UPDATE customers SET status = ? WHERE id = ?", [$new, $cid]);
                crm_log($cid, 'status_change', null, $old, $new);
            }
        }
        crm_redirect('customer', 'تم تحديث الحالة.', 'success', ['id' => $cid]);
    }

    if ($action === 'activity') {
        $type    = $_POST['type'] ?? 'note';
        $content = trim($_POST['content'] ?? '');
        if (!isset($actTypes[$type]) || $type === 'status_change') $type = 'note';
        if ($content !== '') {
            crm_log($cid, $type, $content);
            db_exec("UPDATE customers SET last_contact = NOW() WHERE id = ?", [$cid]);
        }
        crm_redirect('customer', 'تمت إضافة النشاط.', 'success', ['id' => $cid]);
    }

    if ($action === 'followup') {
        $f = trim($_POST['next_followup'] ?? '');
        $f = ($f !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $f)) ? $f : null;
        db_exec("UPDATE customers SET next_followup = ? WHERE id = ?", [$f, $cid]);
        crm_redirect('customer', $f ? 'تم تحديد موعد المتابعة.' : 'تم مسح موعد المتابعة.', 'success', ['id' => $cid]);
    }
}

$c = db_row("SELECT * FROM customers WHERE id = ?", [$id]);
if (!$c) { crm_redirect('customers', 'العميل غير موجود.', 'danger'); }

$acts = db_all(
    "SELECT a.*, e.name AS emp_name FROM customer_activities a
     LEFT JOIN employees e ON e.id = a.employee_id
     WHERE a.customer_id = ? ORDER BY a.id DESC", [$id]
);
$st = $statuses[$c['status']] ?? ['label' => $c['status'], 'color' => '#6b7280'];
$initial = mb_substr(trim($c['name']), 0, 1, 'UTF-8');
$overdue = $c['next_followup'] && $c['next_followup'] < date('Y-m-d') && !in_array($c['status'], ['won','lost']);
$waNum = preg_replace('/[^0-9]/', '', $c['whatsapp'] ?: $c['phone'] ?: '');

crm_layout_start('العميل: ' . $c['name'], 'customers', 'fas fa-user');
?>
<div class="flex-wrap" style="justify-content:space-between;margin-bottom:1rem">
  <a class="c-btn c-btn-outline c-btn-sm" href="<?= crm_url('customers') ?>"><i class="fas fa-arrow-right"></i> العودة للقائمة</a>
  <div class="flex-wrap">
    <?php if ($waNum): ?>
      <a class="c-btn c-btn-wa c-btn-sm" target="_blank" href="https://wa.me/<?= e($waNum) ?>"><i class="fab fa-whatsapp"></i> واتساب</a>
    <?php endif; ?>
    <?php if ($c['phone']): ?>
      <a class="c-btn c-btn-outline c-btn-sm" href="tel:<?= e($c['phone']) ?>"><i class="fas fa-phone"></i> اتصال</a>
    <?php endif; ?>
    <form method="POST" action="<?= crm_url('customer', ['id' => $id]) ?>" onsubmit="return confirm('حذف هذا العميل وكل سجل نشاطه نهائياً؟')" style="display:inline">
      <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $id ?>">
      <button class="c-btn c-btn-danger c-btn-sm"><i class="fas fa-trash"></i> حذف</button>
    </form>
  </div>
</div>

<div class="c-grid c-3">
  <!-- Left: timeline + add activity -->
  <div>
    <div class="c-card">
      <div class="c-card-h"><h2><i class="fas fa-plus-circle"></i> تسجيل نشاط / تفاعل</h2></div>
      <div class="c-card-b">
        <form method="POST" action="<?= crm_url('customer', ['id' => $id]) ?>">
          <input type="hidden" name="action" value="activity"><input type="hidden" name="id" value="<?= $id ?>">
          <div class="c-fg">
            <div class="flex-wrap" style="gap:.4rem">
              <?php foreach ($actTypes as $tk => $tv): if ($tk === 'status_change') continue; ?>
                <label class="c-chip" style="cursor:pointer">
                  <input type="radio" name="type" value="<?= $tk ?>" <?= $tk === 'note' ? 'checked' : '' ?> style="display:none" onchange="chipSel(this)">
                  <i class="fas <?= $tv['icon'] ?>"></i> <?= e($tv['label']) ?>
                </label>
              <?php endforeach; ?>
            </div>
          </div>
          <div class="c-fg mb-0"><textarea name="content" placeholder="اكتب تفاصيل المكالمة أو الاجتماع أو ملاحظة..." required></textarea></div>
          <button class="c-btn c-btn-primary mt-1"><i class="fas fa-paper-plane"></i> إضافة للسجل</button>
        </form>
      </div>
    </div>

    <div class="c-card">
      <div class="c-card-h"><h2><i class="fas fa-clock-rotate-left"></i> سجل النشاطات (<?= count($acts) ?>)</h2></div>
      <div class="c-card-b">
        <?php if ($acts): ?>
          <div class="c-timeline">
            <?php foreach ($acts as $a):
              $at = $actTypes[$a['type']] ?? ['label' => $a['type'], 'icon' => 'fa-circle', 'color' => '#6b7280']; ?>
              <div class="c-tl-item" style="--c:<?= $at['color'] ?>">
                <div class="c-tl-head">
                  <i class="fas <?= $at['icon'] ?>" style="color:<?= $at['color'] ?>"></i>
                  <b><?= e($at['label']) ?></b>
                  <?php if ($a['type'] === 'status_change'): ?>
                    <span>: <span class="c-badge-soft"><?= e(crm_status_label($a['old_status'] ?? '')) ?></span>
                    <i class="fas fa-arrow-left" style="font-size:.7rem"></i>
                    <span class="c-badge" style="background:<?= crm_status_color($a['new_status'] ?? '') ?>"><?= e(crm_status_label($a['new_status'] ?? '')) ?></span></span>
                  <?php endif; ?>
                  <span class="c-tl-time">· <?= e(date('Y-m-d H:i', strtotime($a['created_at']))) ?> · <?= e($a['emp_name'] ?: 'النظام') ?></span>
                </div>
                <?php if (!empty($a['content'])): ?><div class="c-tl-body"><?= e($a['content']) ?></div><?php endif; ?>
              </div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <p class="text-muted text-c" style="padding:1.5rem"><i class="fas fa-inbox"></i> لا توجد نشاطات بعد.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Right: profile + status + followup -->
  <div class="c-prof-order">
    <div class="c-card">
      <div class="c-card-b">
        <div class="c-prof-head">
          <div class="c-avatar"><?= e($initial) ?></div>
          <div>
            <div style="font-size:1.15rem;font-weight:900"><?= e($c['name']) ?></div>
            <?php if ($c['company']): ?><div class="text-muted"><?= e($c['company']) ?></div><?php endif; ?>
            <div class="mt-1"><span class="c-badge" style="background:<?= $st['color'] ?>"><?= e($st['label']) ?></span></div>
          </div>
        </div>
        <ul class="c-meta-list mt-2">
          <li><i class="fas fa-phone"></i><span class="k">الهاتف</span><span><?= e($c['phone'] ?: '—') ?></span></li>
          <li><i class="fab fa-whatsapp"></i><span class="k">واتساب</span><span><?= e($c['whatsapp'] ?: '—') ?></span></li>
          <li><i class="fas fa-envelope"></i><span class="k">البريد</span><span><?= e($c['email'] ?: '—') ?></span></li>
          <li><i class="fas fa-location-dot"></i><span class="k">الموقع</span><span><?= e(trim(($c['city'] ?? '') . ' ' . ($c['country'] ?? '')) ?: '—') ?></span></li>
          <li><i class="fas fa-tag"></i><span class="k">المصدر</span><span><?= e(crm_source_label($c['source'])) ?></span></li>
          <li><i class="fas fa-coins"></i><span class="k">القيمة المتوقعة</span><span style="font-weight:700"><?= $c['deal_value'] > 0 ? e(crm_money((float)$c['deal_value'], $c['currency'])) : '—' ?></span></li>
          <li><i class="fas fa-calendar-plus"></i><span class="k">تاريخ الإضافة</span><span><?= e(date('Y-m-d', strtotime($c['created_at']))) ?></span></li>
          <li><i class="fas fa-clock"></i><span class="k">آخر تواصل</span><span><?= $c['last_contact'] ? e(date('Y-m-d H:i', strtotime($c['last_contact']))) : '—' ?></span></li>
        </ul>
        <?php if ($c['notes']): ?>
          <div class="mt-2" style="background:#f7f9fc;border-radius:8px;padding:.8rem;font-size:.85rem;white-space:pre-wrap"><b>ملاحظات:</b><br><?= e($c['notes']) ?></div>
        <?php endif; ?>
        <button class="c-btn c-btn-outline c-btn-block mt-2" onclick='openEdit(<?= json_encode($c, JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'><i class="fas fa-pen"></i> تعديل البيانات</button>
      </div>
    </div>

    <div class="c-card">
      <div class="c-card-h"><h2><i class="fas fa-diagram-project"></i> تغيير المرحلة</h2></div>
      <div class="c-card-b">
        <form method="POST" action="<?= crm_url('customer', ['id' => $id]) ?>">
          <input type="hidden" name="action" value="status"><input type="hidden" name="id" value="<?= $id ?>">
          <select name="status" class="c-fg" style="width:100%;padding:.62rem .9rem;border:1.5px solid var(--border);border-radius:8px;margin-bottom:.8rem">
            <?php foreach ($statuses as $k => $s2): ?>
              <option value="<?= $k ?>" <?= $c['status'] === $k ? 'selected' : '' ?>><?= e($s2['label']) ?></option>
            <?php endforeach; ?>
          </select>
          <button class="c-btn c-btn-primary c-btn-block"><i class="fas fa-check"></i> تحديث المرحلة</button>
        </form>
      </div>
    </div>

    <div class="c-card">
      <div class="c-card-h"><h2><i class="fas fa-bell"></i> موعد المتابعة</h2></div>
      <div class="c-card-b">
        <?php if ($overdue): ?><div class="c-alert c-alert-danger" style="margin-bottom:.8rem"><i class="fas fa-triangle-exclamation"></i> متأخرة!</div><?php endif; ?>
        <form method="POST" action="<?= crm_url('customer', ['id' => $id]) ?>">
          <input type="hidden" name="action" value="followup"><input type="hidden" name="id" value="<?= $id ?>">
          <input type="date" name="next_followup" value="<?= e($c['next_followup'] ?? '') ?>" style="width:100%;padding:.62rem .9rem;border:1.5px solid var(--border);border-radius:8px;margin-bottom:.8rem">
          <button class="c-btn c-btn-warning c-btn-block"><i class="fas fa-calendar-check"></i> حفظ الموعد</button>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Edit modal (reuses same form as customers) -->
<div class="c-modal" id="custModal">
  <div class="c-modal-box">
    <div class="c-modal-h"><h3>تعديل بيانات العميل</h3><button class="x" onclick="closeModal()">&times;</button></div>
    <div class="c-modal-b">
      <form method="POST" action="<?= crm_url('customers') ?>">
        <input type="hidden" name="action" value="save"><input type="hidden" name="id" id="f_id">
        <div class="c-form-grid">
          <div class="c-fg"><label>اسم العميل <span class="req">*</span></label><input name="name" id="f_name" required></div>
          <div class="c-fg"><label>الشركة / الجهة</label><input name="company" id="f_company"></div>
          <div class="c-fg"><label>الهاتف</label><input name="phone" id="f_phone"></div>
          <div class="c-fg"><label>واتساب</label><input name="whatsapp" id="f_whatsapp"></div>
          <div class="c-fg"><label>البريد الإلكتروني</label><input name="email" id="f_email" type="email"></div>
          <div class="c-fg"><label>المدينة</label><input name="city" id="f_city"></div>
          <div class="c-fg"><label>الدولة</label><input name="country" id="f_country"></div>
          <div class="c-fg"><label>المصدر</label><select name="source" id="f_source">
            <?php foreach (crm_sources() as $k => $l): ?><option value="<?= $k ?>"><?= e($l) ?></option><?php endforeach; ?></select></div>
          <div class="c-fg"><label>الحالة</label><select name="status" id="f_status">
            <?php foreach ($statuses as $k => $s2): ?><option value="<?= $k ?>"><?= e($s2['label']) ?></option><?php endforeach; ?></select></div>
          <div class="c-fg"><label>المتابعة القادمة</label><input type="date" name="next_followup" id="f_followup"></div>
          <div class="c-fg"><label>القيمة المتوقعة</label>
            <div class="c-inline">
              <input name="deal_value" id="f_value" type="number" step="0.01" min="0" style="flex:2">
              <select name="currency" id="f_currency" style="flex:1">
                <?php foreach (crm_currencies() as $k => $sym): ?><option value="<?= $k ?>"><?= e($k) ?> <?= e($sym) ?></option><?php endforeach; ?>
              </select>
            </div>
          </div>
        </div>
        <div class="c-fg"><label>ملاحظات</label><textarea name="notes" id="f_notes"></textarea></div>
        <button class="c-btn c-btn-primary c-btn-block"><i class="fas fa-save"></i> حفظ</button>
      </form>
    </div>
  </div>
</div>

<script>
var M = document.getElementById('custModal');
function closeModal(){ M.classList.remove('open'); }
M.addEventListener('click', function(e){ if(e.target===M) closeModal(); });
function setV(id,v){ var el=document.getElementById(id); if(el) el.value=(v==null?'':v); }
function openEdit(d){
  setV('f_id',d.id); setV('f_name',d.name); setV('f_company',d.company); setV('f_phone',d.phone);
  setV('f_whatsapp',d.whatsapp); setV('f_email',d.email); setV('f_city',d.city); setV('f_country',d.country);
  setV('f_source',d.source||'other'); setV('f_status',d.status||'new'); setV('f_value',d.deal_value);
  setV('f_currency',d.currency||'SAR'); setV('f_followup',d.next_followup); setV('f_notes',d.notes);
  M.classList.add('open');
}
function chipSel(el){
  document.querySelectorAll('.c-chip').forEach(function(c){ c.classList.remove('active'); });
  el.closest('.c-chip').classList.add('active');
}
document.querySelector('.c-chip input:checked')?.closest('.c-chip').classList.add('active');
</script>
<?php crm_layout_end(); ?>
