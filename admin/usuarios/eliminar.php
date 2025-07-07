<?php
require_once '../../includes/auth.php';
requireAdmin();
require_once '../../includes/functions.php';

// Verificar si se recibió ID
if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$id = (int)$_GET['id'];

// Verificar si el usuario a eliminar es el mismo que está logueado
if ($id == $_SESSION['user_id']) {
    $_SESSION['mensaje'] = "danger:No puedes eliminar tu propio usuario";
    header("Location: index.php");
    exit();
}

// Verificar si el usuario tiene préstamos activos
$stmt = $pdo->prepare("SELECT COUNT(*) FROM prestamos WHERE usuario_id = ? AND estado = 'prestado'");
$stmt->execute([$id]);
$prestamos_activos = $stmt->fetchColumn();

if ($prestamos_activos > 0) {
    $_SESSION['mensaje'] = "danger:No se puede eliminar el usuario porque tiene préstamos activos";
    header("Location: index.php");
    exit();
}

// Eliminar usuario
$stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
if ($stmt->execute([$id])) {
    $_SESSION['mensaje'] = "success:Usuario eliminado correctamente";
} else {
    $_SESSION['mensaje'] = "danger:Error al eliminar el usuario";
}

header("Location: index.php");
exit();
?>