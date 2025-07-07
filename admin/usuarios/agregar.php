<?php
require_once '../../includes/auth.php';
requireAdmin();
require_once '../../includes/functions.php';

$mensaje = '';

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = trim($_POST['nombre']);
    $email = trim($_POST['email']);
    $telefono = trim($_POST['telefono']);
    $direccion = trim($_POST['direccion']);
    $rol = $_POST['rol'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validación
    if (empty($nombre) || empty($email) || empty($password) || empty($confirm_password)) {
        $mensaje = "danger:Los campos nombre, email y contraseña son obligatorios";
    } elseif ($password !== $confirm_password) {
        $mensaje = "danger:Las contraseñas no coinciden";
    } elseif (strlen($password) < 6) {
        $mensaje = "danger:La contraseña debe tener al menos 6 caracteres";
    } else {
        try {
            // Verificar si el email ya existe
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->fetch()) {
                $mensaje = "danger:Este correo electrónico ya está registrado";
            } else {
                // Hash de la contraseña
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                
                // Insertar usuario
                $stmt = $pdo->prepare("INSERT INTO usuarios 
                                     (nombre, email, telefono, direccion, password, rol) 
                                     VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$nombre, $email, $telefono, $direccion, $password_hash, $rol]);
                
                $mensaje = "success:Usuario registrado correctamente";
                
                // Limpiar campos
                $nombre = $email = $telefono = $direccion = '';
                $rol = 'cliente';
            }
        } catch (PDOException $e) {
            $mensaje = "danger:Error al registrar el usuario: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agregar Usuario - Admin</title>
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
                    <h1 class="h2"><i class="mdi mdi-account-plus me-2"></i>Agregar Nuevo Usuario</h1>
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
                                <h5 class="mb-0">Información del Usuario</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="agregar.php">
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="nombre" class="form-label">Nombre Completo *</label>
                                            <input type="text" class="form-control bg-gray-700 border-secondary text-black" 
                                                   id="nombre" name="nombre" value="<?= htmlspecialchars($nombre ?? '') ?>" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="email" class="form-label">Correo Electrónico *</label>
                                            <input type="email" class="form-control bg-gray-700 border-secondary text-black" 
                                                   id="email" name="email" value="<?= htmlspecialchars($email ?? '') ?>" required>
                                        </div>
                                    </div>
                                    
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="telefono" class="form-label">Teléfono</label>
                                            <input type="tel" class="form-control bg-gray-700 border-secondary text-black" 
                                                   id="telefono" name="telefono" value="<?= htmlspecialchars($telefono ?? '') ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="rol" class="form-label">Rol *</label>
                                            <select class="form-select bg-gray-700 border-secondary text-black" 
                                                    id="rol" name="rol" required>
                                                <option value="cliente" <?= ($rol ?? 'cliente') == 'cliente' ? 'selected' : '' ?>>Cliente</option>
                                                <option value="admin" <?= ($rol ?? '') == 'admin' ? 'selected' : '' ?>>Administrador</option>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="direccion" class="form-label">Dirección</label>
                                        <textarea class="form-control bg-gray-700 border-secondary text-black" 
                                                  id="direccion" name="direccion" rows="2"><?= htmlspecialchars($direccion ?? '') ?></textarea>
                                    </div>
                                    
                                    <hr class="border-secondary my-4">
                                    
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="password" class="form-label">Contraseña *</label>
                                            <input type="password" class="form-control bg-gray-700 border-secondary text-black" 
                                                   id="password" name="password" required>
                                            <small class="text-muted">Mínimo 6 caracteres</small>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="confirm_password" class="form-label">Confirmar Contraseña *</label>
                                            <input type="password" class="form-control bg-gray-700 border-secondary text-black" 
                                                   id="confirm_password" name="confirm_password" required>
                                        </div>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary me-2">
                                        <i class="mdi mdi-content-save me-1"></i> Guardar Usuario
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
                                    <p class="small mb-0">Los campos marcados con (*) son obligatorios para registrar un usuario.</p>
                                </div>
                                
                                <div class="alert alert-warning text-dark">
                                    <h6 class="mb-2"><i class="mdi mdi-alert me-2"></i>Datos importantes</h6>
                                    <ul class="small mb-0 ps-3">
                                        <li>El email debe ser único para cada usuario</li>
                                        <li>Asigna el rol adecuado a cada usuario</li>
                                        <li>La contraseña se almacena de forma segura</li>
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