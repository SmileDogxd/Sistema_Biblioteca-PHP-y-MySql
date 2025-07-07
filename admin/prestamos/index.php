<?php
require_once '../../includes/auth.php';
requireAdmin();
require_once '../../includes/functions.php';

// Paginación
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$por_pagina = 15;
$inicio = ($pagina > 1) ? ($pagina * $por_pagina - $por_pagina) : 0;

// Filtros
$estado = isset($_GET['estado']) ? $_GET['estado'] : '';
$usuario_id = isset($_GET['usuario_id']) ? (int)$_GET['usuario_id'] : 0;
$libro_id = isset($_GET['libro_id']) ? (int)$_GET['libro_id'] : 0;

// Consulta base
$query = "SELECT SQL_CALC_FOUND_ROWS p.*, 
          l.titulo as libro_titulo, l.autor as libro_autor,
          u.nombre as usuario_nombre, u.email as usuario_email
          FROM prestamos p
          JOIN libros l ON p.libro_id = l.id
          JOIN usuarios u ON p.usuario_id = u.id
          WHERE 1=1 ";

$params = [];

// Aplicar filtros
if (!empty($estado)) {
    $query .= "AND p.estado = ? ";
    $params[] = $estado;
}

if ($usuario_id > 0) {
    $query .= "AND p.usuario_id = ? ";
    $params[] = $usuario_id;
}

if ($libro_id > 0) {
    $query .= "AND p.libro_id = ? ";
    $params[] = $libro_id;
}

// Orden y paginación
$query .= "ORDER BY p.fecha_prestamo DESC LIMIT $inicio, $por_pagina";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$prestamos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Total para paginación
$total = $pdo->query("SELECT FOUND_ROWS()")->fetchColumn();
$paginas = ceil($total / $por_pagina);

// Obtener usuarios y libros para filtros
$usuarios = $pdo->query("SELECT id, nombre, email FROM usuarios ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
$libros = $pdo->query("SELECT id, titulo FROM libros ORDER BY titulo")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Préstamos - Admin</title>
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
                    <h1 class="h2"><i class="mdi mdi-book-arrow-right me-2"></i>Gestión de Préstamos</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="registrar.php" class="btn btn-sm btn-primary">
                            <i class="mdi mdi-plus me-1"></i> Nuevo Préstamo
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
                                <label for="estado" class="form-label">Estado</label>
                                <select class="form-select bg-gray-700 border-secondary text-black" id="estado" name="estado">
                                    <option value="">Todos</option>
                                    <option value="prestado" <?= $estado == 'prestado' ? 'selected' : '' ?>>Prestado</option>
                                    <option value="devuelto" <?= $estado == 'devuelto' ? 'selected' : '' ?>>Devuelto</option>
                                    <option value="atrasado" <?= $estado == 'atrasado' ? 'selected' : '' ?>>Atrasado</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="usuario_id" class="form-label">Usuario</label>
                                <select class="form-select bg-gray-700 border-secondary text-black" id="usuario_id" name="usuario_id">
                                    <option value="0">Todos los usuarios</option>
                                    <?php foreach ($usuarios as $usuario): ?>
                                        <option value="<?= $usuario['id'] ?>" <?= $usuario['id'] == $usuario_id ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($usuario['nombre']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="libro_id" class="form-label">Libro</label>
                                <select class="form-select bg-gray-700 border-secondary text-black" id="libro_id" name="libro_id">
                                    <option value="0">Todos los libros</option>
                                    <?php foreach ($libros as $libro): ?>
                                        <option value="<?= $libro['id'] ?>" <?= $libro['id'] == $libro_id ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($libro['titulo']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="mdi mdi-magnify me-1"></i> Filtrar
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Listado de Préstamos -->
                <div class="card bg-gray-800 border-secondary">
                    <div class="card-header bg-gray-700 border-secondary d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Préstamos Registrados</h5>
                        <span class="badge bg-primary">Total: <?= $total ?></span>
                    </div>
                    <div class="card-body">
                        <?php if (empty($prestamos)): ?>
                            <div class="alert alert-info">
                                No se encontraron préstamos con los filtros aplicados.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-dark table-hover align-middle">
                                    <thead>
                                        <tr>
                                            <th>Libro</th>
                                            <th>Usuario</th>
                                            <th>Fecha Préstamo</th>
                                            <th>Fecha Dev.</th>
                                            <th>Estado</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($prestamos as $prestamo): 
                                            $fechaPrestamo = new DateTime($prestamo['fecha_prestamo']);
                                            $fechaEstimada = new DateTime($prestamo['fecha_devolucion_estimada']);
                                            $hoy = new DateTime();
                                            
                                            // Determinar estado real
                                            $estadoReal = $prestamo['estado'];
                                            if ($estadoReal == 'prestado' && $fechaEstimada < $hoy) {
                                                $estadoReal = 'atrasado';
                                            }
                                        ?>
                                        <tr>
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
                                                <?php if ($estadoReal == 'atrasado'): ?>
                                                    <span class="badge bg-danger ms-1">
                                                        <?= $hoy->diff($fechaEstimada)->days ?>d
                                                    </span>
                                                <?php elseif ($estadoReal == 'prestado'): ?>
                                                    <span class="badge bg-success ms-1">
                                                        <?= $hoy->diff($fechaEstimada)->days ?>d
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($estadoReal == 'prestado'): ?>
                                                    <span class="badge bg-warning text-dark">Prestado</span>
                                                <?php elseif ($estadoReal == 'devuelto'): ?>
                                                    <span class="badge bg-secondary">Devuelto</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">Atrasado</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="d-flex gap-2">
                                                    <?php if ($prestamo['estado'] == 'prestado'): ?>
                                                        <a href="../devoluciones/registrar.php?prestamo_id=<?= $prestamo['id'] ?>" 
                                                           class="btn btn-sm btn-outline-success"
                                                           title="Registrar devolución">
                                                            <i class="mdi mdi-book-arrow-left"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                    <a href="#" class="btn btn-sm btn-outline-info"
                                                       title="Ver detalles">
                                                        <i class="mdi mdi-eye"></i>
                                                    </a>
                                                </div>
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
</body>
</html>