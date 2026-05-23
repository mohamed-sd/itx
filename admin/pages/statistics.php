<?php
$action = $_POST['action'] ?? '';
if ($action === 'save') {
    $id = (int)($_POST['id'] ?? 0);
    $d  = [trim($_POST['value'] ?? ''), trim($_POST['label'] ?? ''), (int)($_POST['sort_order'] ?? 0), $_POST['status'] ?? 'active'];
    if ($id) db_exec("UPDATE statistics SET value=?,label=?,sort_order=?,status=? WHERE id=?", array_merge($d,[$id]));
    else     db_exec("INSERT INTO statistics (value,label,sort_order,status) VALUES (?,?,?,?)", $d);
    redirect_admin('statistics', $id ? 'تم التعديل' : 'تمت الإضافة');
}
if ($action === 'delete') {
    db_exec("DELETE FROM statistics WHERE id=?", [(int)$_POST['id']]);
    redirect_admin('statistics', 'تم الحذف', 'danger');
}

$rows = db_all("SELECT * FROM statistics ORDER BY sort_order");
$edit = !empty($_GET['edit']) ? db_row("SELECT * FROM statistics WHERE id=?", [(int)$_GET['edit']]) : null;

layout_start('الإحصائيات', 'statistics');
?>
<div class="card">
  <div class="card-head">
    <h2><i class="fas fa-chart-bar"></i> الإحصائيات</h2>
    <button class="btn btn-primary btn-sm" onclick="openModal('statModal')"><i class="fas fa-plus"></i> إضافة</button>
  </div>
  <div class="card-body p0">
    <table class="tbl">
      <thead><tr><th>القيمة</th><th>التسمية</th><th>الترتيب</th><th>الحالة</th><th>إجراءات</th></tr></thead>
      <tbody>
      <?php if ($rows): foreach ($rows as $r): ?>
        <tr>
          <td><strong style="font-size:1.2rem;color:var(--primary)"><?= e($r['value']) ?></strong></td>
          <td><?= e($r['label']) ?></td>
          <td><?= (int)$r['sort_order'] ?></td>
          <td><span class="badge badge-<?= $r['status']==='active'?'active':'inactive' ?>"><?= $r['status']==='active'?'نشط':'مخفي' ?></span></td>
          <td>
            <div class="actions">
              <a href="?page=statistics&edit=<?= $r['id'] ?>" class="btn btn-warning btn-xs btn-icon"><i class="fas fa-edit"></i></a>
              <form method="POST" style="display:inline">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= $r['id'] ?>">
                <button class="btn btn-danger btn-xs btn-icon" data-confirm="حذف الإحصائية؟"><i class="fas fa-trash"></i></button>
              </form>
            </div>
          </td>
        </tr>
      <?php endforeach; else: ?>
        <tr class="empty-row"><td colspan="5"><i class="fas fa-inbox"></i> لا توجد إحصائيات</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="modal-bg <?= $edit ? 'open' : '' ?>" id="statModal">
  <div class="modal-dlg">
    <div class="modal-hd">
      <h3><?= $edit ? 'تعديل إحصائية' : 'إضافة إحصائية' ?></h3>
      <button class="modal-close" onclick="closeModal('statModal')"><i class="fas fa-times"></i></button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="save">
      <input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
      <div class="modal-bd">
        <div class="form-grid">
          <div class="fg">
            <label>القيمة <span class="req">*</span></label>
            <input type="text" name="value" value="<?= e($edit['value'] ?? '') ?>" placeholder="500+" required>
          </div>
          <div class="fg">
            <label>التسمية <span class="req">*</span></label>
            <input type="text" name="label" value="<?= e($edit['label'] ?? '') ?>" placeholder="مشروع منجز" required>
          </div>
          <div class="fg">
            <label>الترتيب</label>
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
        <button type="button" class="btn btn-outline" onclick="closeModal('statModal')">إلغاء</button>
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> حفظ</button>
      </div>
    </form>
  </div>
</div>
<?php layout_end(); ?>
