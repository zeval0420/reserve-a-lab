<?php
require_once __DIR__ . '/../../src/bootstrap.php';

Auth::logout();
header('Location: /admin/login.php');
exit;
