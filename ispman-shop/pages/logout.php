<?php
session_start();
require_once '../config/config.php';
require_once '../includes/Auth.php';

$auth = new Auth(getPDO());
$auth->logout();

header('Location: ../index.php');
exit;
