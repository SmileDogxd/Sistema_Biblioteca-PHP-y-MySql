<?php
require_once '../../includes/auth.php';
requireAdmin();
require_once '../../includes/functions.php';

// Verificar si se recibió ID
if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$id = (int)$_GET['id'];
$mensaje = '';

// Obtener datos del usuario
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$id]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$usuario) {
    header("Location: index.php");
    exit();
}

// Procesar actualización
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = trim($_POST['nombre']);
    $email = trim($_POST['email']);
    $telefono = trim($_POST['telefono']);
    $direccion = trim($_POST['direccion']);
    $rol = $_POST['rol'];
    $password_actual = $_POST['password_actual'];
    $password_nueva = $_POST['password_nueva'];
    $confirm_password = $_POST['confirm_password'];
    
    try {
        // Verificar si el email ya existe (excepto para este usuario)
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ? AND id != ?");
        $stmt->execute([$email, $id]);
        
        if ($stmt->fetch()) {
            throw new Exception("Este correo electrónico ya está registrado por otro usuario");
        }
        
        // Verificar contraseña actual si se quiere cambiar
        $cambiarPassword = !empty($password_nueva);
        
        if ($cambiarPassword) {
            if (!password_verify($password_actual, $usuario['password'])) {
                throw new Exception("La contraseña actual es incorrecta");
            }
            
            if ($password_nueva !== $confirm_password) {
                throw new Exception("Las nuevas contraseñas no coinciden");
            }
            
            if (strlen($password_nueva) < 6) {
                throw new Exception("La nueva contraseña debe tener al menos 6 caracteres");
            }
        }
        
        // Actualizar datos
        if ($cambiarPassword) {
            $password_hash = password_hash($password_nueva, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE usuarios SET 
                                 nombre = ?, email = ?, telefono = ?, direccion = ?, 
                                 rol = ?, password = ? 
                                 WHERE id = ?");
            $stmt->execute([$nombre, $email, $telefono, $direccion, $rol, $password_hash, $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE usuarios SET 
                                 nombre = ?, email = ?, telefono = ?, direccion = ?, 
                                 rol = ? 
                                 WHERE id = ?");
            $stmt->execute([$nombre, $email, $telefono, $direccion, $rol, $id]);
        }
        
        $mensaje = "success:Usuario actualizado correctamente";
        
        // Refrescar datos del usuario
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
        $stmt->execute([$id]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        $mensaje = "danger:" . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Usuario - Admin</title>
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
                    <h1 class="h2"><i class="mdi mdi-account-edit me-2"></i>Editar Usuario</h1>
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
                                <h5 class="mb-0">Editar: <?= htmlspecialchars($usuario['nombre']) ?></h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="editar.php?id=<?= $id ?>">
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="nombre" class="form-label">Nombre Completo *</label>
                                            <input type="text" class="form-control bg-gray-700 border-secondary text-black" 
                                                   id="nombre" name="nombre" value="<?= htmlspecialchars($usuario['nombre']) ?>" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="email" class="form-label">Correo Electrónico *</label>
                                            <input type="email" class="form-control bg-gray-700 border-secondary text-black" 
                                                   id="email" name="email" value="<?= htmlspecialchars($usuario['email']) ?>" required>
                                        </div>
                                    </div>
                                    
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="telefono" class="form-label">Teléfono</label>
                                            <input type="tel" class="form-control bg-gray-700 border-secondary text-black" 
                                                   id="telefono" name="telefono" value="<?= htmlspecialchars($usuario['telefono']) ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="rol" class="form-label">Rol *</label>
                                            <select class="form-select bg-gray-700 border-secondary text-black" 
                                                    id="rol" name="rol" required>
                                                <option value="cliente" <?= $usuario['rol'] == 'cliente' ? 'selected' : '' ?>>Cliente</option>
                                                <option value="admin" <?= $usuario['rol'] == 'admin' ? 'selected' : '' ?>>Administrador</option>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="direccion" class="form-label">Dirección</label>
                                        <textarea class="form-control bg-gray-700 border-secondary text-black" 
                                                  id="direccion" name="direccion" rows="2"><?= htmlspecialchars($usuario['direccion']) ?></textarea>
                                    </div>
                                    
                                    <hr class="border-secondary my-4">
                                    
                                    <h5 class="mb-3"><i class="mdi mdi-lock me-2"></i>Cambiar Contraseña</h5>
                                    <div class="row mb-3">
                                        <div class="col-md-4">
                                            <label for="password_actual" class="form-label">Contraseña Actual</label>
                                            <input type="password" class="form-control bg-gray-700 border-secondary text-black" 
                                                   id="password_actual" name="password_actual">
                                            <small class="text-muted">Obligatorio para cambiar</small>
                                        </div>
                                        <div class="col-md-4">
                                            <label for="password_nueva" class="form-label">Nueva Contraseña</label>
                                            <input type="password" class="form-control bg-gray-700 border-secondary text-black" 
                                                   id="password_nueva" name="password_nueva">
                                        </div>
                                        <div class="col-md-4">
                                            <label for="confirm_password" class="form-label">Confirmar</label>
                                            <input type="password" class="form-control bg-gray-700 border-secondary text-black" 
                                                   id="confirm_password" name="confirm_password">
                                        </div>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary me-2">
                                        <i class="mdi mdi-content-save me-1"></i> Guardar Cambios
                                    </button>
                                    <a href="index.php" class="btn btn-outline-light">
                                        <i class="mdi mdi-close me-1"></i> Cancelar
                                    </a>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-4">
                        <div class="card bg-gray-800 border-secondary">
                            <div class="card-header bg-gray-700 border-secondary">
                                <h5 class="mb-0"><i class="mdi mdi-information me-2"></i>Información de Cuenta</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <h6 class="text-muted">Fecha de Registro</h6>
                                    <p><?= date('d/m/Y H:i', strtotime($usuario['fecha_registro'])) ?></p>
                                </div>
                                
                                <div class="mb-3">
                                    <h6 class="text-muted">Último Acceso</h6>
                                    <p><?= isset($_SESSION['last_login']) ? date('d/m/Y H:i', $_SESSION['last_login']) : 'N/A' ?></p>
                                </div>
                                
                                <hr class="border-secondary my-3">
                                
                                <div class="alert alert-info">
                                    <h6 class="mb-2"><i class="mdi mdi-alert-circle-outline me-2"></i>Actualizar contraseña</h6>
                                    <p class="small mb-0">Solo completa los campos de contraseña si deseas cambiarla.</p>
                                </div>
                                
                                <div class="alert alert-warning text-dark">
                                    <h6 class="mb-2"><i class="mdi mdi-alert me-2"></i>Precaución</h6>
                                    <ul class="small mb-0 ps-3">
                                        <li>No cambies el rol sin necesidad</li>
                                        <li>Verifica los datos antes de guardar</li>
                                        <li>Las contraseñas se almacenan de forma segura</li>
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