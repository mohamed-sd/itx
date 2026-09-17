<?php
require_once __DIR__ . '/includes/auth.php';
crm_kill_session();
header('Location: ' . crm_prefix() . '/login.php');
exit;
