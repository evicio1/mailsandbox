<?php
require_once __DIR__ . '/../app/auth.php';

logout();
header("Location: /public/login.php");
exit;
