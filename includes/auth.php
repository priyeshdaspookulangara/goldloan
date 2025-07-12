<?php
session_start();

function check_login() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }
}

function logout() {
    session_destroy();
    header("Location: login.php");
    exit();
}

if (isset($_GET['logout'])) {
    logout();
}
?>
