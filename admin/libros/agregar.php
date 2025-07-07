<?php
require_once '../../includes/auth.php';
requireAdmin();
require_once '../../includes/functions.php';

$categorias = $pdo->query("SELECT DISTINCT categoria FROM libros WHERE categoria IS NOT NULL ORDER BY categoria")->fetchAll(PDO::FETCH_COLUMN);
$mensaje = '';

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $titulo = trim($_POST['titulo']);
    $autor = trim($_POST['autor']);
    $isbn = trim($_POST['isbn']);
    $editorial = trim($_POST['editorial']);
    $anio = (int)$_POST['anio'];
    $categoria = trim($_POST['categoria']);
    $ejemplares = (int)$_POST['ejemplares'];
    $descripcion = trim($_POST['descripcion']);
    
    // Validación básica
    if (empty($titulo) || empty($autor) || empty($isbn) || $ejemplares <= 0) {
        $mensaje = "danger:Los campos título, autor, ISBN y ejemplares son obligatorios";
    } else {
        try {
            // Procesar portada si se subió
            $portada = null;
            if (isset($_FILES['portada']) && $_FILES['portada']['error'] == UPLOAD_ERR_OK) {
                $extension = pathinfo($_FILES['portada']['name'], PATHINFO_EXTENSION);
                $nombreUnico = uniqid() . '.' . $extension;
                $rutaDestino = "../../uploads/portadas/" . $nombreUnico;
                
                if (move_uploaded_file($_FILES['portada']['tmp_name'], $rutaDestino)) {
                    $portada = $nombreUnico;
                }
            }
            
            // Insertar libro
            $stmt = $pdo->prepare("INSERT INTO libros 
                                 (titulo, autor, isbn, editorial, anio_publicacion, categoria, 
                                 ejemplares, disponibles, descripcion, portada) 
                                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $titulo, $autor, $isbn, $editorial, $anio, $categoria, 
                $ejemplares, $ejemplares, $descripcion, $portada
            ]);
            
            $mensaje = "success:Libro agregado correctamente";
            
            // Limpiar campos
            $titulo = $autor = $isbn = $editorial = $categoria = $descripcion = '';
            $anio = date('Y');
            $ejemplares = 1;
            
        } catch (PDOException $e) {
            if ($e->errorInfo[1] == 1062) { // Duplicado de ISBN
                $mensaje = "danger:El ISBN ya existe en la base de datos";
            } else {
                $mensaje = "danger:Error al agregar el libro: " . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agregar Libro - Admin</title>
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
                    <h1 class="h2"><i class="mdi mdi-book-plus me-2"></i>Agregar Nuevo Libro</h1>
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
                                <h5 class="mb-0">Información del Libro</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="agregar.php" enctype="multipart/form-data">
                                    <div class="row mb-3">
                                        <div class="col-md-8">
                                            <label for="titulo" class="form-label">Título *</label>
                                            <input type="text" class="form-control bg-gray-700 border-secondary text-black" 
                                                   id="titulo" name="titulo" value="<?= htmlspecialchars($titulo ?? '') ?>" required>
                                        </div>
                                        <div class="col-md-4">
                                            <label for="isbn" class="form-label">ISBN *</label>
                                            <input type="text" class="form-control bg-gray-700 border-secondary text-black" 
                                                   id="isbn" name="isbn" value="<?= htmlspecialchars($isbn ?? '') ?>" required>
                                        </div>
                                    </div>
                                    
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="autor" class="form-label">Autor *</label>
                                            <input type="text" class="form-control bg-gray-700 border-secondary text-black" 
                                                   id="autor" name="autor" value="<?= htmlspecialchars($autor ?? '') ?>" required>
                                        </div>
                                        <div class="col-md-3">
                                            <label for="anio" class="form-label">Año Publicación</label>
                                            <input type="number" class="form-control bg-gray-700 border-secondary text-black" 
                                                   id="anio" name="anio" min="1000" max="<?= date('Y') ?>" 
                                                   value="<?= htmlspecialchars($anio ?? date('Y')) ?>">
                                        </div>
                                        <div class="col-md-3">
                                            <label for="ejemplares" class="form-label">Ejemplares *</label>
                                            <input type="number" class="form-control bg-gray-700 border-secondary text-black" 
                                                   id="ejemplares" name="ejemplares" min="1" value="<?= htmlspecialchars($ejemplares ?? 1) ?>" required>
                                        </div>
                                    </div>
                                    
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="editorial" class="form-label">Editorial</label>
                                            <input type="text" class="form-control bg-gray-700 border-secondary text-black" 
                                                   id="editorial" name="editorial" value="<?= htmlspecialchars($editorial ?? '') ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="categoria" class="form-label">Categoría</label>
                                            <input type="text" class="form-control bg-gray-700 border-secondary text-black" 
                                                   id="categoria" name="categoria" list="categorias" value="<?= htmlspecialchars($categoria ?? '') ?>">
                                            <datalist id="categorias">
                                                <?php foreach ($categorias as $cat): ?>
                                                    <option value="<?= htmlspecialchars($cat) ?>">
                                                <?php endforeach; ?>
                                            </datalist>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="descripcion" class="form-label">Descripción</label>
                                        <textarea class="form-control bg-gray-700 border-secondary text-black" 
                                                  id="descripcion" name="descripcion" rows="3"><?= htmlspecialchars($descripcion ?? '') ?></textarea>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <label for="portada" class="form-label">Portada</label>
                                        <input class="form-control bg-gray-700 border-secondary text-black" 
                                               type="file" id="portada" name="portada" accept="image/*">
                                        <small class="text-muted">Formatos: JPG, PNG. Tamaño máximo: 2MB</small>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary me-2">
                                        <i class="mdi mdi-content-save me-1"></i> Guardar Libro
                                    </button>
                                    <button type="reset" class="btn btn-outline-light">
                                        <i class="mdi mdi-undo me-1"></i> Limpiar
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
                                    <h6 class="mb-2"><i class="mdi mdi-alert-circle me-2"></i>Campos obligatorios</h6>
                                    <p class="small mb-0">Los campos marcados con (*) son obligatorios para registrar un libro.</p>
                                </div>
                                
                                <div class="alert alert-warning text-dark">
                                    <h6 class="mb-2"><i class="mdi mdi-alert me-2"></i>Datos importantes</h6>
                                    <ul class="small mb-0 ps-3">
                                        <li>El ISBN debe ser único para cada libro</li>
                                        <li>Verifica la información antes de guardar</li>
                                        <li>Puedes editar los datos después si es necesario</li>
                                    </ul>
                                </div>
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