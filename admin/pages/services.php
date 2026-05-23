<?php
// ── POST actions ──────────────────────────────────────────────
$action = $_POST['action'] ?? '';

if ($action === 'save') {
    $id   = (int)($_POST['id'] ?? 0);
    $data = [
        trim($_POST['icon']        ?? 'fas fa-cog'),
        trim($_POST['title']       ?? ''),
        trim($_POST['description'] ?? ''),
        (int)($_POST['sort_order'] ?? 0),
        $_POST['status'] ?? 'active',
    ];
    if ($id) {
        db_exec("UPDATE services SET icon=?,title=?,description=?,sort_order=?,status=? WHERE id=?",
                array_merge($data, [$id]));
    } else {
        db_exec("INSERT INTO services (icon,title,description,sort_order,status) VALUES (?,?,?,?,?)", $data);
    }
    redirect_admin('services', $id ? 'تم تعديل الخدمة' : 'تم إضافة الخدمة');
}

if ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id) db_exec("DELETE FROM services WHERE id=?", [$id]);
    redirect_admin('services', 'تم حذف الخدمة', 'danger');
}

$services = db_all("SELECT * FROM services ORDER BY sort_order");
$edit     = null;
if (!empty($_GET['edit'])) {
    $edit = db_row("SELECT * FROM services WHERE id=?", [(int)$_GET['edit']]);
}

layout_start('الخدمات', 'services');
?>
<div class="card">
  <div class="card-head">
    <h2><i class="fas fa-tools"></i> قائمة الخدمات</h2>
    <button class="btn btn-primary btn-sm" onclick="openModal('svcModal')">
      <i class="fas fa-plus"></i> إضافة خدمة
    </button>
  </div>
  <div class="card-body p0">
    <table class="tbl">
      <thead><tr><th>الأيقونة</th><th>الخدمة</th><th>الوصف</th><th>الترتيب</th><th>الحالة</th><th>إجراءات</th></tr></thead>
      <tbody>
      <?php if ($services): foreach ($services as $s): ?>
        <tr>
          <td><i class="<?= e($s['icon']) ?>" style="font-size:1.5rem;color:var(--primary)"></i></td>
          <td><strong><?= e($s['title']) ?></strong></td>
          <td style="max-width:200px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= e($s['description']) ?></td>
          <td><?= (int)$s['sort_order'] ?></td>
          <td><span class="badge badge-<?= $s['status']==='active'?'active':'inactive' ?>"><?= $s['status']==='active'?'نشط':'مخفي' ?></span></td>
          <td>
            <div class="actions">
              <a href="?page=services&edit=<?= $s['id'] ?>" class="btn btn-warning btn-xs btn-icon"><i class="fas fa-edit"></i></a>
              <form method="POST" style="display:inline">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= $s['id'] ?>">
                <button type="submit" class="btn btn-danger btn-xs btn-icon" data-confirm="حذف '<?= e($s['title']) ?>'؟"><i class="fas fa-trash"></i></button>
              </form>
            </div>
          </td>
        </tr>
      <?php endforeach; else: ?>
        <tr class="empty-row"><td colspan="6"><i class="fas fa-inbox"></i> لا توجد خدمات</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal Add/Edit -->
<div class="modal-bg <?= $edit ? 'open' : '' ?>" id="svcModal">
  <div class="modal-dlg">
    <div class="modal-hd">
      <h3><i class="fas fa-tools"></i> <?= $edit ? 'تعديل الخدمة' : 'إضافة خدمة' ?></h3>
      <button class="modal-close" onclick="closeModal('svcModal')"><i class="fas fa-times"></i></button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="save">
      <input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
      <div class="modal-bd">
        <div class="form-grid">
          <div class="fg full">
            <label>أيقونة FontAwesome <span class="req">*</span></label>
            <div style="display:flex;gap:.5rem;align-items:center">
              <input type="text" name="icon" class="icon-inp"
                     value="<?= e($edit['icon'] ?? 'fas fa-cog') ?>"
                     placeholder="fas fa-globe" style="flex:1">
              <span class="icon-preview <?= e($edit['icon'] ?? 'fas fa-cog') ?>"></span>
            </div>
            <small>مثال: fas fa-globe | fab fa-whatsapp | <a href="https://fontawesome.com/icons" target="_blank">تصفح الأيقونات</a></small>
          </div>
          <div class="fg full">
            <label>اسم الخدمة <span class="req">*</span></label>
            <input type="text" name="title" value="<?= e($edit['title'] ?? '') ?>" required>
          </div>
          <div class="fg full">
            <label>الوصف</label>
            <textarea name="description"><?= e($edit['description'] ?? '') ?></textarea>
          </div>
          <div class="fg">
            <label>ترتيب العرض</label>
            <input type="number" name="sort_order" value="<?= (int)($edit['sort_order'] ?? 0) ?>" min="0">
          </div>
          <div class="fg">
            <label>الحالة</label>
            <select name="status">
              <option value="active"   <?= ($edit['status'] ?? 'active')==='active'  ?'selected':'' ?>>نشط</option>
              <option value="inactive" <?= ($edit['status'] ?? '')==='inactive'?'selected':'' ?>>مخفي</option>
            </select>
          </div>
        </div>
      </div>
      <div class="modal-ft">
        <button type="button" class="btn btn-outline" onclick="closeModal('svcModal')">إلغاء</button>
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> حفظ</button>
      </div>
    </form>
  </div>
</div>
<?php layout_end(); ?>
