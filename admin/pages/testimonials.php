<?php
$action = $_POST['action'] ?? '';
if ($action === 'save') {
    $id = (int)($_POST['id'] ?? 0);
    $d  = [
        trim($_POST['author_name'] ?? ''), trim($_POST['author_role'] ?? ''),
        max(1, min(5, (int)($_POST['rating'] ?? 5))),
        trim($_POST['content'] ?? ''), (int)($_POST['sort_order'] ?? 0),
        $_POST['status'] ?? 'active',
    ];
    if ($id) db_exec("UPDATE testimonials SET author_name=?,author_role=?,rating=?,content=?,sort_order=?,status=? WHERE id=?", array_merge($d,[$id]));
    else     db_exec("INSERT INTO testimonials (author_name,author_role,rating,content,sort_order,status) VALUES (?,?,?,?,?,?)", $d);
    redirect_admin('testimonials', $id ? 'تم التعديل' : 'تمت الإضافة');
}
if ($action === 'delete') {
    db_exec("DELETE FROM testimonials WHERE id=?", [(int)$_POST['id']]);
    redirect_admin('testimonials', 'تم الحذف', 'danger');
}

$rows = db_all("SELECT * FROM testimonials ORDER BY sort_order");
$edit = !empty($_GET['edit']) ? db_row("SELECT * FROM testimonials WHERE id=?", [(int)$_GET['edit']]) : null;

layout_start('آراء العملاء', 'testimonials');
?>
<div class="card">
  <div class="card-head">
    <h2><i class="fas fa-comments"></i> آراء العملاء</h2>
    <button class="btn btn-primary btn-sm" onclick="openModal('testiModal')"><i class="fas fa-plus"></i> إضافة</button>
  </div>
  <div class="card-body p0">
    <table class="tbl">
      <thead><tr><th>العميل</th><th>الدور</th><th>التقييم</th><th>الرأي</th><th>الترتيب</th><th>الحالة</th><th>إجراءات</th></tr></thead>
      <tbody>
      <?php if ($rows): foreach ($rows as $r): ?>
        <tr>
          <td><strong><?= e($r['author_name']) ?></strong></td>
          <td><?= e($r['author_role']) ?></td>
          <td style="color:#ffc107;letter-spacing:2px"><?= str_repeat('★', (int)$r['rating']) ?></td>
          <td style="max-width:180px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= e($r['content']) ?></td>
          <td><?= (int)$r['sort_order'] ?></td>
          <td><span class="badge badge-<?= $r['status']==='active'?'active':'inactive' ?>"><?= $r['status']==='active'?'نشط':'مخفي' ?></span></td>
          <td>
            <div class="actions">
              <a href="?page=testimonials&edit=<?= $r['id'] ?>" class="btn btn-warning btn-xs btn-icon"><i class="fas fa-edit"></i></a>
              <form method="POST" style="display:inline">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= $r['id'] ?>">
                <button class="btn btn-danger btn-xs btn-icon" data-confirm="حذف التقييم؟"><i class="fas fa-trash"></i></button>
              </form>
            </div>
          </td>
        </tr>
      <?php endforeach; else: ?>
        <tr class="empty-row"><td colspan="7"><i class="fas fa-inbox"></i> لا توجد تقييمات</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="modal-bg <?= $edit ? 'open' : '' ?>" id="testiModal">
  <div class="modal-dlg">
    <div class="modal-hd">
      <h3><?= $edit ? 'تعديل التقييم' : 'إضافة تقييم' ?></h3>
      <button class="modal-close" onclick="closeModal('testiModal')"><i class="fas fa-times"></i></button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="save">
      <input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
      <div class="modal-bd">
        <div class="form-grid">
          <div class="fg">
            <label>اسم العميل <span class="req">*</span></label>
            <input type="text" name="author_name" value="<?= e($edit['author_name'] ?? '') ?>" required>
          </div>
          <div class="fg">
            <label>المسمى الوظيفي / الدور</label>
            <input type="text" name="author_role" value="<?= e($edit['author_role'] ?? '') ?>"
                   placeholder="مالك متجر إلكتروني">
          </div>
          <div class="fg full">
            <label>التقييم</label>
            <div class="stars-input">
              <?php for ($s = 5; $s >= 1; $s--): $checked = (int)($edit['rating'] ?? 5) >= $s ? 'checked' : ''; ?>
                <input type="radio" name="rating" id="star<?= $s ?>" value="<?= $s ?>" <?= $checked ?>>
                <label for="star<?= $s ?>">★</label>
              <?php endfor; ?>
            </div>
          </div>
          <div class="fg full">
            <label>نص الرأي <span class="req">*</span></label>
            <textarea name="content" rows="4" required><?= e($edit['content'] ?? '') ?></textarea>
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
        <button type="button" class="btn btn-outline" onclick="closeModal('testiModal')">إلغاء</button>
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> حفظ</button>
      </div>
    </form>
  </div>
</div>
<?php layout_end(); ?>
