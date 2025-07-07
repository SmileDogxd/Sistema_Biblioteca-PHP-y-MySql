<?php
require_once 'includes/auth.php';

// Verificar si hay una sesión activa
session_start();

// Redirección basada en el estado de autenticación
if (isset($_SESSION['user_id'])) {
    // Usuario autenticado - redirigir según rol
    if (isset($_SESSION['user_rol']) && $_SESSION['user_rol'] == 'admin') {
        header("Location: admin/dashboard.php");
    } else {
        header("Location: cliente/dashboard.php");
    }
} else {
    // Usuario no autenticado - redirigir a login
    header("Location: login.php");
}
exit();
?>