<?php
require_once '../../includes/auth.php';
requireAdmin();
require_once '../../includes/functions.php';

// Paginación
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$por_pagina = 15;
$inicio = ($pagina > 1) ? ($pagina * $por_pagina - $por_pagina) : 0;

// Filtros
$fecha_inicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : '';
$fecha_fin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : '';
$usuario_id = isset($_GET['usuario_id']) ? (int)$_GET['usuario_id'] : 0;

// Consulta base
$query = "SELECT SQL_CALC_FOUND_ROWS d.*, 
          l.titulo as libro_titulo, l.autor as libro_autor,
          u.nombre as usuario_nombre, u.email as usuario_email
          FROM prestamos d
          JOIN libros l ON d.libro_id = l.id
          JOIN usuarios u ON d.usuario_id = u.id
          WHERE d.estado = 'devuelto' ";

$params = [];

// Aplicar filtros
if (!empty($fecha_inicio) && !empty($fecha_fin)) {
    $query .= "AND DATE(d.fecha_devolucion_real) BETWEEN ? AND ? ";
    $params[] = $fecha_inicio;
    $params[] = $fecha_fin;
}

if ($usuario_id > 0) {
    $query .= "AND d.usuario_id = ? ";
    $params[] = $usuario_id;
}

// Orden y paginación
$query .= "ORDER BY d.fecha_devolucion_real DESC LIMIT $inicio, $por_pagina";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$devoluciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Total para paginación
$total = $pdo->query("SELECT FOUND_ROWS()")->fetchColumn();
$paginas = ceil($total / $por_pagina);

// Obtener usuarios para filtro
$usuarios = $pdo->query("SELECT id, nombre, email FROM usuarios ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Devoluciones - Admin</title>
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
                    <h1 class="h2"><i class="mdi mdi-book-arrow-left me-2"></i>Historial de Devoluciones</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="registrar.php" class="btn btn-sm btn-primary">
                            <i class="mdi mdi-plus me-1"></i> Registrar Devolución
                        </a>
                    </div>
                </div>
                
                <!-- Filtros -->
                <div class="card bg-gray-800 border-secondary mb-4">
                    <div class="card-header bg-gray-700 border-secondary">
                        <h5 class="mb-0"><i class="mdi mdi-filter me-2"></i>Filtros</h5>
                    </div>
                    <div class="card-body">
                        <form method="GET" action="index.php" class="row g-3">
                            <div class="col-md-3">
                                <label for="fecha_inicio" class="form-label">Fecha Inicio</label>
                                <input type="date" class="form-control bg-gray-700 border-secondary text-black" 
                                       id="fecha_inicio" name="fecha_inicio" value="<?= htmlspecialchars($fecha_inicio) ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="fecha_fin" class="form-label">Fecha Fin</label>
                                <input type="date" class="form-control bg-gray-700 border-secondary text-black" 
                                       id="fecha_fin" name="fecha_fin" value="<?= htmlspecialchars($fecha_fin) ?>">
                            </div>
                            <div class="col-md-4">
                                <label for="usuario_id" class="form-label">Usuario</label>
                                <select class="form-select bg-gray-700 border-secondary text-black" id="usuario_id" name="usuario_id">
                                    <option value="0">Todos los usuarios</option>
                                    <?php foreach ($usuarios as $usuario): ?>
                                        <option value="<?= $usuario['id'] ?>" <?= $usuario['id'] == $usuario_id ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($usuario['nombre']) ?> (<?= htmlspecialchars($usuario['email']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="mdi mdi-magnify me-1"></i> Filtrar
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Listado de Devoluciones -->
                <div class="card bg-gray-800 border-secondary">
                    <div class="card-header bg-gray-700 border-secondary d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Devoluciones Registradas</h5>
                        <span class="badge bg-primary">Total: <?= $total ?></span>
                    </div>
                    <div class="card-body">
                        <?php if (empty($devoluciones)): ?>
                            <div class="alert alert-info">
                                No se encontraron devoluciones con los filtros aplicados.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-dark table-hover align-middle">
                                    <thead>
                                        <tr>
                                            <th>Libro</th>
                                            <th>Usuario</th>
                                            <th>Préstamo</th>
                                            <th>Dev. Estimada</th>
                                            <th>Dev. Real</th>
                                            <th>Días</th>
                                            <th>Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($devoluciones as $dev): 
                                            $fechaPrestamo = new DateTime($dev['fecha_prestamo']);
                                            $fechaEstimada = new DateTime($dev['fecha_devolucion_estimada']);
                                            $fechaReal = new DateTime($dev['fecha_devolucion_real']);
                                            
                                            $diasAtraso = $fechaReal > $fechaEstimada ? $fechaEstimada->diff($fechaReal)->days : 0;
                                        ?>
                                        <tr>
                                            <td>
                                                <strong><?= htmlspecialchars($dev['libro_titulo']) ?></strong><br>
                                                <small class="text-muted"><?= htmlspecialchars($dev['libro_autor']) ?></small>
                                            </td>
                                            <td>
                                                <?= htmlspecialchars($dev['usuario_nombre']) ?><br>
                                                <small class="text-muted"><?= htmlspecialchars($dev['usuario_email']) ?></small>
                                            </td>
                                            <td><?= date('d/m/y', $fechaPrestamo->getTimestamp()) ?></td>
                                            <td><?= date('d/m/y', $fechaEstimada->getTimestamp()) ?></td>
                                            <td><?= date('d/m/y H:i', $fechaReal->getTimestamp()) ?></td>
                                            <td>
                                                <?php if ($diasAtraso > 0): ?>
                                                    <span class="badge bg-danger">+<?= $diasAtraso ?></span>
                                                <?php else: ?>
                                                    <span class="badge bg-success">A tiempo</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary">Devuelto</span>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Paginación -->
                            <?php if ($paginas > 1): ?>
                            <nav aria-label="Page navigation">
                                <ul class="pagination justify-content-center">
                                    <?php if ($pagina > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link bg-gray-700 border-secondary" 
                                               href="?<?= http_build_query(array_merge($_GET, ['pagina' => $pagina - 1])) ?>">
                                                &laquo;
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = 1; $i <= $paginas; $i++): ?>
                                        <li class="page-item <?= $i == $pagina ? 'active' : '' ?>">
                                            <a class="page-link bg-gray-700 border-secondary" 
                                               href="?<?= http_build_query(array_merge($_GET, ['pagina' => $i])) ?>">
                                                <?= $i ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <?php if ($pagina < $paginas): ?>
                                        <li class="page-item">
                                            <a class="page-link bg-gray-700 border-secondary" 
                                               href="?<?= http_build_query(array_merge($_GET, ['pagina' => $pagina + 1])) ?>">
                                                &raquo;
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Establecer fecha fin como fecha inicio si es mayor
        document.getElementById('fecha_inicio').addEventListener('change', function() {
            const fechaFin = document.getElementById('fecha_fin');
            if (this.value && (!fechaFin.value || this.value > fechaFin.value)) {
                fechaFin.value = this.value;
            }
        });
    </script>
</body>
</html>