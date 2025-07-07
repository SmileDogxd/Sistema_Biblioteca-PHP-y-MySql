<?php
session_start();

// Verificar si el usuario está logueado
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Verificar rol de administrador
function isAdmin() {
    return isset($_SESSION['user_rol']) && $_SESSION['user_rol'] == 'admin';
}

// Redireccionar si no está logueado
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: ../login.php");
        exit();
    }
}

// Redireccionar si no es admin
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header("Location: ../cliente/dashboard.php");
        exit();
    }
}

// Función para cerrar sesión
function logout() {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit();
}
?>