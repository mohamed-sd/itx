<?php
$action = $_POST['action'] ?? '';
if ($action === 'save') {
    $id = (int)($_POST['id'] ?? 0);
    $d  = [trim($_POST['platform'] ?? ''), trim($_POST['icon'] ?? 'fas fa-link'),
           trim($_POST['url'] ?? ''), (int)($_POST['sort_order'] ?? 0), $_POST['status'] ?? 'active'];
    if ($id) db_exec("UPDATE social_media SET platform=?,icon=?,url=?,sort_order=?,status=? WHERE id=?", array_merge($d,[$id]));
    else     db_exec("INSERT INTO social_media (platform,icon,url,sort_order,status) VALUES (?,?,?,?,?)", $d);
    redirect_admin('social', $id ? 'تم التعديل' : 'تمت الإضافة');
}
if ($action === 'delete') {
    db_exec("DELETE FROM social_media WHERE id=?", [(int)$_POST['id']]);
    redirect_admin('social', 'تم الحذف', 'danger');
}

$rows = db_all("SELECT * FROM social_media ORDER BY sort_order");
$edit = !empty($_GET['edit']) ? db_row("SELECT * FROM social_media WHERE id=?", [(int)$_GET['edit']]) : null;

layout_start('السوشيال ميديا', 'social');
?>
<div class="card">
  <div class="card-head">
    <h2><i class="fas fa-share-alt"></i> روابط السوشيال ميديا</h2>
    <button class="btn btn-primary btn-sm" onclick="openModal('socialModal')"><i class="fas fa-plus"></i> إضافة</button>
  </div>
  <div class="card-body p0">
    <table class="tbl">
      <thead><tr><th>المنصة</th><th>الأيقونة</th><th>الرابط</th><th>الترتيب</th><th>الحالة</th><th>إجراءات</th></tr></thead>
      <tbody>
      <?php if ($rows): foreach ($rows as $r): ?>
        <tr>
          <td><strong><?= e($r['platform']) ?></strong></td>
          <td><i class="<?= e($r['icon']) ?>" style="font-size:1.4rem;color:var(--primary)"></i></td>
          <td><a href="<?= e($r['url']) ?>" target="_blank" style="color:var(--secondary)"><?= e($r['url'] ?: '—') ?></a></td>
          <td><?= (int)$r['sort_order'] ?></td>
          <td><span class="badge badge-<?= $r['status']==='active'?'active':'inactive' ?>"><?= $r['status']==='active'?'نشط':'مخفي' ?></span></td>
          <td>
            <div class="actions">
              <a href="?page=social&edit=<?= $r['id'] ?>" class="btn btn-warning btn-xs btn-icon"><i class="fas fa-edit"></i></a>
              <form method="POST" style="display:inline">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= $r['id'] ?>">
                <button class="btn btn-danger btn-xs btn-icon" data-confirm="حذف المنصة؟"><i class="fas fa-trash"></i></button>
              </form>
            </div>
          </td>
        </tr>
      <?php endforeach; else: ?>
        <tr class="empty-row"><td colspan="6"><i class="fas fa-inbox"></i> لا توجد منصات</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="modal-bg <?= $edit ? 'open' : '' ?>" id="socialModal">
  <div class="modal-dlg">
    <div class="modal-hd">
      <h3><?= $edit ? 'تعديل المنصة' : 'إضافة منصة' ?></h3>
      <button class="modal-close" onclick="closeModal('socialModal')"><i class="fas fa-times"></i></button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="save">
      <input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
      <div class="modal-bd">
        <div class="form-grid">
          <div class="fg">
            <label>اسم المنصة <span class="req">*</span></label>
            <input type="text" name="platform" value="<?= e($edit['platform'] ?? '') ?>" placeholder="فيسبوك" required>
          </div>
          <div class="fg">
            <label>أيقونة FontAwesome <span class="req">*</span></label>
            <div style="display:flex;gap:.5rem;align-items:center">
              <input type="text" name="icon" class="icon-inp" value="<?= e($edit['icon'] ?? 'fab fa-link') ?>"
                     placeholder="fab fa-facebook-f" style="flex:1" required>
              <span class="icon-preview <?= e($edit['icon'] ?? 'fab fa-link') ?>"></span>
            </div>
            <small>fab fa-facebook-f | fab fa-twitter | fab fa-instagram | fab fa-linkedin-in | fab fa-github | fab fa-youtube | fab fa-tiktok | fab fa-whatsapp</small>
          </div>
          <div class="fg full">
            <label>الرابط</label>
            <input type="url" name="url" value="<?= e($edit['url'] ?? '') ?>" placeholder="https://...">
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
        <button type="button" class="btn btn-outline" onclick="closeModal('socialModal')">إلغاء</button>
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> حفظ</button>
      </div>
    </form>
  </div>
</div>
<?php layout_end(); ?>
