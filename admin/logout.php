<?php
require_once __DIR__ . '/includes/auth.php';
session_destroy();
header('Location: ' . admin_prefix() . '/login.php');
exit;
