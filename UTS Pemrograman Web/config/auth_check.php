<?php
/**
 * CALIBRAMED SYSTEM - Authentication Middleware
 */

require_once __DIR__ . '/database.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$currentUser = getCurrentUser($pdo);
if (!$currentUser) {
    session_destroy();
    header("Location: login.php?msg=invalid_session");
    exit();
}
