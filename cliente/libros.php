<?php
require_once '../includes/auth.php';
requireLogin();
require_once '../includes/functions.php';

if (isAdmin()) {
    header("Location: ../admin/dashboard.php");
    exit();
}

// Obtener todos los libros disponibles
$query = "SELECT * FROM libros WHERE disponibles > 0 ORDER BY titulo";
$libros = $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);

// Busqueda
if (isset($_GET['busqueda'])) {
    $busqueda = '%' . $_GET['busqueda'] . '%';
    $stmt = $pdo->prepare("SELECT * FROM libros 
                          WHERE (titulo LIKE ? OR autor LIKE ? OR isbn LIKE ?) 
                          AND disponibles > 0 
                          ORDER BY titulo");
    $stmt->execute([$busqueda, $busqueda, $busqueda]);
    $libros = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Libros Disponibles - Biblioteca</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/cliente.css" rel="stylesheet">
</head>
<body class="bg-dark text-light">
    <?php include '../includes/header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between pt-3 pb-2 mb-3 border-bottom border-secondary">
                    <h1 class="h2"><i class="fas fa-book me-2"></i>Libros Disponibles</h1>
                    <form class="d-flex" method="GET" action="libros.php">
                        <input class="form-control bg-gray-800 text-black border-secondary" 
                               type="search" 
                               name="busqueda" 
                               placeholder="Buscar libros..." 
                               aria-label="Buscar">
                        <button class="btn btn-outline-light ms-2" type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>
                </div>
                
                <div class="row">
                    <?php if (empty($libros)): ?>
                        <div class="col-12">
                            <div class="alert alert-info">
                                No se encontraron libros disponibles.
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($libros as $libro): ?>
                        <div class="col-md-4 mb-4">
                            <div class="card bg-gray-800 border-secondary h-100">
                                <div class="card-header bg-gray-700 border-secondary">
                                    <h5 class="card-title mb-0"><?= htmlspecialchars($libro['titulo']) ?></h5>
                                </div>
                                <div class="card-body">
                                    <p class="card-text"><strong>Autor:</strong> <?= htmlspecialchars($libro['autor']) ?></p>
                                    <p class="card-text"><strong>Editorial:</strong> <?= htmlspecialchars($libro['editorial']) ?></p>
                                    <p class="card-text"><strong>Año:</strong> <?= htmlspecialchars($libro['anio_publicacion']) ?></p>
                                    <p class="card-text"><strong>Disponibles:</strong> 
                                        <span class="badge bg-success"><?= $libro['disponibles'] ?></span>
                                    </p>
                                </div>
                                <div class="card-footer bg-gray-800 border-secondary">
                                    <a href="reservas.php?libro_id=<?= $libro['id'] ?>" 
                                       class="btn btn-sm btn-primary">
                                        <i class="fas fa-calendar-plus me-1"></i> Reservar
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>