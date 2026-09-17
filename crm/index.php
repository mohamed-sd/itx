<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';

require_emp_login();

$valid = ['dashboard', 'customers', 'customer', 'followups', 'reports'];
$page  = $_GET['page'] ?? 'dashboard';
if (!in_array($page, $valid, true)) $page = 'dashboard';

require_once __DIR__ . "/pages/{$page}.php";
