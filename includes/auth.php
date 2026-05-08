<?php
session_start();

function checkLogin() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }
}

function checkLoginForPages() {
    // For files inside /pages directory
    if (!isset($_SESSION['user_id'])) {
        header("Location: ../login.php");
        exit();
    }
}
?>
