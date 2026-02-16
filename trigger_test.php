<?php
// trigger_test.php
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['host'] = 'smtp.gmail.com';
$_POST['port'] = 587;
$_POST['username'] = 'adohuken2005@gmail.com';
$_POST['password'] = ''; // Should auto-load from config
$_POST['encryption'] = 'tls';

// Fake server name for EHLO
$_SERVER['SERVER_NAME'] = 'localhost';

require 'smtp_tester.php';
?>