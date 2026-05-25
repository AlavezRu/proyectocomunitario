<!-- src/Shared/Ui/Layout/header.php -->
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
            <div class="avatar" style="background: #f8fafc; border: 2px solid var(--border);">
                <img src="/proyectocomunitario/shared/img/logosinfond.png" alt="Escudo" style="width: 26px; height: 26px; object-fit: contain;">
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
    if (!e.target.closest('.user-profile-dropdown')) {
        dropdown.style.display = 'none';
    }
});

// ── Title Case global ──────────────────────────────────────────────────────
// Usa event delegation para cubrir también inputs creados dinámicamente
// (ej. sucesores agregados en tiempo real). Se excluyen los campos con
// data-no-titlecase y los tipos que no son texto libre.
(function () {
    var TIPOS_EXCLUIDOS = ['password','email','number','date','hidden','search','url','tel'];

    function toTitleCase(str) {
        return str.split(' ').map(function (word) {
            if (word.length === 0) return word;
            return word.charAt(0).toUpperCase() + word.slice(1).toLowerCase();
        }).join(' ');
    }

    function debeAplicar(el) {
        if (!el || !el.tagName) return false;
        var tag = el.tagName.toUpperCase();
        if (tag !== 'INPUT' && tag !== 'TEXTAREA') return false;
        if (tag === 'INPUT' && TIPOS_EXCLUIDOS.indexOf(el.type) !== -1) return false;
        if (el.dataset.noTitlecase !== undefined) return false;
        if (!el.closest('form')) return false;
        return true;
    }

    function aplicar(el) {
        var start = el.selectionStart;
        var end   = el.selectionEnd;
        var nuevo = toTitleCase(el.value);
        if (el.value !== nuevo) {
            el.value = nuevo;
            if (el.setSelectionRange) el.setSelectionRange(start, end);
        }
    }

    // input: dispara mientras el usuario escribe (bubbles → delegation funciona)
    document.addEventListener('input', function (e) {
        if (debeAplicar(e.target)) aplicar(e.target);
    });

    // blur no burbujea, se usa capture para atrapar el evento
    document.addEventListener('blur', function (e) {
        if (debeAplicar(e.target)) {
            e.target.value = toTitleCase(e.target.value.trim());
        }
    }, true);
})();
</script>
