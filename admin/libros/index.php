<?php
require_once '../../includes/auth.php';
requireAdmin();
require_once '../../includes/functions.php';

// Paginación
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$por_pagina = 10;
$inicio = ($pagina > 1) ? ($pagina * $por_pagina - $por_pagina) : 0;

// Búsqueda y filtros
$busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';
$categoria = isset($_GET['categoria']) ? $_GET['categoria'] : '';

// Consulta base
$query = "SELECT SQL_CALC_FOUND_ROWS * FROM libros ";
$params = [];

// Aplicar filtros
if (!empty($busqueda)) {
    $query .= "WHERE (titulo LIKE ? OR autor LIKE ? OR isbn LIKE ?) ";
    $params = array_merge($params, ["%$busqueda%", "%$busqueda%", "%$busqueda%"]);
}

if (!empty($categoria)) {
    $query .= (strpos($query, 'WHERE') !== false ? 'AND' : 'WHERE') . " categoria = ? ";
    $params[] = $categoria;
}

// Orden y paginación
$query .= "ORDER BY titulo ASC LIMIT $inicio, $por_pagina";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$libros = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Total de libros para paginación
$total = $pdo->query("SELECT FOUND_ROWS()")->fetchColumn();
$paginas = ceil($total / $por_pagina);

// Obtener categorías únicas para filtro
$categorias = $pdo->query("SELECT DISTINCT categoria FROM libros WHERE categoria IS NOT NULL ORDER BY categoria")->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Libros - Admin</title>
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
                    <h1 class="h2"><i class="mdi mdi-book-multiple me-2"></i>Gestión de Libros</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="agregar.php" class="btn btn-sm btn-primary">
                            <i class="mdi mdi-plus me-1"></i> Agregar Libro
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
                            <div class="col-md-6">
                                <label for="busqueda" class="form-label">Buscar</label>
                                <input type="text" class="form-control bg-gray-700 border-secondary text-black" 
                                       id="busqueda" name="busqueda" value="<?= htmlspecialchars($busqueda) ?>" 
                                       placeholder="Título, autor o ISBN">
                            </div>
                            <div class="col-md-4">
                                <label for="categoria" class="form-label">Categoría</label>
                                <select class="form-select bg-gray-700 border-secondary text-black" id="categoria" name="categoria">
                                    <option value="">Todas las categorías</option>
                                    <?php foreach ($categorias as $cat): ?>
                                        <option value="<?= htmlspecialchars($cat) ?>" <?= $cat == $categoria ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($cat) ?>
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
                
                <!-- Listado de Libros -->
                <div class="card bg-gray-800 border-secondary">
                    <div class="card-header bg-gray-700 border-secondary d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Listado de Libros</h5>
                        <span class="badge bg-primary">Total: <?= $total ?></span>
                    </div>
                    <div class="card-body">
                        <?php if (empty($libros)): ?>
                            <div class="alert alert-info">
                                No se encontraron libros con los filtros aplicados.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-dark table-hover align-middle">
                                    <thead>
                                        <tr>
                                            <th>Portada</th>
                                            <th>Título</th>
                                            <th>Autor</th>
                                            <th>ISBN</th>
                                            <th>Disponibles</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($libros as $libro): ?>
                                        <tr>
                                            <td>
                                                <?php if ($libro['portada']): ?>
                                                    <img src="../../uploads/portadas/<?= htmlspecialchars($libro['portada']) ?>" 
                                                         alt="Portada" class="img-thumbnail" style="width: 60px;">
                                                <?php else: ?>
                                                    <div class="bg-gray-700 d-flex align-items-center justify-content-center" 
                                                         style="width: 60px; height: 80px;">
                                                        <i class="mdi mdi-book-open-variant text-muted" style="font-size: 1.5rem;"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= htmlspecialchars($libro['titulo']) ?></td>
                                            <td><?= htmlspecialchars($libro['autor']) ?></td>
                                            <td><?= htmlspecialchars($libro['isbn']) ?></td>
                                            <td>
                                                <span class="badge bg-<?= $libro['disponibles'] > 0 ? 'success' : 'danger' ?>">
                                                    <?= $libro['disponibles'] ?> / <?= $libro['ejemplares'] ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="d-flex gap-2">
                                                    <a href="editar.php?id=<?= $libro['id'] ?>" 
                                                       class="btn btn-sm btn-outline-warning"
                                                       title="Editar">
                                                        <i class="mdi mdi-pencil"></i>
                                                    </a>
                                                    <a href="eliminar.php?id=<?= $libro['id'] ?>" 
                                                       class="btn btn-sm btn-outline-danger"
                                                       title="Eliminar"
                                                       onclick="return confirm('¿Estás seguro de eliminar este libro?')">
                                                        <i class="mdi mdi-delete"></i>
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