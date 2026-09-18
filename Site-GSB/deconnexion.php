<?php
require_once __DIR__ . '/inc/auth.php';
logout();
header('Location: connexion.php');
exit;
