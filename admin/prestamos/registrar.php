<?php
require_once '../../includes/auth.php';
requireAdmin();
require_once '../../includes/functions.php';

$mensaje = '';

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['registrar_prestamo'])) {
    $libro_id = (int)$_POST['libro_id'];
    $usuario_id = (int)$_POST['usuario_id'];
    $fecha_devolucion = $_POST['fecha_devolucion'];
    $observaciones = trim($_POST['observaciones']);
    
    try {
        $pdo->beginTransaction();
        
        // Verificar disponibilidad del libro
        $stmt = $pdo->prepare("SELECT disponibles FROM libros WHERE id = ? FOR UPDATE");
        $stmt->execute([$libro_id]);
        $libro = $stmt->fetch();
        
        if (!$libro || $libro['disponibles'] <= 0) {
            throw new Exception("El libro seleccionado no está disponible");
        }
        
        // Registrar préstamo
        $stmt = $pdo->prepare("INSERT INTO prestamos 
                             (libro_id, usuario_id, fecha_devolucion_estimada, observaciones, estado) 
                             VALUES (?, ?, ?, ?, 'prestado')");
        $stmt->execute([$libro_id, $usuario_id, $fecha_devolucion, $observaciones]);
        
        // Disminuir disponibilidad del libro
        $stmt = $pdo->prepare("UPDATE libros SET disponibles = disponibles - 1 WHERE id = ?");
        $stmt->execute([$libro_id]);
        
        $pdo->commit();
        $mensaje = "success:Préstamo registrado correctamente. Fecha de devolución: " . date('d/m/Y', strtotime($fecha_devolucion));
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $mensaje = "danger:" . $e->getMessage();
    }
}

// Obtener libros disponibles
$libros_disponibles = $pdo->query("SELECT * FROM libros WHERE disponibles > 0 ORDER BY titulo")->fetchAll(PDO::FETCH_ASSOC);

// Obtener usuarios activos
$usuarios = $pdo->query("SELECT * FROM usuarios WHERE rol = 'cliente' ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Préstamo - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@mdi/font@6.9.96/css/materialdesignicons.min.css" rel="stylesheet">
    <link href="../../assets/css/admin.css" rel="stylesheet">
</head>
<body class="bg-dark text-light">
    <?php include '../../includes/header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include '../sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom border-secondary">
                    <h1 class="h2"><i class="mdi mdi-book-arrow-right me-2"></i>Registrar Nuevo Préstamo</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="index.php" class="btn btn-sm btn-outline-light">
                            <i class="mdi mdi-arrow-left me-1"></i> Volver
                        </a>
                    </div>
                </div>
                
                <?php if ($mensaje): 
                    list($tipo, $texto) = explode(':', $mensaje, 2); ?>
                    <div class="alert alert-<?= $tipo ?>">
                        <?= htmlspecialchars($texto) ?>
                    </div>
                <?php endif; ?>
                
                <div class="row">
                    <div class="col-lg-8">
                        <div class="card bg-gray-800 border-secondary">
                            <div class="card-header bg-gray-700 border-secondary">
                                <h5 class="mb-0">Datos del Préstamo</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="registrar.php">
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="libro_id" class="form-label">Libro *</label>
                                            <select class="form-select bg-gray-700 border-secondary text-black" 
                                                    id="libro_id" name="libro_id" required>
                                                <option value="">Seleccionar libro</option>
                                                <?php foreach ($libros_disponibles as $libro): ?>
                                                    <option value="<?= $libro['id'] ?>">
                                                        <?= htmlspecialchars($libro['titulo']) ?> - 
                                                        <?= htmlspecialchars($libro['autor']) ?> 
                                                        (Disponibles: <?= $libro['disponibles'] ?>)
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="usuario_id" class="form-label">Usuario *</label>
                                            <select class="form-select bg-gray-700 border-secondary text-black" 
                                                    id="usuario_id" name="usuario_id" required>
                                                <option value="">Seleccionar usuario</option>
                                                <?php foreach ($usuarios as $usuario): ?>
                                                    <option value="<?= $usuario['id'] ?>">
                                                        <?= htmlspecialchars($usuario['nombre']) ?> 
                                                        (<?= htmlspecialchars($usuario['email']) ?>)
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="fecha_devolucion" class="form-label">Fecha de Devolución *</label>
                                            <input type="date" class="form-control bg-gray-700 border-secondary text-black" 
                                                   id="fecha_devolucion" name="fecha_devolucion" required
                                                   min="<?= date('Y-m-d', strtotime('+1 day')) ?>"
                                                   max="<?= date('Y-m-d', strtotime('+1 month')) ?>">
                                            <small class="text-muted">Máximo 30 días de préstamo</small>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="observaciones" class="form-label">Observaciones</label>
                                            <textarea class="form-control bg-gray-700 border-secondary text-black" 
                                                      id="observaciones" name="observaciones" rows="1"></textarea>
                                        </div>
                                    </div>
                                    
                                    <button type="submit" name="registrar_prestamo" class="btn btn-primary">
                                        <i class="mdi mdi-check-circle me-1"></i> Registrar Préstamo
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-4">
                        <div class="card bg-gray-800 border-secondary">
                            <div class="card-header bg-gray-700 border-secondary">
                                <h5 class="mb-0"><i class="mdi mdi-information me-2"></i>Instrucciones</h5>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-info">
                                    <h6 class="mb-2"><i class="mdi mdi-alert-circle me-2"></i>Proceso de préstamo</h6>
                                    <ol class="small mb-0 ps-3">
                                        <li>Selecciona el libro y el usuario</li>
                                        <li>Establece la fecha de devolución</li>
                                        <li>Registra observaciones si es necesario</li>
                                        <li>Confirma el préstamo</li>
                                    </ol>
                                </div>
                                
                                <div class="alert alert-warning text-dark">
                                    <h6 class="mb-2"><i class="mdi mdi-alert me-2"></i>Importante</h6>
                                    <ul class="small mb-0 ps-3">
                                        <li>Verifica la disponibilidad del libro</li>
                                        <li>El sistema actualizará automáticamente los ejemplares disponibles</li>
                                        <li>Los préstamos no pueden editarse después de registrarse</li>
                                    </ul>
                                </div>
                                
                                <?php if (empty($libros_disponibles)): ?>
                                <div class="alert alert-danger">
                                    <h6 class="mb-2"><i class="mdi mdi-alert-circle me-2"></i>No hay libros disponibles</h6>
                                    <p class="small mb-0">Todos los ejemplares están actualmente prestados.</p>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
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