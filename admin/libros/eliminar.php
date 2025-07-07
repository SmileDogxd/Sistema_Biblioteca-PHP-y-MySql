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

// Obtener datos del libro para eliminar la portada
$stmt = $pdo->prepare("SELECT portada FROM libros WHERE id = ?");
$stmt->execute([$id]);
$libro = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$libro) {
    header("Location: index.php");
    exit();
}

// Verificar si hay préstamos activos para este libro
$stmt = $pdo->prepare("SELECT COUNT(*) FROM prestamos WHERE libro_id = ? AND estado = 'prestado'");
$stmt->execute([$id]);
$prestamos_activos = $stmt->fetchColumn();

if ($prestamos_activos > 0) {
    $_SESSION['mensaje'] = "danger:No se puede eliminar el libro porque tiene préstamos activos";
    header("Location: index.php");
    exit();
}

// Eliminar portada si existe
if ($libro['portada']) {
    $ruta_imagen = "../../uploads/portadas/" . $libro['portada'];
    if (file_exists($ruta_imagen)) {
        unlink($ruta_imagen);
    }
}

// Eliminar libro
$stmt = $pdo->prepare("DELETE FROM libros WHERE id = ?");
if ($stmt->execute([$id])) {
    $_SESSION['mensaje'] = "success:Libro eliminado correctamente";
} else {
    $_SESSION['mensaje'] = "danger:Error al eliminar el libro";
}

header("Location: index.php");
exit();
?>