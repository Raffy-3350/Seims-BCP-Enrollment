<?php
session_start();
require_once __DIR__ . '/../app/config/db.php';

$_SESSION = [];
session_destroy();

header("location: login.php");
exit;