<?php
/**
 * ITX Admin Setup — Run ONCE to create the admin account.
 * DELETE this file after setup for security.
 */
require_once __DIR__ . '/includes/auth.php';

$done  = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password']         ?? '';
    $confirm  = $_POST['confirm']          ?? '';
    $name     = trim($_POST['name']        ?? '');

    if (!$username || !$password || !$name) {
        $error = 'يرجى تعبئة جميع الحقول';
    } elseif (strlen($password) < 8) {
        $error = 'كلمة المرور يجب أن تكون 8 أحرف على الأقل';
    } elseif ($password !== $confirm) {
        $error = 'كلمتا المرور غير متطابقتين';
    } else {
        try {
            $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            $db   = getDB();
            // Update if exists, insert if not
            $exist = db_row("SELECT id FROM admins WHERE username = ?", [$username]);
            if ($exist) {
                db_exec("UPDATE admins SET password = ?, name = ? WHERE username = ?", [$hash, $name, $username]);
            } else {
                db_exec("INSERT INTO admins (username, password, name) VALUES (?, ?, ?)", [$username, $hash, $name]);
            }
            $done = true;
        } catch (\Exception $e) {
            $error = 'خطأ: ' . $e->getMessage();
        }
    }
}

$logoUrl = img_url(get_setting('site_logo', 'logo.jpeg'));
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>إعداد الحساب الإداري — ITX</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="<?= admin_prefix() ?>/assets/admin.css">
</head>
<body>
<div class="login-page">
  <div class="login-box">
    <div class="login-logo">
      <?php if ($logoUrl): ?><img src="<?= e($logoUrl) ?>" alt=""><?php endif; ?>
      <h1>إعداد الحساب</h1>
      <p>أنشئ حساب المدير الخاص بك</p>
    </div>

    <?php if ($done): ?>
      <div class="alert alert-success"><i class="fas fa-check-circle"></i> تم إنشاء الحساب بنجاح!</div>
      <div style="text-align:center;margin-top:1rem">
        <a href="login.php" class="btn btn-primary"><i class="fas fa-sign-in-alt"></i> تسجيل الدخول</a>
        <p style="margin-top:1rem;color:#ef4444;font-size:.85rem;font-weight:700">
          <i class="fas fa-exclamation-triangle"></i> احذف ملف setup.php الآن لحماية موقعك!
        </p>
      </div>
    <?php else: ?>
      <?php if ($error): ?>
        <div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> <?= e($error) ?></div>
      <?php endif; ?>
      <form method="POST">
        <div class="fg mb-2">
          <label>الاسم الكامل <span class="req">*</span></label>
          <input type="text" name="name" value="<?= e($_POST['name'] ?? 'مدير النظام') ?>" required>
        </div>
        <div class="fg mb-2">
          <label>اسم المستخدم <span class="req">*</span></label>
          <input type="text" name="username" value="<?= e($_POST['username'] ?? 'admin') ?>" required>
        </div>
        <div class="fg mb-2">
          <label>كلمة المرور <span class="req">*</span></label>
          <input type="password" name="password" placeholder="8 أحرف على الأقل" required>
        </div>
        <div class="fg mb-2">
          <label>تأكيد كلمة المرور <span class="req">*</span></label>
          <input type="password" name="confirm" required>
        </div>
        <button type="submit" class="btn btn-primary btn-block" style="margin-top:.5rem;padding:.8rem">
          <i class="fas fa-user-plus"></i> إنشاء الحساب
        </button>
      </form>
    <?php endif; ?>
  </div>
</div>
</body>
</html>
