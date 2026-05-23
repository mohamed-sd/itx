<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';

require_login();

$valid = ['dashboard','settings','hero','about','services','statistics',
          'works','testimonials','contact','social','pages'];

$page = $_GET['page'] ?? 'dashboard';
if (!in_array($page, $valid)) $page = 'dashboard';

require_once __DIR__ . "/pages/{$page}.php";
