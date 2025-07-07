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

// Procesar actualización de perfil
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = trim($_POST['nombre']);
    $email = trim($_POST['email']);
    $telefono = trim($_POST['telefono']);
    $direccion = trim($_POST['direccion']);
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
            
            if (strlen($password_nueva) < 6) {
                throw new Exception("La nueva contraseña debe tener al menos 6 caracteres");
            }
        }
        
        // Actualizar datos
        if ($cambiarPassword) {
            $password_hash = password_hash($password_nueva, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE usuarios SET nombre = ?, email = ?, telefono = ?, direccion = ?, password = ? WHERE id = ?");
            $stmt->execute([$nombre, $email, $telefono, $direccion, $password_hash, $_SESSION['user_id']]);
        } else {
            $stmt = $pdo->prepare("UPDATE usuarios SET nombre = ?, email = ?, telefono = ?, direccion = ? WHERE id = ?");
            $stmt->execute([$nombre, $email, $telefono, $direccion, $_SESSION['user_id']]);
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
    <title>Mi Perfil - Biblioteca</title>
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
                    <h1 class="h2"><i class="fas fa-user me-2"></i>Mi Perfil</h1>
                </div>
                
                <?php if ($mensaje): 
                    list($tipo, $texto) = explode(':', $mensaje, 2); ?>
                    <div class="alert alert-<?= $tipo ?>">
                        <?= htmlspecialchars($texto) ?>
                    </div>
                <?php endif; ?>
                
                <div class="card bg-gray-800 border-secondary">
                    <div class="card-header bg-gray-700 border-secondary">
                        <h5 class="mb-0">Información Personal</h5>
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
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="telefono" class="form-label">Teléfono</label>
                                    <input type="tel" class="form-control bg-gray-700 border-secondary text-black" 
                                           id="telefono" name="telefono" value="<?= htmlspecialchars($user['telefono']) ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="direccion" class="form-label">Dirección</label>
                                    <input type="text" class="form-control bg-gray-700 border-secondary text-black" 
                                           id="direccion" name="direccion" value="<?= htmlspecialchars($user['direccion']) ?>">
                                </div>
                            </div>
                            
                            <hr class="border-secondary my-4">
                            
                            <h5 class="mb-3">Cambiar Contraseña</h5>
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
                                <i class="fas fa-save me-1"></i> Guardar Cambios
                            </button>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>