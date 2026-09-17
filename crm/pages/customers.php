<?php
/* CRM — customers list + create/edit/delete */

$statuses  = crm_statuses();
$sources   = crm_sources();
$currencies = crm_currencies();

/* ── Handle POST ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        try { db_exec("DELETE FROM customers WHERE id = ?", [$id]); crm_redirect('customers', 'تم حذف العميل.', 'success'); }
        catch (\Throwable $ex) { crm_redirect('customers', 'تعذّر الحذف.', 'danger'); }
    }

    if ($action === 'save') {
        $id       = (int)($_POST['id'] ?? 0);
        $name     = trim($_POST['name'] ?? '');
        $status   = $_POST['status'] ?? 'new';
        $source   = $_POST['source'] ?? 'other';
        if (!crm_is_status($status)) $status = 'new';
        if (!isset($sources[$source])) $source = 'other';
        $currency = in_array($_POST['currency'] ?? 'SAR', array_keys($currencies), true) ? $_POST['currency'] : 'SAR';
        $val      = (float)str_replace([',', ' '], '', $_POST['deal_value'] ?? '0');
        $followup = trim($_POST['next_followup'] ?? '');
        $followup = ($followup !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $followup)) ? $followup : null;

        $data = [
            $name,
            trim($_POST['company'] ?? '') ?: null,
            trim($_POST['phone'] ?? '') ?: null,
            trim($_POST['whatsapp'] ?? '') ?: null,
            trim($_POST['email'] ?? '') ?: null,
            trim($_POST['city'] ?? '') ?: null,
            trim($_POST['country'] ?? '') ?: null,
            $source, $status, $val, $currency,
            trim($_POST['notes'] ?? '') ?: null,
            $followup,
        ];

        if ($name === '') crm_redirect('customers', 'اسم العميل مطلوب.', 'danger');

        try {
            if ($id > 0) {
                $old = db_row("SELECT status FROM customers WHERE id = ?", [$id]);
                $sql = "UPDATE customers SET name=?, company=?, phone=?, whatsapp=?, email=?, city=?, country=?,
                        source=?, status=?, deal_value=?, currency=?, notes=?, next_followup=? WHERE id=?";
                $s = getDB()->prepare($sql); $s->execute(array_merge($data, [$id]));
                if ($old && $old['status'] !== $status) {
                    crm_log($id, 'status_change', null, $old['status'], $status);
                }
                crm_redirect('customer', 'تم تحديث بيانات العميل.', 'success', ['id' => $id]);
            } else {
                $emp = current_emp();
                $sql = "INSERT INTO customers (name,company,phone,whatsapp,email,city,country,source,status,deal_value,currency,notes,next_followup,created_by)
                        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
                $s = getDB()->prepare($sql); $s->execute(array_merge($data, [$emp['id'] ?: null]));
                $newId = (int)getDB()->lastInsertId();
                crm_log($newId, 'note', 'تم إضافة العميل إلى النظام.');
                crm_redirect('customer', 'تمت إضافة العميل بنجاح.', 'success', ['id' => $newId]);
            }
        } catch (\Throwable $ex) {
            crm_redirect('customers', 'خطأ في الحفظ: ' . $ex->getMessage(), 'danger');
        }
    }
}

/* ── Filters ── */
$q       = trim($_GET['q'] ?? '');
$fStatus = $_GET['status'] ?? '';
$fSource = $_GET['source'] ?? '';

$where = []; $params = [];
if ($q !== '') {
    $where[] = '(name LIKE ? OR company LIKE ? OR phone LIKE ? OR whatsapp LIKE ? OR email LIKE ?)';
    $like = '%' . $q . '%'; array_push($params, $like, $like, $like, $like, $like);
}
if ($fStatus !== '' && crm_is_status($fStatus)) { $where[] = 'status = ?'; $params[] = $fStatus; }
if ($fSource !== '' && isset($sources[$fSource])) { $where[] = 'source = ?'; $params[] = $fSource; }
$W = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$rows  = db_all("SELECT * FROM customers $W ORDER BY updated_at DESC, id DESC LIMIT 500", $params);
$total = db_row("SELECT COUNT(*) c FROM customers $W", $params)['c'] ?? 0;

crm_layout_start('العملاء', 'customers', 'fas fa-users');
?>
<div class="c-toolbar">
  <form class="c-filters" method="GET" action="<?= crm_prefix() ?>/index.php">
    <input type="hidden" name="page" value="customers">
    <input type="text" name="q" value="<?= e($q) ?>" placeholder="بحث بالاسم/الهاتف/الشركة...">
    <select name="status" onchange="this.form.submit()">
      <option value="">كل الحالات</option>
      <?php foreach ($statuses as $k => $st): ?>
        <option value="<?= $k ?>" <?= $fStatus === $k ? 'selected' : '' ?>><?= e($st['label']) ?></option>
      <?php endforeach; ?>
    </select>
    <select name="source" onchange="this.form.submit()">
      <option value="">كل المصادر</option>
      <?php foreach ($sources as $k => $l): ?>
        <option value="<?= $k ?>" <?= $fSource === $k ? 'selected' : '' ?>><?= e($l) ?></option>
      <?php endforeach; ?>
    </select>
    <button class="c-btn c-btn-outline c-btn-sm"><i class="fas fa-search"></i> بحث</button>
    <?php if ($q || $fStatus || $fSource): ?>
      <a class="c-btn c-btn-outline c-btn-sm" href="<?= crm_url('customers') ?>">إلغاء</a>
    <?php endif; ?>
  </form>
  <button class="c-btn c-btn-primary" onclick="openAdd()"><i class="fas fa-plus"></i> عميل جديد</button>
</div>

<div class="c-card">
  <div class="c-card-h"><h2><i class="fas fa-address-book"></i> قائمة العملاء (<?= (int)$total ?>)</h2></div>
  <div class="c-card-b p0"><div class="c-scroll">
    <table class="c-tbl">
      <thead><tr>
        <th>العميل</th><th>التواصل</th><th>المصدر</th><th>الحالة</th>
        <th>القيمة المتوقعة</th><th>المتابعة القادمة</th><th></th>
      </tr></thead>
      <tbody>
      <?php if ($rows): foreach ($rows as $r):
        $st = $statuses[$r['status']] ?? ['label' => $r['status'], 'color' => '#6b7280'];
        $overdue = $r['next_followup'] && $r['next_followup'] < date('Y-m-d') && !in_array($r['status'], ['won','lost']);
      ?>
        <tr>
          <td data-label="العميل">
            <div>
              <a href="<?= crm_url('customer', ['id' => $r['id']]) ?>" style="font-weight:700;color:var(--primary)"><?= e($r['name']) ?></a>
              <?php if ($r['company']): ?><div class="text-muted" style="font-size:.78rem"><?= e($r['company']) ?></div><?php endif; ?>
            </div>
          </td>
          <td data-label="التواصل" style="font-size:.82rem">
            <div>
              <?php if ($r['phone']): ?><div><i class="fas fa-phone text-muted"></i> <?= e($r['phone']) ?></div><?php endif; ?>
              <?php if ($r['city'] || $r['country']): ?><div class="text-muted"><i class="fas fa-location-dot"></i> <?= e(trim(($r['city'] ?? '') . ' ' . ($r['country'] ?? ''))) ?></div><?php endif; ?>
              <?php if (!$r['phone'] && !$r['city'] && !$r['country']): ?><span class="text-muted">—</span><?php endif; ?>
            </div>
          </td>
          <td data-label="المصدر"><span class="c-badge-soft"><?= e($sources[$r['source']] ?? $r['source']) ?></span></td>
          <td data-label="الحالة"><span class="c-badge" style="background:<?= $st['color'] ?>"><?= e($st['label']) ?></span></td>
          <td data-label="القيمة" style="font-weight:700"><?= $r['deal_value'] > 0 ? e(crm_money((float)$r['deal_value'], $r['currency'])) : '<span class="text-muted">—</span>' ?></td>
          <td data-label="المتابعة" style="font-size:.82rem">
            <?php if ($r['next_followup']): ?>
              <span style="<?= $overdue ? 'color:var(--danger);font-weight:700' : '' ?>">
                <i class="fas fa-<?= $overdue ? 'triangle-exclamation' : 'calendar' ?>"></i> <?= e($r['next_followup']) ?>
              </span>
            <?php else: ?><span class="text-muted">—</span><?php endif; ?>
          </td>
          <td class="c-actions">
            <a class="c-btn c-btn-outline c-btn-sm" href="<?= crm_url('customer', ['id' => $r['id']]) ?>"><i class="fas fa-eye"></i> عرض</a>
            <button class="c-btn c-btn-outline c-btn-sm" onclick='openEdit(<?= json_encode($r, JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'><i class="fas fa-pen"></i> تعديل</button>
          </td>
        </tr>
      <?php endforeach; else: ?>
        <tr><td colspan="7" class="empty"><i class="fas fa-user-plus"></i> لا يوجد عملاء بعد. ابدأ بإضافة عميل جديد.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div></div>
</div>

<!-- Add/Edit modal -->
<div class="c-modal" id="custModal">
  <div class="c-modal-box">
    <div class="c-modal-h"><h3 id="mTitle">عميل جديد</h3><button class="x" onclick="closeModal()">&times;</button></div>
    <div class="c-modal-b">
      <form method="POST" action="<?= crm_url('customers') ?>">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" id="f_id" value="">
        <div class="c-form-grid">
          <div class="c-fg"><label>اسم العميل <span class="req">*</span></label><input name="name" id="f_name" required></div>
          <div class="c-fg"><label>الشركة / الجهة</label><input name="company" id="f_company"></div>
          <div class="c-fg"><label>الهاتف</label><input name="phone" id="f_phone"></div>
          <div class="c-fg"><label>واتساب</label><input name="whatsapp" id="f_whatsapp"></div>
          <div class="c-fg"><label>البريد الإلكتروني</label><input name="email" id="f_email" type="email"></div>
          <div class="c-fg"><label>المدينة</label><input name="city" id="f_city"></div>
          <div class="c-fg"><label>الدولة</label><input name="country" id="f_country"></div>
          <div class="c-fg"><label>المصدر</label>
            <select name="source" id="f_source">
              <?php foreach ($sources as $k => $l): ?><option value="<?= $k ?>"><?= e($l) ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="c-fg"><label>الحالة</label>
            <select name="status" id="f_status">
              <?php foreach ($statuses as $k => $st): ?><option value="<?= $k ?>"><?= e($st['label']) ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="c-fg"><label>المتابعة القادمة</label><input type="date" name="next_followup" id="f_followup"></div>
          <div class="c-fg"><label>القيمة المتوقعة</label>
            <div class="c-inline">
              <input name="deal_value" id="f_value" type="number" step="0.01" min="0" placeholder="0" style="flex:2">
              <select name="currency" id="f_currency" style="flex:1">
                <?php foreach ($currencies as $k => $sym): ?><option value="<?= $k ?>"><?= e($k) ?> <?= e($sym) ?></option><?php endforeach; ?>
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
function openModal(){ M.classList.add('open'); }
function closeModal(){ M.classList.remove('open'); }
M.addEventListener('click', function(e){ if(e.target===M) closeModal(); });
function setV(id,v){ document.getElementById(id).value = (v==null?'':v); }
function openAdd(){
  document.getElementById('mTitle').textContent = 'عميل جديد';
  ['f_id','f_name','f_company','f_phone','f_whatsapp','f_email','f_city','f_country','f_value','f_followup','f_notes'].forEach(function(i){setV(i,'');});
  setV('f_source','other'); setV('f_status','new'); setV('f_currency','SAR');
  openModal();
}
function openEdit(d){
  document.getElementById('mTitle').textContent = 'تعديل: ' + d.name;
  setV('f_id',d.id); setV('f_name',d.name); setV('f_company',d.company); setV('f_phone',d.phone);
  setV('f_whatsapp',d.whatsapp); setV('f_email',d.email); setV('f_city',d.city); setV('f_country',d.country);
  setV('f_source',d.source||'other'); setV('f_status',d.status||'new'); setV('f_value',d.deal_value);
  setV('f_currency',d.currency||'SAR'); setV('f_followup',d.next_followup); setV('f_notes',d.notes);
  openModal();
}
</script>
<?php crm_layout_end(); ?>
