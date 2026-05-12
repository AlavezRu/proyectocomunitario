<!-- src/Shared/Ui/Layout/sidebar.php -->
<?php
require_once __DIR__ . '/../../Infrastructure/Auth/Session.php';
Session::iniciar();
$usuario = Session::obtenerUsuario();
?>

<aside class="sidebar">
    <div class="sidebar-brand">
        <img
            src="/proyectocomunitario/shared/img/logosinfond.png"
            alt="Escudo Municipal de San Bartolo Soyaltepec"
            class="sidebar-logo"
        >
        <span>SysComunal</span>
    </div>

    <!-- Perfil de usuario -->
    <div class="sidebar-profile" style="padding: 0.6rem; border-bottom: 1px solid var(--border); margin-bottom: 0.75rem;">
        <div style="display: flex; align-items: center; gap: 0.6rem;">
            <div style="width: 34px; height: 34px; border-radius: 50%; border: 1px solid var(--border); overflow: hidden; display: flex; align-items: center; justify-content: center; background: #f8fafc; flex-shrink: 0;">
                <img src="/proyectocomunitario/shared/img/logosinfond.png" alt="Escudo" style="width: 28px; height: 28px; object-fit: contain;">
            </div>
            <div style="flex: 1; min-width: 0;">
                <div style="font-weight: 600; font-size: 0.78rem; color: var(--text-main); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                    <?= htmlspecialchars($usuario['nombre'] ?? '') ?>
                </div>
                <div style="font-size: 0.71rem; color: var(--text-muted);">
                    <span style="background: rgba(37, 99, 235, 0.1); color: var(--primary); padding: 0.15rem 0.3rem; border-radius: 2px;">
                        <?= htmlspecialchars($usuario['nombre_rol'] ?? 'Usuario') ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <ul class="nav-menu">
        <li class="nav-item">
            <a href="/proyectocomunitario/public/index.php?page=dashboard" class="nav-link <?= $activePage == 'dashboard' ? 'active' : '' ?>">
                <i class="fas fa-chart-pie"></i>
                <span>Dashboard</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="/proyectocomunitario/public/index.php?page=comuneros" class="nav-link <?= $activePage == 'comuneros' ? 'active' : '' ?>">
                <i class="fas fa-users"></i>
                <span>Comuneros</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="/proyectocomunitario/public/index.php?page=actas" class="nav-link <?= $activePage == 'actas' ? 'active' : '' ?>">
                <i class="fas fa-file-contract"></i>
                <span>Actas de Posesión</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="/proyectocomunitario/public/index.php?page=predial" class="nav-link <?= $activePage == 'predial' ? 'active' : '' ?>">
                <i class="fas fa-money-bill-wave"></i>
                <span>Pago Predial</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="/proyectocomunitario/public/index.php?page=tequios" class="nav-link <?= $activePage == 'tequios' ? 'active' : '' ?>">
                <i class="fas fa-hands-helping"></i>
                <span>Tequios</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="/proyectocomunitario/public/index.php?page=asambleas" class="nav-link <?= $activePage == 'asambleas' ? 'active' : '' ?>">
                <i class="fas fa-users-cog"></i>
                <span>Asambleas</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="/proyectocomunitario/public/index.php?page=mapa" class="nav-link <?= $activePage == 'mapa' ? 'active' : '' ?>">
                <i class="fas fa-map-marked-alt"></i>
                <span>Mapa de Predios</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="/proyectocomunitario/public/index.php?page=reportes" class="nav-link <?= $activePage == 'reportes' ? 'active' : '' ?>">
                <i class="fas fa-file-alt"></i>
                <span>Reportes</span>
            </a>
        </li>
        <?php if (Session::esAdmin()): ?>
        <li class="nav-item">
            <a href="/proyectocomunitario/public/index.php?page=usuarios" class="nav-link <?= $activePage == 'usuarios' ? 'active' : '' ?>">
                <i class="fas fa-user-tie"></i>
                <span>Usuarios</span>
            </a>
        </li>
        <?php endif; ?>
    </ul>

    <!-- Logout -->
    <div style="padding: 0.6rem; border-top: 1px solid var(--border); margin-top: auto;">
        <a href="/proyectocomunitario/public/logout.php" class="nav-link" style="color: var(--danger); background: rgba(239, 68, 68, 0.1); border-radius: 8px; margin: 0;">
            <i class="fas fa-sign-out-alt"></i>
            <span>Cerrar Sesión</span>
        </a>
    </div>
</aside>
