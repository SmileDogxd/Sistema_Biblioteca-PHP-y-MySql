<?php
require_once '../../includes/auth.php';
requireAdmin();
require_once '../../includes/functions.php';

$mensaje = '';

// Procesar devolución
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['registrar_devolucion'])) {
    $prestamo_id = (int)$_POST['prestamo_id'];
    $observaciones = trim($_POST['observaciones']);
    
    try {
        $pdo->beginTransaction();
        
        // Obtener datos del préstamo
        $stmt = $pdo->prepare("SELECT libro_id, usuario_id FROM prestamos WHERE id = ? AND estado = 'prestado' FOR UPDATE");
        $stmt->execute([$prestamo_id]);
        $prestamo = $stmt->fetch();
        
        if (!$prestamo) {
            throw new Exception("El préstamo seleccionado no existe o ya fue devuelto");
        }
        
        // Registrar devolución
        $stmt = $pdo->prepare("UPDATE prestamos SET 
                              estado = 'devuelto', 
                              fecha_devolucion_real = NOW(),
                              observaciones = ?
                              WHERE id = ?");
        $stmt->execute([$observaciones, $prestamo_id]);
        
        // Incrementar disponibilidad del libro
        $stmt = $pdo->prepare("UPDATE libros SET disponibles = disponibles + 1 WHERE id = ?");
        $stmt->execute([$prestamo['libro_id']]);
        
        $pdo->commit();
        $mensaje = "success:Devolución registrada correctamente";
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $mensaje = "danger:" . $e->getMessage();
    }
}

// Obtener préstamos activos para selección
$prestamos_activos = $pdo->query("SELECT p.id, p.fecha_prestamo, p.fecha_devolucion_estimada,
                                 l.titulo as libro_titulo, l.autor as libro_autor,
                                 u.nombre as usuario_nombre, u.email as usuario_email
                                 FROM prestamos p
                                 JOIN libros l ON p.libro_id = l.id
                                 JOIN usuarios u ON p.usuario_id = u.id
                                 WHERE p.estado = 'prestado'
                                 ORDER BY p.fecha_devolucion_estimada ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Devolución - Admin</title>
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
                    <h1 class="h2"><i class="mdi mdi-book-arrow-left me-2"></i>Registrar Devolución</h1>
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
                                <h5 class="mb-0">Seleccionar Préstamo</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="registrar.php">
                                    <?php if (empty($prestamos_activos)): ?>
                                        <div class="alert alert-info">
                                            No hay préstamos activos para registrar devolución.
                                        </div>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-dark table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>Seleccionar</th>
                                                        <th>Libro</th>
                                                        <th>Usuario</th>
                                                        <th>Fecha Préstamo</th>
                                                        <th>Fecha Dev.</th>
                                                        <th>Estado</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($prestamos_activos as $prestamo): 
                                                        $fechaPrestamo = new DateTime($prestamo['fecha_prestamo']);
                                                        $fechaEstimada = new DateTime($prestamo['fecha_devolucion_estimada']);
                                                        $hoy = new DateTime();
                                                        $diasRestantes = $hoy->diff($fechaEstimada)->days;
                                                        $diasRestantes = $fechaEstimada >= $hoy ? $diasRestantes : -$diasRestantes;
                                                    ?>
                                                    <tr>
                                                        <td>
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="radio" 
                                                                       name="prestamo_id" id="prestamo_<?= $prestamo['id'] ?>" 
                                                                       value="<?= $prestamo['id'] ?>" required>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <strong><?= htmlspecialchars($prestamo['libro_titulo']) ?></strong><br>
                                                            <small class="text-muted"><?= htmlspecialchars($prestamo['libro_autor']) ?></small>
                                                        </td>
                                                        <td>
                                                            <?= htmlspecialchars($prestamo['usuario_nombre']) ?><br>
                                                            <small class="text-muted"><?= htmlspecialchars($prestamo['usuario_email']) ?></small>
                                                        </td>
                                                        <td><?= date('d/m/y', $fechaPrestamo->getTimestamp()) ?></td>
                                                        <td>
                                                            <?= date('d/m/y', $fechaEstimada->getTimestamp()) ?>
                                                            <?php if ($diasRestantes >= 0): ?>
                                                                <span class="badge bg-success ms-1"><?= $diasRestantes ?>d</span>
                                                            <?php else: ?>
                                                                <span class="badge bg-danger ms-1"><?= abs($diasRestantes) ?>d</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-warning text-dark">Prestado</span>
                                                        </td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="observaciones" class="form-label">Observaciones</label>
                                            <textarea class="form-control bg-gray-700 border-secondary text-black" 
                                                      id="observaciones" name="observaciones" rows="2"></textarea>
                                        </div>
                                        
                                        <button type="submit" name="registrar_devolucion" class="btn btn-primary">
                                            <i class="mdi mdi-check-circle me-1"></i> Registrar Devolución
                                        </button>
                                    <?php endif; ?>
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
                                    <h6 class="mb-2"><i class="mdi mdi-alert-circle me-2"></i>Proceso de devolución</h6>
                                    <ol class="small mb-0 ps-3">
                                        <li>Selecciona el préstamo a devolver</li>
                                        <li>Verifica que el libro esté en buen estado</li>
                                        <li>Ingresa observaciones si es necesario</li>
                                        <li>Confirma la devolución</li>
                                    </ol>
                                </div>
                                
                                <div class="alert alert-warning text-dark">
                                    <h6 class="mb-2"><i class="mdi mdi-alert me-2"></i>Importante</h6>
                                    <ul class="small mb-0 ps-3">
                                        <li>El sistema actualizará automáticamente la disponibilidad del libro</li>
                                        <li>Las devoluciones no se pueden deshacer</li>
                                        <li>Registra daños o observaciones relevantes</li>
                                    </ul>
                                </div>
                                
                                <?php if (!empty($prestamos_activos)): ?>
                                <div class="alert alert-primary">
                                    <h6 class="mb-2"><i class="mdi mdi-clock-alert me-2"></i>Préstamos atrasados</h6>
                                    <p class="small mb-0">
                                        Hay <?= count(array_filter($prestamos_activos, function($p) {
                                            return (new DateTime()) > (new DateTime($p['fecha_devolucion_estimada']));
                                        })) ?> préstamos con devolución atrasada.
                                    </p>
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
</body>
</html>