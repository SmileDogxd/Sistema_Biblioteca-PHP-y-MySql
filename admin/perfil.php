<?php
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/functions.php';

$user = getCurrentUser();
$mensaje = '';

// Procesar actualización de perfil
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = trim($_POST['nombre']);
    $email = trim($_POST['email']);
    $telefono = trim($_POST['telefono']);
    $password_actual = $_POST['password_actual'];
    $password_nueva = $_POST['password_nueva'];
    $confirm_password = $_POST['confirm_password'];
    
    try {
        // Verificar contraseña actual si se quiere cambiar
        $cambiarPassword = !empty($password_nueva);
        
        if ($cambiarPassword) {
            if (!password_verify($password_actual, $user['password'])) {
                throw new Exception("La contraseña actual es incorrecta");
            }
            
            if ($password_nueva !== $confirm_password) {
                throw new Exception("Las nuevas contraseñas no coinciden");
            }
            
            if (strlen($password_nueva) < 8) {
                throw new Exception("La nueva contraseña debe tener al menos 8 caracteres");
            }
        }
        
        // Actualizar datos
        if ($cambiarPassword) {
            $password_hash = password_hash($password_nueva, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE usuarios SET nombre = ?, email = ?, telefono = ?, password = ? WHERE id = ?");
            $stmt->execute([$nombre, $email, $telefono, $password_hash, $_SESSION['user_id']]);
        } else {
            $stmt = $pdo->prepare("UPDATE usuarios SET nombre = ?, email = ?, telefono = ? WHERE id = ?");
            $stmt->execute([$nombre, $email, $telefono, $_SESSION['user_id']]);
        }
        
        // Actualizar datos de sesión
        $_SESSION['user_nombre'] = $nombre;
        $_SESSION['user_email'] = $email;
        
        $mensaje = "success:Perfil actualizado correctamente";
        $user = getCurrentUser(); // Refrescar datos
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
    <title>Perfil Admin - Biblioteca</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@mdi/font@6.9.96/css/materialdesignicons.min.css" rel="stylesheet">
    <link href="../assets/css/admin.css" rel="stylesheet">
    <style>
        
    </style>
</head>
<body class="bg-dark text-light">
    <?php include '../includes/header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include 'sidebar.php'; ?>
            
            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between pt-3 pb-2 mb-3 border-bottom border-secondary">
                    <h1 class="h2"><i class="mdi mdi-account me-2"></i>Mi Perfil</h1>
                </div>
                
                <?php if ($mensaje): 
                    list($tipo, $texto) = explode(':', $mensaje, 2); ?>
                    <div class="alert alert-<?= $tipo ?>">
                        <?= htmlspecialchars($texto) ?>
                    </div>
                <?php endif; ?>
                
                <div class="row">
                    <div class="col-lg-8">
                        <div class="card bg-gray-800 border-secondary mb-4">
                            <div class="card-header bg-gray-700 border-secondary">
                                <h5 class="mb-0"><i class="mdi mdi-account-edit me-2"></i>Información Personal</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="perfil.php">
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="nombre" class="form-label">Nombre Completo</label>
                                            <input type="text" class="form-control bg-gray-700 border-secondary text-black" 
                                                   id="nombre" name="nombre" value="<?= htmlspecialchars($user['nombre']) ?>" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="email" class="form-label">Correo Electrónico</label>
                                            <input type="email" class="form-control bg-gray-700 border-secondary text-black" 
                                                   id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="telefono" class="form-label">Teléfono</label>
                                        <input type="tel" class="form-control bg-gray-700 border-secondary text-black" 
                                               id="telefono" name="telefono" value="<?= htmlspecialchars($user['telefono']) ?>">
                                    </div>
                                    
                                    <hr class="border-secondary my-4">
                                    
                                    <h5 class="mb-3"><i class="mdi mdi-lock me-2"></i>Cambiar Contraseña</h5>
                                    <div class="row mb-3">
                                        <div class="col-md-4">
                                            <label for="password_actual" class="form-label">Contraseña Actual</label>
                                            <input type="password" class="form-control bg-gray-700 border-secondary text-black" 
                                                   id="password_actual" name="password_actual">
                                            <small class="text-muted">Dejar en blanco si no deseas cambiar</small>
                                        </div>
                                        <div class="col-md-4">
                                            <label for="password_nueva" class="form-label">Nueva Contraseña</label>
                                            <input type="password" class="form-control bg-gray-700 border-secondary text-black" 
                                                   id="password_nueva" name="password_nueva">
                                        </div>
                                        <div class="col-md-4">
                                            <label for="confirm_password" class="form-label">Confirmar Contraseña</label>
                                            <input type="password" class="form-control bg-gray-700 border-secondary text-black" 
                                                   id="confirm_password" name="confirm_password">
                                        </div>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary">
                                        <i class="mdi mdi-content-save me-1"></i> Guardar Cambios
                                    </button>
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
                                    <h6 class="text-muted">Rol</h6>
                                    <p class="mb-0">
                                        <span class="badge bg-primary">
                                            <i class="mdi mdi-shield-account me-1"></i>
                                            <?= ucfirst($user['rol']) ?>
                                        </span>
                                    </p>
                                </div>
                                
                                <div class="mb-3">
                                    <h6 class="text-muted">Fecha de Registro</h6>
                                    <p><?= date('d/m/Y H:i', strtotime($user['fecha_registro'])) ?></p>
                                </div>
                                
                                <div class="mb-3">
                                    <h6 class="text-muted">Último Acceso</h6>
                                    <p><?= isset($_SESSION['last_login']) ? date('d/m/Y H:i', $_SESSION['last_login']) : 'N/A' ?></p>
                                </div>
                                
                                <hr class="border-secondary my-3">
                                
                                <div class="alert alert-info">
                                    <i class="mdi mdi-alert-circle-outline me-2"></i>
                                    Como administrador, tienes acceso completo al sistema.
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