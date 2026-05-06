<!-- shared/layout/sidebar.php -->
<aside class="sidebar">
    <div class="sidebar-brand">
        <img
            src="/proyectocomunitariov3/shared/img/logosinfond.png"
            alt="Escudo Municipal de San Bartolo Soyaltepec"
            class="sidebar-logo"
        >
        <span>SysComunal</span>
    </div>

    <ul class="nav-menu">
        <li class="nav-item">
            <a href="/proyectocomunitariov3/index.php" class="nav-link <?= $activePage == 'dashboard' ? 'active' : '' ?>">
                <i class="fas fa-chart-pie"></i>
                <span>Dashboard</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="/proyectocomunitariov3/comuneros/index.php" class="nav-link <?= $activePage == 'comuneros' ? 'active' : '' ?>">
                <i class="fas fa-users"></i>
                <span>Comuneros</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="/proyectocomunitariov3/actas_posesion/index.php" class="nav-link <?= $activePage == 'actas' ? 'active' : '' ?>">
                <i class="fas fa-file-contract"></i>
                <span>Actas de Posesión</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="/proyectocomunitariov3/predial/index.php" class="nav-link <?= $activePage == 'predial' ? 'active' : '' ?>">
                <i class="fas fa-money-bill-wave"></i>
                <span>Pago Predial</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="/proyectocomunitariov3/tequios/index.php" class="nav-link <?= $activePage == 'tequios' ? 'active' : '' ?>">
                <i class="fas fa-hands-helping"></i>
                <span>Tequios</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="/proyectocomunitariov3/asambleas/index.php" class="nav-link <?= $activePage == 'asambleas' ? 'active' : '' ?>">
                <i class="fas fa-users-cog"></i>
                <span>Asambleas</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="/proyectocomunitariov3/mapa/index.php" class="nav-link <?= $activePage == 'mapa' ? 'active' : '' ?>">
                <i class="fas fa-map-marked-alt"></i>
                <span>Mapa de Predios</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="/proyectocomunitariov3/reportes/index.php" class="nav-link <?= $activePage == 'reportes' ? 'active' : '' ?>">
                <i class="fas fa-file-alt"></i>
                <span>Reportes</span>
            </a>
        </li>
    </ul>
</aside>
