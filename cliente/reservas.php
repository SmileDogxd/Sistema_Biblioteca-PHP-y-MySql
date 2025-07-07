<?php
require_once '../includes/auth.php';
requireLogin();
require_once '../includes/functions.php';

if (isAdmin()) {
    header("Location: ../admin/dashboard.php");
    exit();
}

$user = getCurrentUser();
$mensaje = '';

// Procesar nueva reserva
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reservar'])) {
    $libro_id = $_POST['libro_id'];
    $fecha_devolucion = $_POST['fecha_devolucion'];
    
    try {
        $pdo->beginTransaction();
        
        // Verificar disponibilidad
        $stmt = $pdo->prepare("SELECT disponibles FROM libros WHERE id = ? FOR UPDATE");
        $stmt->execute([$libro_id]);
        $libro = $stmt->fetch();
        
        if ($libro && $libro['disponibles'] > 0) {
            // Crear préstamo (el admin debería confirmar)
            $stmt = $pdo->prepare("INSERT INTO prestamos 
                                 (libro_id, usuario_id, fecha_devolucion_estimada, estado) 
                                 VALUES (?, ?, ?, 'prestado')");
            $stmt->execute([$libro_id, $_SESSION['user_id'], $fecha_devolucion]);
            
            // Disminuir disponibilidad
            $stmt = $pdo->prepare("UPDATE libros SET disponibles = disponibles - 1 WHERE id = ?");
            $stmt->execute([$libro_id]);
            
            $pdo->commit();
            $mensaje = "success:¡Libro reservado exitosamente! Fecha de devolución: " . date('d/m/Y', strtotime($fecha_devolucion));
        } else {
            $pdo->rollBack();
            $mensaje = "danger:No hay ejemplares disponibles de este libro en este momento.";
        }
    } catch (PDOException $e) {
        $pdo->rollBack();
        $mensaje = "danger:Error al procesar la reserva: " . $e->getMessage();
    }
}

// Obtener libro específico si se pasa por GET
$libro = null;
if (isset($_GET['libro_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM libros WHERE id = ?");
    $stmt->execute([$_GET['libro_id']]);
    $libro = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Obtener reservas del usuario
$stmt = $pdo->prepare("SELECT r.*, l.titulo, l.autor 
                      FROM prestamos r 
                      JOIN libros l ON r.libro_id = l.id 
                      WHERE r.usuario_id = ? 
                      ORDER BY r.fecha_prestamo DESC");
$stmt->execute([$_SESSION['user_id']]);
$reservas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Reservas - Biblioteca</title>
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
                    <h1 class="h2"><i class="fas fa-calendar-check me-2"></i>Mis Reservas</h1>
                </div>
                
                <?php if ($mensaje): 
                    list($tipo, $texto) = explode(':', $mensaje, 2); ?>
                    <div class="alert alert-<?= $tipo ?>">
                        <?= htmlspecialchars($texto) ?>
                    </div>
                <?php endif; ?>
                
                <!-- Formulario de Reserva -->
                <?php if ($libro): ?>
                <div class="card bg-gray-800 border-secondary mb-4">
                    <div class="card-header bg-gray-700 border-secondary">
                        <h5 class="mb-0">Reservar: <?= htmlspecialchars($libro['titulo']) ?></h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="reservas.php">
                            <input type="hidden" name="libro_id" value="<?= $libro['id'] ?>">
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Libro</label>
                                    <input type="text" class="form-control bg-gray-700 border-secondary text-black" 
                                           value="<?= htmlspecialchars($libro['titulo']) ?>" readonly>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Autor</label>
                                    <input type="text" class="form-control bg-gray-700 border-secondary text-black" 
                                           value="<?= htmlspecialchars($libro['autor']) ?>" readonly>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="fecha_devolucion" class="form-label">Fecha de Devolución</label>
                                    <input type="date" class="form-control bg-gray-700 border-secondary text-black" 
                                           id="fecha_devolucion" name="fecha_devolucion" required
                                           min="<?= date('Y-m-d', strtotime('+1 day')) ?>"
                                           max="<?= date('Y-m-d', strtotime('+1 month')) ?>">
                                    <small class="text-muted">Máximo 30 días de préstamo</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Ejemplares Disponibles</label>
                                    <input type="text" class="form-control bg-gray-700 border-secondary text-black" 
                                           value="<?= $libro['disponibles'] ?>" readonly>
                                </div>
                            </div>
                            
                            <button type="submit" name="reservar" class="btn btn-primary">
                                <i class="fas fa-check me-1"></i> Confirmar Reserva
                            </button>
                            <a href="libros.php" class="btn btn-outline-black ms-2">
                                <i class="fas fa-arrow-left me-1"></i> Volver
                            </a>
                        </form>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Historial de Reservas -->
                <div class="card bg-gray-800 border-secondary">
                    <div class="card-header bg-gray-700 border-secondary">
                        <h5 class="mb-0"><i class="fas fa-history me-2"></i>Historial de Préstamos</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($reservas)): ?>
                            <p class="text-muted">No has realizado ningún préstamo aún.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-dark table-hover">
                                    <thead>
                                        <tr>
                                            <th>Libro</th>
                                            <th>Autor</th>
                                            <th>Fecha Préstamo</th>
                                            <th>Fecha Devolución</th>
                                            <th>Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($reservas as $reserva): 
                                            $hoy = new DateTime();
                                            $fechaDevolucion = new DateTime($reserva['fecha_devolucion_estimada']);
                                            $estado = $reserva['estado'];
                                            
                                            if ($estado == 'prestado' && $fechaDevolucion < $hoy) {
                                                $estado = 'atrasado';
                                            }
                                        ?>
                                        <tr>
                                            <td><?= htmlspecialchars($reserva['titulo']) ?></td>
                                            <td><?= htmlspecialchars($reserva['autor']) ?></td>
                                            <td><?= date('d/m/Y', strtotime($reserva['fecha_prestamo'])) ?></td>
                                            <td><?= date('d/m/Y', strtotime($reserva['fecha_devolucion_estimada'])) ?></td>
                                            <td>
                                                <?php if ($estado == 'prestado'): ?>
                                                    <span class="badge bg-warning text-dark">Prestado</span>
                                                <?php elseif ($estado == 'devuelto'): ?>
                                                    <span class="badge bg-success">Devuelto</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">Atrasado</span>
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
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Establecer fecha mínima (mañana) y máxima (30 días)
        document.addEventListener('DOMContentLoaded', function() {
            const fechaDevolucion = document.getElementById('fecha_devolucion');
            if (fechaDevolucion) {
                const hoy = new Date();
                const manana = new Date(hoy);
                manana.setDate(hoy.getDate() + 1);
                
                const maxFecha = new Date(hoy);
                maxFecha.setDate(hoy.getDate() + 30);
                
                fechaDevolucion.min = manana.toISOString().split('T')[0];
                fechaDevolucion.max = maxFecha.toISOString().split('T')[0];
                fechaDevolucion.value = maxFecha.toISOString().split('T')[0];
            }
        });
    </script>
</body>
</html>