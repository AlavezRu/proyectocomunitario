<!-- shared/layout/header.php -->
<header class="top-header">
    <div class="header-title">
        <?= isset($pageTitle) ? htmlspecialchars($pageTitle) : 'Sistema Comunal' ?>
    </div>
    
    <div class="user-profile-dropdown">
        <button class="user-profile-btn" onclick="toggleDropdown(event)" style="cursor: pointer; display: flex; align-items: center; gap: 0.75rem; text-decoration: none; color: inherit; background: none; border: none; padding: 0;">
            <div class="user-info" style="text-align: right;">
                <div style="font-weight: 600; font-size: 0.875rem;">Administrador</div>
                <div style="color: var(--text-muted); font-size: 0.75rem;">San Bartolo Soyaltepec</div>
            </div>
            <div class="avatar">
                A
            </div>
        </button>
        <div class="dropdown-menu" id="userDropdown" style="display: none; position: absolute; top: 100%; right: 0; background: white; border: 1px solid var(--border); border-radius: 8px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15); min-width: 200px; z-index: 1000; margin-top: 0.5rem;">
            <a href="/proyectocomunitario/public/logout.php" style="display: block; padding: 0.75rem 1rem; color: var(--danger); text-decoration: none; transition: background 150ms; border-bottom: 1px solid var(--border);">
                <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
            </a>
        </div>
    </div>
</header>

<style>
.user-profile-dropdown {
    position: relative;
}

.user-profile-btn:hover {
    opacity: 0.8;
}

.dropdown-menu a:hover {
    background: rgba(239, 68, 68, 0.1);
}
</style>

<script>
function toggleDropdown(e) {
    e.preventDefault();
    const dropdown = document.getElementById('userDropdown');
    dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
}

// Cerrar dropdown al hacer clic fuera
document.addEventListener('click', function(e) {
    const dropdown = document.getElementById('userDropdown');
    const profileBtn = document.querySelector('.user-profile-btn');
    if (!e.target.closest('.user-profile-dropdown')) {
        dropdown.style.display = 'none';
    }
});
</script>
