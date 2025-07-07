<?php
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/functions.php';

// Obtener estadísticas
$stats = [
    'total_libros' => $pdo->query("SELECT COUNT(*) FROM libros")->fetchColumn(),
    'libros_disponibles' => $pdo->query("SELECT SUM(disponibles) FROM libros")->fetchColumn(),
    'total_usuarios' => $pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn(),
    'prestamos_activos' => $pdo->query("SELECT COUNT(*) FROM prestamos WHERE estado = 'prestado'")->fetchColumn(),
    'prestamos_atrasados' => $pdo->query("SELECT COUNT(*) FROM prestamos WHERE estado = 'prestado' AND fecha_devolucion_estimada < NOW()")->fetchColumn(),
];

// Obtener últimos préstamos
$ultimos_prestamos = $pdo->query("SELECT p.*, l.titulo, u.nombre as usuario 
                                 FROM prestamos p 
                                 JOIN libros l ON p.libro_id = l.id 
                                 JOIN usuarios u ON p.usuario_id = u.id 
                                 ORDER BY p.fecha_prestamo DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

// Obtener últimos libros agregados
$ultimos_libros = $pdo->query("SELECT * FROM libros ORDER BY fecha_agregado DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Biblioteca</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@mdi/font@6.9.96/css/materialdesignicons.min.css" rel="stylesheet">
    <link href="../assets/css/admin.css" rel="stylesheet">
</head>
<body class="bg-dark text-light">
    <?php include '../includes/header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 d-md-block bg-dark sidebar">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="dashboard.php">
                                <i class="mdi mdi-view-dashboard me-2"></i>Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="libros/">
                                <i class="mdi mdi-book-multiple me-2"></i>Libros
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="prestamos/">
                                <i class="mdi mdi-book-arrow-right me-2"></i>Préstamos
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="devoluciones/">
                                <i class="mdi mdi-book-arrow-left me-2"></i>Devoluciones
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="usuarios/">
                                <i class="mdi mdi-account-group me-2"></i>Usuarios
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="perfil.php">
                                <i class="mdi mdi-account me-2"></i>Perfil
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../logout.php">
                                <i class="mdi mdi-logout me-2"></i>Cerrar Sesión
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>
            
            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between pt-3 pb-2 mb-3 border-bottom border-secondary">
                    <h1 class="h2"><i class="mdi mdi-view-dashboard me-2"></i>Dashboard</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-light">
                                <i class="mdi mdi-calendar"></i> <?= date('d/m/Y') ?>
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Estadísticas -->
                <div class="row mb-4">
                    <div class="col-md-6 col-lg-3 mb-3">
                        <div class="card bg-gray-800 border-primary">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-2">Total Libros</h6>
                                        <h3 class="mb-0"><?= $stats['total_libros'] ?></h3>
                                    </div>
                                    <div class="bg-primary bg-opacity-10 p-3 rounded">
                                        <i class="mdi mdi-book-multiple text-primary" style="font-size: 1.5rem;"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 col-lg-3 mb-3">
                        <div class="card bg-gray-800 border-success">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-2">Disponibles</h6>
                                        <h3 class="mb-0"><?= $stats['libros_disponibles'] ?></h3>
                                    </div>
                                    <div class="bg-success bg-opacity-10 p-3 rounded">
                                        <i class="mdi mdi-book-check text-success" style="font-size: 1.5rem;"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 col-lg-3 mb-3">
                        <div class="card bg-gray-800 border-info">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-2">Total Usuarios</h6>
                                        <h3 class="mb-0"><?= $stats['total_usuarios'] ?></h3>
                                    </div>
                                    <div class="bg-info bg-opacity-10 p-3 rounded">
                                        <i class="mdi mdi-account-group text-info" style="font-size: 1.5rem;"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 col-lg-3 mb-3">
                        <div class="card bg-gray-800 border-warning">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-2">Préstamos Activos</h6>
                                        <h3 class="mb-0"><?= $stats['prestamos_activos'] ?></h3>
                                    </div>
                                    <div class="bg-warning bg-opacity-10 p-3 rounded">
                                        <i class="mdi mdi-book-arrow-right text-warning" style="font-size: 1.5rem;"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Calendario y Secciones -->
                <div class="row">
                    <div class="col-lg-8 mb-4">
                        <div class="card bg-gray-800 border-secondary h-100">
                            <div class="card-header bg-gray-700 border-secondary">
                                <h5 class="mb-0"><i class="mdi mdi-calendar me-2"></i>Calendario de Préstamos</h5>
                            </div>
                            <div class="card-body">
                                <div id="calendar"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-4 mb-4">
                        <div class="card bg-gray-800 border-secondary h-100">
                            <div class="card-header bg-gray-700 border-secondary">
                                <h5 class="mb-0"><i class="mdi mdi-alert-circle me-2"></i>Préstamos Atrasados</h5>
                            </div>
                            <div class="card-body">
                                <?php if ($stats['prestamos_atrasados'] > 0): ?>
                                    <div class="alert alert-danger">
                                        <i class="mdi mdi-alert me-2"></i>
                                        Hay <?= $stats['prestamos_atrasados'] ?> préstamos atrasados
                                    </div>
                                    <?php 
                                    $atrasados = $pdo->query("SELECT p.*, l.titulo, u.nombre as usuario 
                                                            FROM prestamos p 
                                                            JOIN libros l ON p.libro_id = l.id 
                                                            JOIN usuarios u ON p.usuario_id = u.id 
                                                            WHERE p.estado = 'prestado' AND p.fecha_devolucion_estimada < NOW() 
                                                            LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
                                    foreach ($atrasados as $atrasado): 
                                        $diasAtraso = (new DateTime())->diff(new DateTime($atrasado['fecha_devolucion_estimada']))->days;
                                    ?>
                                        <div class="mb-3 p-2 bg-gray-700 rounded">
                                            <h6 class="mb-1"><?= htmlspecialchars($atrasado['titulo']) ?></h6>
                                            <p class="mb-1 text-muted small">Usuario: <?= htmlspecialchars($atrasado['usuario']) ?></p>
                                            <p class="mb-1 text-danger small">
                                                <i class="mdi mdi-clock-alert me-1"></i>
                                                <?= $diasAtraso ?> días de atraso
                                            </p>
                                        </div>
                                    <?php endforeach; ?>
                                    <a href="prestamos/" class="btn btn-sm btn-outline-danger mt-2">
                                        Ver todos los atrasados
                                    </a>
                                <?php else: ?>
                                    <div class="alert alert-success">
                                        <i class="mdi mdi-check-circle me-2"></i>
                                        No hay préstamos atrasados
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Últimos Préstamos y Libros -->
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <div class="card bg-gray-800 border-secondary">
                            <div class="card-header bg-gray-700 border-secondary">
                                <h5 class="mb-0"><i class="mdi mdi-history me-2"></i>Últimos Préstamos</h5>
                            </div>
                            <div class="card-body">
                                <div class="list-group list-group-flush">
                                    <?php foreach ($ultimos_prestamos as $prestamo): ?>
                                    <div class="list-group-item bg-transparent text-light border-secondary">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1"><?= htmlspecialchars($prestamo['titulo']) ?></h6>
                                            <small class="text-muted">
                                                <?= date('d/m', strtotime($prestamo['fecha_prestamo'])) ?>
                                            </small>
                                        </div>
                                        <p class="mb-1 small text-muted">
                                            Usuario: <?= htmlspecialchars($prestamo['usuario']) ?>
                                        </p>
                                        <small class="text-muted">
                                            Devuelve: <?= date('d/m/Y', strtotime($prestamo['fecha_devolucion_estimada'])) ?>
                                        </small>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <a href="prestamos/" class="btn btn-sm btn-outline-light mt-3">
                                    Ver todos los préstamos
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-4">
                        <div class="card bg-gray-800 border-secondary">
                            <div class="card-header bg-gray-700 border-secondary">
                                <h5 class="mb-0"><i class="mdi mdi-book-plus me-2"></i>Últimos Libros Agregados</h5>
                            </div>
                            <div class="card-body">
                                <div class="list-group list-group-flush">
                                    <?php foreach ($ultimos_libros as $libro): ?>
                                    <div class="list-group-item bg-transparent text-light border-secondary">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1"><?= htmlspecialchars($libro['titulo']) ?></h6>
                                            <small class="text-muted">
                                                <?= date('d/m', strtotime($libro['fecha_agregado'])) ?>
                                            </small>
                                        </div>
                                        <p class="mb-1 small text-muted">
                                            Autor: <?= htmlspecialchars($libro['autor']) ?>
                                        </p>
                                        <small>
                                            <span class="badge bg-<?= $libro['disponibles'] > 0 ? 'success' : 'danger' ?>">
                                                <?= $libro['disponibles'] ?> disponibles
                                            </span>
                                        </small>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <a href="libros/" class="btn btn-sm btn-outline-light mt-3">
                                    Ver todos los libros
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/locales/es.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendar');
            var calendar = new FullCalendar.Calendar(calendarEl, {
                themeSystem: 'bootstrap5',
                initialView: 'dayGridMonth',
                locale: 'es',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                events: '../api/get_prestamos.php',
                eventColor: '#378006',
                eventTimeFormat: { 
                    hour: '2-digit',
                    minute: '2-digit',
                    hour12: true
                }
            });
            calendar.render();
        });
    </script>
</body>
</html>