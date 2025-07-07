<nav class="col-md-3 col-lg-2 d-md-block bg-dark sidebar">
    <div class="position-sticky pt-3">
        <div class="text-center mb-4">
            <h5 class="text-light">Panel de Administración</h5>
            <hr class="border-secondary">
        </div>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>" 
                   href="dashboard.php">
                    <i class="mdi mdi-view-dashboard me-2"></i>Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= strpos($_SERVER['PHP_SELF'], 'libros/') !== false ? 'active' : '' ?>" 
                   href="/biblioteca/admin/libros/">
                    <i class="mdi mdi-book-multiple me-2"></i>Libros
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= strpos($_SERVER['PHP_SELF'], 'prestamos/') !== false ? 'active' : '' ?>" 
                   href="/biblioteca/admin/prestamos/">
                    <i class="mdi mdi-book-arrow-right me-2"></i>Préstamos
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= strpos($_SERVER['PHP_SELF'], 'devoluciones/') !== false ? 'active' : '' ?>" 
                   href="/biblioteca/admin/devoluciones/">
                    <i class="mdi mdi-book-arrow-left me-2"></i>Devoluciones
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= strpos($_SERVER['PHP_SELF'], 'usuarios/') !== false ? 'active' : '' ?>" 
                   href="/biblioteca/admin/usuarios/">
                    <i class="mdi mdi-account-group me-2"></i>Usuarios
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'perfil.php' ? 'active' : '' ?>" 
                   href="/biblioteca/admin/perfil.php">
                    <i class="mdi mdi-account me-2"></i>Perfil
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="/biblioteca/logout.php">
                    <i class="mdi mdi-logout me-2"></i>Cerrar Sesión
                </a>
            </li>
        </ul>
    </div>
</nav>