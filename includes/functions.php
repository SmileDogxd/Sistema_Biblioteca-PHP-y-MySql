<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/auth.php';

// Función para obtener información del usuario actual
function getCurrentUser() {
    global $pdo;
    if (!isLoggedIn()) return null;
    
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Función para obtener todos los libros
function getAllLibros() {
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM libros ORDER BY titulo");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Función para obtener préstamos activos
function getPrestamosActivos() {
    global $pdo;
    $stmt = $pdo->query("SELECT p.*, l.titulo, u.nombre as usuario 
                         FROM prestamos p 
                         JOIN libros l ON p.libro_id = l.id 
                         JOIN usuarios u ON p.usuario_id = u.id 
                         WHERE p.estado = 'prestado'");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Otras funciones útiles...
?>