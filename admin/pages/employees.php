<?php
/* Admin — manage CRM employee accounts (add / edit / activate / delete) */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'edit') {
        $id       = (int)($_POST['id'] ?? 0);
        $name     = trim($_POST['name'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $email    = trim($_POST['email'] ?? '') ?: null;
        $phone    = trim($_POST['phone'] ?? '') ?: null;
        $password = $_POST['password'] ?? '';

        if ($name === '' || $username === '') {
            redirect_admin('employees', 'الاسم واسم المستخدم مطلوبان.', 'danger');
        }
        if (!preg_match('/^[A-Za-z0-9_.-]{3,50}$/', $username)) {
            redirect_admin('employees', 'اسم المستخدم يجب أن يكون 3-50 حرفاً إنجليزياً/أرقاماً بدون مسافات.', 'danger');
        }

        // Uniqueness
        $clash = db_row("SELECT id FROM employees WHERE username = ? AND id <> ?", [$username, $id]);
        if ($clash) redirect_admin('employees', 'اسم المستخدم مستخدم بالفعل.', 'danger');

        try {
            if ($action === 'add') {
                if (strlen($password) < 8) redirect_admin('employees', 'كلمة المرور يجب أن تكون 8 أحرف على الأقل.', 'danger');
                $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                db_exec("INSERT INTO employees (name, username, email, phone, password, status, created_by)
                         VALUES (?,?,?,?,?, 'active', ?)",
                        [$name, $username, $email, $phone, $hash, $_SESSION['admin_id'] ?? null]);
                redirect_admin('employees', 'تم إنشاء حساب الموظف بنجاح.', 'success');
            } else {
                if ($password !== '') {
                    if (strlen($password) < 8) redirect_admin('employees', 'كلمة المرور الجديدة يجب أن تكون 8 أحرف على الأقل.', 'danger');
                    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                    db_exec("UPDATE employees SET name=?, username=?, email=?, phone=?, password=? WHERE id=?",
                            [$name, $username, $email, $phone, $hash, $id]);
                } else {
                    db_exec("UPDATE employees SET name=?, username=?, email=?, phone=? WHERE id=?",
                            [$name, $username, $email, $phone, $id]);
                }
                redirect_admin('employees', 'تم تحديث بيانات الموظف.', 'success');
            }
        } catch (\Throwable $ex) {
            redirect_admin('employees', 'خطأ: ' . $ex->getMessage(), 'danger');
        }
    }

    if ($action === 'toggle') {
        $id = (int)($_POST['id'] ?? 0);
        db_exec("UPDATE employees SET status = IF(status='active','inactive','active') WHERE id = ?", [$id]);
        redirect_admin('employees', 'تم تغيير حالة الحساب.', 'success');
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        db_exec("DELETE FROM employees WHERE id = ?", [$id]);
        redirect_admin('employees', 'تم حذف الموظف.', 'success');
    }
}

$emps = db_all("SELECT * FROM employees ORDER BY status='inactive', name");
$activeCount = 0;
foreach ($emps as $e) if ($e['status'] === 'active') $activeCount++;

// CRM portal URL (sibling of /admin)
$crmUrl = site_prefix() . '/crm/';

layout_start('موظفو CRM', 'employees');
?>
<style>
  .emp-modal{position:fixed;inset:0;background:rgba(0,0,0,.5);display:none;align-items:flex-start;justify-content:center;z-index:200;padding:2rem 1rem;overflow:auto}
  .emp-modal.open{display:flex}
  .emp-modal-box{background:#fff;border-radius:12px;max-width:520px;width:100%;box-shadow:0 20px 60px rgba(0,0,0,.3)}
  .emp-modal-h{padding:1rem 1.4rem;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center}
  .emp-modal-h h3{font-size:1.05rem;color:var(--primary)}
  .emp-modal-h .x{background:none;border:none;font-size:1.4rem;cursor:pointer;color:var(--muted)}
  .emp-modal-b{padding:1.4rem}
  .emp-grid{display:grid;grid-template-columns:1fr 1fr;gap:0 1rem}
  @media(max-width:560px){.emp-grid{grid-template-columns:1fr}}
</style>

<div class="dash-stats" style="grid-template-columns:repeat(auto-fill,minmax(200px,1fr))">
  <div class="dash-card"><div class="dc-icon" style="background:#3F4D60"><i class="fas fa-users-gear"></i></div>
    <div class="dc-val"><?= count($emps) ?></div><div class="dc-lbl">إجمالي الموظفين</div></div>
  <div class="dash-card"><div class="dc-icon" style="background:#10b981"><i class="fas fa-user-check"></i></div>
    <div class="dc-val"><?= $activeCount ?></div><div class="dc-lbl">حسابات نشطة</div></div>
  <div class="dash-card"><div class="dc-icon" style="background:#2FA8B9"><i class="fas fa-headset"></i></div>
    <div class="dc-val"><a href="<?= e($crmUrl) ?>" target="_blank" style="color:var(--secondary)"><i class="fas fa-external-link-alt"></i></a></div>
    <div class="dc-lbl">فتح بوابة CRM</div></div>
</div>

<div class="card">
  <div class="card-head">
    <h2><i class="fas fa-users-gear"></i> حسابات الموظفين</h2>
    <button class="btn btn-primary btn-sm" onclick="empAdd()"><i class="fas fa-user-plus"></i> موظف جديد</button>
  </div>
  <div class="card-body p0">
    <table class="tbl">
      <thead><tr><th>الاسم</th><th>اسم المستخدم</th><th>التواصل</th><th>الحالة</th><th>آخر دخول</th><th>الإجراءات</th></tr></thead>
      <tbody>
      <?php if ($emps): foreach ($emps as $e): ?>
        <tr<?= $e['status'] === 'inactive' ? ' style="opacity:.6"' : '' ?>>
          <td style="font-weight:700"><?= e($e['name']) ?></td>
          <td><code><?= e($e['username']) ?></code></td>
          <td style="font-size:.82rem">
            <?php if ($e['email']): ?><div><i class="fas fa-envelope"></i> <?= e($e['email']) ?></div><?php endif; ?>
            <?php if ($e['phone']): ?><div><i class="fas fa-phone"></i> <?= e($e['phone']) ?></div><?php endif; ?>
          </td>
          <td><span class="badge badge-<?= $e['status'] === 'active' ? 'active' : 'inactive' ?>"><?= $e['status'] === 'active' ? 'نشط' : 'موقوف' ?></span></td>
          <td style="font-size:.8rem;color:var(--muted)"><?= $e['last_login'] ? e(date('Y-m-d H:i', strtotime($e['last_login']))) : 'لم يدخل بعد' ?></td>
          <td style="white-space:nowrap">
            <button class="btn btn-outline btn-sm" onclick='empEdit(<?= json_encode($e, JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT) ?>)' title="تعديل"><i class="fas fa-pen"></i></button>
            <form method="POST" style="display:inline">
              <input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= $e['id'] ?>">
              <button class="btn btn-<?= $e['status'] === 'active' ? 'warning' : 'success' ?> btn-sm" title="<?= $e['status'] === 'active' ? 'إيقاف' : 'تفعيل' ?>">
                <i class="fas fa-<?= $e['status'] === 'active' ? 'ban' : 'check' ?>"></i>
              </button>
            </form>
            <form method="POST" style="display:inline" onsubmit="return confirm('حذف هذا الموظف نهائياً؟ (لن تُحذف بيانات العملاء)')">
              <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $e['id'] ?>">
              <button class="btn btn-danger btn-sm" title="حذف"><i class="fas fa-trash"></i></button>
            </form>
          </td>
        </tr>
      <?php endforeach; else: ?>
        <tr class="empty-row"><td colspan="6"><i class="fas fa-user-slash"></i> لا يوجد موظفون. أضف أول موظف.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="emp-modal" id="empModal">
  <div class="emp-modal-box">
    <div class="emp-modal-h"><h3 id="empTitle">موظف جديد</h3><button class="x" onclick="empClose()">&times;</button></div>
    <div class="emp-modal-b">
      <form method="POST">
        <input type="hidden" name="action" id="e_action" value="add">
        <input type="hidden" name="id" id="e_id" value="">
        <div class="emp-grid">
          <div class="fg mb-2"><label>الاسم الكامل <span class="req">*</span></label><input name="name" id="e_name" required></div>
          <div class="fg mb-2"><label>اسم المستخدم <span class="req">*</span></label><input name="username" id="e_username" required placeholder="employee1"></div>
          <div class="fg mb-2"><label>البريد الإلكتروني</label><input name="email" id="e_email" type="email"></div>
          <div class="fg mb-2"><label>الهاتف</label><input name="phone" id="e_phone"></div>
        </div>
        <div class="fg mb-2">
          <label>كلمة المرور <span class="req" id="e_pwreq">*</span></label>
          <input name="password" id="e_password" type="text" placeholder="8 أحرف على الأقل">
          <small id="e_pwhint" class="text-muted">اتركها فارغة للإبقاء على كلمة المرور الحالية.</small>
        </div>
        <button class="btn btn-primary btn-block" style="padding:.75rem"><i class="fas fa-save"></i> حفظ</button>
      </form>
    </div>
  </div>
</div>

<script>
var EM = document.getElementById('empModal');
function empClose(){ EM.classList.remove('open'); }
EM.addEventListener('click', function(ev){ if(ev.target===EM) empClose(); });
function sv(id,v){ var el=document.getElementById(id); if(el) el.value=(v==null?'':v); }
function empAdd(){
  document.getElementById('empTitle').textContent='موظف جديد';
  sv('e_action','add'); sv('e_id',''); sv('e_name',''); sv('e_username',''); sv('e_email',''); sv('e_phone',''); sv('e_password','');
  document.getElementById('e_pwreq').style.display='inline';
  document.getElementById('e_pwhint').style.display='none';
  document.getElementById('e_password').required=true;
  document.getElementById('e_password').placeholder='8 أحرف على الأقل';
  EM.classList.add('open');
}
function empEdit(d){
  document.getElementById('empTitle').textContent='تعديل: '+d.name;
  sv('e_action','edit'); sv('e_id',d.id); sv('e_name',d.name); sv('e_username',d.username);
  sv('e_email',d.email); sv('e_phone',d.phone); sv('e_password','');
  document.getElementById('e_pwreq').style.display='none';
  document.getElementById('e_pwhint').style.display='block';
  document.getElementById('e_password').required=false;
  document.getElementById('e_password').placeholder='كلمة مرور جديدة (اختياري)';
  EM.classList.add('open');
}
</script>
<?php layout_end(); ?>
