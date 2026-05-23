<?php
require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) {
    header('Location: ' . admin_url('dashboard'));
    exit;
}

$error = '';
$maxAttempts = 5;
$lockWindow  = 300;

$attempts = (int)($_SESSION['login_attempts'] ?? 0);
$firstTry = (int)($_SESSION['login_first_try'] ?? 0);

if ($attempts > 0 && $firstTry > 0 && (time() - $firstTry) > $lockWindow) {
  unset($_SESSION['login_attempts'], $_SESSION['login_first_try']);
  $attempts = 0;
  $firstTry = 0;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if ($attempts >= $maxAttempts && $firstTry > 0 && (time() - $firstTry) <= $lockWindow) {
    $waitLeft = $lockWindow - (time() - $firstTry);
    $error = 'تم تجاوز عدد المحاولات المسموح. حاول مرة أخرى بعد ' . max(1, $waitLeft) . ' ثانية.';
  } else {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username && $password) {
        try {
            $row = db_row("SELECT id, username, password, name FROM admins WHERE username = ? LIMIT 1", [$username]);
            if ($row && password_verify($password, $row['password'])) {
                session_regenerate_id(true);
                $_SESSION['admin_id']   = $row['id'];
                $_SESSION['admin_name'] = $row['name'] ?: $row['username'];
        $_SESSION['last_activity'] = time();
        unset($_SESSION['login_attempts'], $_SESSION['login_first_try']);
                header('Location: ' . admin_url('dashboard'));
                exit;
            }
        } catch (\Exception $e) {}

    $_SESSION['login_attempts'] = $attempts + 1;
    if (empty($_SESSION['login_first_try'])) {
      $_SESSION['login_first_try'] = time();
    }
    usleep(random_int(250000, 500000));
        $error = 'اسم المستخدم أو كلمة المرور غير صحيحة';
    } else {
        $error = 'يرجى تعبئة جميع الحقول';
    }
  }
}

$logo     = get_setting('site_logo', 'logo.jpeg');
$siteName = get_setting('site_name', 'ITX');
$logoUrl  = img_url($logo);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>تسجيل الدخول — <?= e($siteName) ?></title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="<?= admin_prefix() ?>/assets/admin.css">
</head>
<body>
<div class="login-page">
  <div class="login-box">
    <div class="login-logo">
      <?php if ($logoUrl): ?>
        <img src="<?= e($logoUrl) ?>" alt="logo">
      <?php endif; ?>
      <h1><?= e($siteName) ?></h1>
      <p>لوحة إدارة الموقع</p>
    </div>

    <?php if ($error): ?>
      <div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> <?= e($error) ?></div>
    <?php endif; ?>

    <form method="POST">
      <div class="fg mb-2">
        <label>اسم المستخدم <span class="req">*</span></label>
        <input type="text" name="username" value="<?= e($_POST['username'] ?? '') ?>"
               placeholder="admin" required autofocus autocomplete="username">
      </div>
      <div class="fg mb-2">
        <label>كلمة المرور <span class="req">*</span></label>
        <input type="password" name="password" placeholder="••••••••" required autocomplete="current-password">
      </div>
      <button type="submit" class="btn btn-primary btn-block" style="margin-top:.5rem;padding:.8rem">
        <i class="fas fa-sign-in-alt"></i> دخول
      </button>
    </form>

    <div style="text-align:center;margin-top:1.5rem">
      <a href="<?= site_url() ?>" class="site-preview-link">
        <i class="fas fa-arrow-left"></i> العودة للموقع
      </a>
    </div>
  </div>
</div>
</body>
</html>
