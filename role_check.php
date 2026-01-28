<?php
if (!isset($_SESSION['role'])) {
    die("Access denied.");
}

function isAdmin() {
    return $_SESSION['role'] === 'Admin';
}

function isStaff() {
    return $_SESSION['role'] === 'Staff';
}

function isViewer() {
    return $_SESSION['role'] === 'Viewer';
}
