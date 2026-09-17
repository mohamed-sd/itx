<?php
require_once __DIR__ . '/includes/auth.php';

if (crm_logged_in()) { header('Location: ' . crm_url('dashboard')); exit; }

$error = '';
$maxAttempts = 5; $lockWindow = 300;
$attempts = (int)($_SESSION['crm_attempts'] ?? 0);
$firstTry = (int)($_SESSION['crm_first'] ?? 0);
if ($attempts > 0 && $firstTry > 0 && (time() - $firstTry) > $lockWindow) {
    unset($_SESSION['crm_attempts'], $_SESSION['crm_first']); $attempts = 0; $firstTry = 0;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($attempts >= $maxAttempts && $firstTry > 0 && (time() - $firstTry) <= $lockWindow) {
        $wait = $lockWindow - (time() - $firstTry);
        $error = 'تم تجاوز عدد المحاولات. حاول بعد ' . max(1, $wait) . ' ثانية.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        if ($username && $password) {
            try {
                $row = db_row("SELECT id, username, password, name, status FROM employees WHERE username = ? LIMIT 1", [$username]);
                if ($row && $row['status'] === 'active' && password_verify($password, $row['password'])) {
                    session_regenerate_id(true);
                    $_SESSION['emp_id']   = $row['id'];
                    $_SESSION['emp_name'] = $row['name'] ?: $row['username'];
                    $_SESSION['emp_last'] = time();
                    unset($_SESSION['crm_attempts'], $_SESSION['crm_first']);
                    try { db_exec("UPDATE employees SET last_login = NOW() WHERE id = ?", [$row['id']]); } catch (\Throwable $ex) {}
                    header('Location: ' . crm_url('dashboard')); exit;
                }
                if ($row && $row['status'] !== 'active') {
                    $error = 'حسابك موقوف حالياً. تواصل مع الإدارة.';
                }
            } catch (\Throwable $ex) {}
            if ($error === '') {
                $_SESSION['crm_attempts'] = $attempts + 1;
                if (empty($_SESSION['crm_first'])) $_SESSION['crm_first'] = time();
                usleep(random_int(250000, 500000));
                $error = 'اسم المستخدم أو كلمة المرور غير صحيحة';
            }
        } else {
            $error = 'يرجى تعبئة جميع الحقول';
        }
    }
}

$logo = 'logo.jpeg';
try { $r = db_row("SELECT setting_value v FROM site_settings WHERE setting_key='site_logo'"); if (!empty($r['v'])) $logo = $r['v']; } catch (\Throwable $ex) {}
$logoUrl = preg_match('#^https?://#', $logo) ? $logo : (dirname(crm_prefix()) ?: '') . '/' . ltrim($logo, '/');
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>دخول الموظفين — ITX CRM</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="<?= crm_asset('crm.css') ?>">
</head>
<body>
<div class="c-login-page">
  <div class="c-login-box">
    <div class="c-login-logo">
      <img src="<?= e($logoUrl) ?>" alt="" onerror="this.style.display='none'">
      <h1>نظام إدارة العملاء</h1>
      <p>بوابة دخول الموظفين</p>
    </div>
    <?php if ($error): ?>
      <div class="c-alert c-alert-danger"><i class="fas fa-exclamation-triangle"></i> <?= e($error) ?></div>
    <?php endif; ?>
    <?= crm_flash() ?>
    <form method="POST">
      <div class="c-fg">
        <label>اسم المستخدم <span class="req">*</span></label>
        <input type="text" name="username" value="<?= e($_POST['username'] ?? '') ?>" required autofocus autocomplete="username">
      </div>
      <div class="c-fg">
        <label>كلمة المرور <span class="req">*</span></label>
        <input type="password" name="password" placeholder="••••••••" required autocomplete="current-password">
      </div>
      <button class="c-btn c-btn-primary c-btn-block" style="padding:.8rem"><i class="fas fa-sign-in-alt"></i> دخول</button>
    </form>
  </div>
</div>
</body>
</html>
