<?php
require_once '../includes/auth.php';
requireLogin();
require_once '../includes/functions.php';

if (isAdmin()) {
    header("Location: ../admin/dashboard.php");
    exit();
}

$user = getCurrentUser();

// Obtener préstamos activos del usuario
$stmt = $pdo->prepare("SELECT p.*, l.titulo, l.autor 
                      FROM prestamos p 
                      JOIN libros l ON p.libro_id = l.id 
                      WHERE p.usuario_id = ? AND p.estado = 'prestado'");
$stmt->execute([$_SESSION['user_id']]);
$prestamos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener reservas activas
$stmt = $pdo->prepare("SELECT r.*, l.titulo, l.autor 
                      FROM reservas r 
                      JOIN libros l ON r.libro_id = l.id 
                      WHERE r.usuario_id = ? AND r.estado = 'pendiente'");
$stmt->execute([$_SESSION['user_id']]);
$reservas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Biblioteca</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css" rel="stylesheet">
    <link href="../assets/css/cliente.css" rel="stylesheet">
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
                                <i class="fas fa-home me-2"></i>Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="libros.php">
                                <i class="fas fa-book me-2"></i>Libros
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="reservas.php">
                                <i class="fas fa-calendar-check me-2"></i>Mis Reservas
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="perfil.php">
                                <i class="fas fa-user me-2"></i>Perfil
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i>Cerrar Sesión
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>
            
            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between pt-3 pb-2 mb-3 border-bottom border-secondary">
                    <h1 class="h2">Bienvenido, <?= htmlspecialchars($user['nombre']) ?></h1>
                </div>
                
                <!-- Alertas de Préstamos -->
                <?php foreach ($prestamos as $prestamo): 
                    $fechaDevolucion = new DateTime($prestamo['fecha_devolucion_estimada']);
                    $hoy = new DateTime();
                    $diasRestantes = $hoy->diff($fechaDevolucion)->days;
                    
                    if ($diasRestantes <= 3): ?>
                    <div class="alert alert-warning text-dark">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        El libro <strong><?= htmlspecialchars($prestamo['titulo']) ?></strong> debe ser devuelto el 
                        <?= date('d/m/Y', strtotime($prestamo['fecha_devolucion_estimada'])) ?>.
                    </div>
                    <?php endif; ?>
                <?php endforeach; ?>
                
                <!-- Calendario -->
                <div class="card bg-dark border-secondary mb-4">
                    <div class="card-header bg-gray-800 border-secondary">
                        <h5 class="mb-0"><i class="fas fa-calendar-alt me-2"></i>Mis Préstamos</h5>
                    </div>
                    <div class="card-body">
                        <div id="calendar"></div>
                    </div>
                </div>
                
                <!-- Préstamos Activos -->
                <div class="card bg-dark border-secondary mb-4">
                    <div class="card-header bg-gray-800 border-secondary">
                        <h5 class="mb-0"><i class="fas fa-bookmark me-2"></i>Mis Préstamos Activos</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($prestamos)): ?>
                            <p class="text-muted">No tienes préstamos activos actualmente.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-dark table-hover">
                                    <thead>
                                        <tr>
                                            <th>Libro</th>
                                            <th>Autor</th>
                                            <th>Fecha Préstamo</th>
                                            <th>Fecha Devolución</th>
                                            <th>Días Restantes</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($prestamos as $prestamo): 
                                            $fechaDevolucion = new DateTime($prestamo['fecha_devolucion_estimada']);
                                            $hoy = new DateTime();
                                            $diasRestantes = $hoy->diff($fechaDevolucion)->days;
                                            $diasRestantes = $fechaDevolucion >= $hoy ? $diasRestantes : -$diasRestantes;
                                        ?>
                                        <tr>
                                            <td><?= htmlspecialchars($prestamo['titulo']) ?></td>
                                            <td><?= htmlspecialchars($prestamo['autor']) ?></td>
                                            <td><?= date('d/m/Y', strtotime($prestamo['fecha_prestamo'])) ?></td>
                                            <td><?= date('d/m/Y', strtotime($prestamo['fecha_devolucion_estimada'])) ?></td>
                                            <td>
                                                <?php if ($diasRestantes >= 0): ?>
                                                    <span class="badge bg-success"><?= $diasRestantes ?> días</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger"><?= abs($diasRestantes) ?> días de retraso</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Reservas Pendientes -->
                <?php if (!empty($reservas)): ?>
                <div class="card bg-dark border-secondary">
                    <div class="card-header bg-gray-800 border-secondary">
                        <h5 class="mb-0"><i class="fas fa-clock me-2"></i>Mis Reservas Pendientes</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-dark table-hover">
                                <thead>
                                    <tr>
                                        <th>Libro</th>
                                        <th>Autor</th>
                                        <th>Fecha Reserva</th>
                                        <th>Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($reservas as $reserva): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($reserva['titulo']) ?></td>
                                        <td><?= htmlspecialchars($reserva['autor']) ?></td>
                                        <td><?= date('d/m/Y H:i', strtotime($reserva['fecha_reserva'])) ?></td>
                                        <td><span class="badge bg-warning text-dark">Pendiente</span></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
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
                events: [
                    <?php foreach ($prestamos as $prestamo): ?>
                    {
                        title: '<?= addslashes($prestamo['titulo']) ?>',
                        start: '<?= date('Y-m-d', strtotime($prestamo['fecha_prestamo'])) ?>',
                        end: '<?= date('Y-m-d', strtotime($prestamo['fecha_devolucion_estimada'])) ?>',
                        color: '<?= (new DateTime($prestamo['fecha_devolucion_estimada']) < new DateTime()) ? "#dc3545" : "#28a745" ?>'
                    },
                    <?php endforeach; ?>
                ]
            });
            calendar.render();
        });
    </script>
</body>
</html>