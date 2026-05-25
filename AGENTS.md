# SysComunal - Agent Guidelines

**SysComunal** is a community management system for the Municipality of San Bartolo Soyaltepec built with PHP, PostgreSQL, and modern CSS.

## Project Overview

**Purpose**: Manage community members (comuneros), assemblies (asambleas), community work services (tequios), land records (predial), and internal reports.

**Tech Stack**:
- Backend: PHP 7.4+ with PostgreSQL (pg_* functions)
- Frontend: HTML5, CSS3 (glassmorphism design), JavaScript (Fetch API)
- Infrastructure: XAMPP (Apache + PHP + PostgreSQL)
- Language: Spanish

**Key Domain**:
- **Comuneros**: Community members with progressive numbers, situations (estado), locations
- **Asambleas**: Assemblies with attendance tracking (pase de lista)
- **Tequios**: Community work services with task lists and attendance
- **Actas de Posesión**: Position/appointment certificates
- **Predial**: Land/property payment tracking
- **Mapa Parcelario**: Visual parcel map representation with color coding
- **Reportes**: Data reports

## Architecture Patterns

### Directory Structure

```
src/
  ├─ [Module]/
  │  ├─ Ui/              # Presentation layer (forms, lists, views)
  │  ├─ Application/     # Business logic (create, edit, delete actions)
  │  └─ Infrastructure/  # Data access and system services
  ├─ Shared/
  │  ├─ Infrastructure/
  │  │  ├─ Database/     # Database connection
  │  │  └─ Auth/         # Session and authentication
  │  └─ Ui/Layout/       # Reusable UI components
public/               # Legacy entry points
db/conexion.php       # Legacy connection file (deprecated—use src/Shared)
shared/
  ├─ css/style.css    # Global styles
  ├─ layout/          # Reusable layout partials
  └─ img/             # Assets
setup/                # Database schema and admin setup scripts
```

### Layers

- **Ui** (`src/*/Ui/*.php`): Page rendering, forms, list views. Includes minimal PHP logic for data display.
- **Application** (`src/*/Application/*.php`): Business logic, validation, state changes. Handles AJAX requests (`Content-Type: application/json`).
- **Infrastructure**:
  - **Database** (`Connection.php`): PostgreSQL connection setup
  - **Auth** (`Session.php`, `check_auth.php`, `require_admin.php`): Session management and role checks
  - **Shared Utils**: Reusable functions across modules

### Key Conventions

#### Database Interactions
- Use `pg_query_params()` for parameterized queries (always, to prevent SQL injection)
- Begin transactions with `pg_query($conexion, "BEGIN")` for multi-step operations
- Always validate input types: `(int)$_POST['id']`, `trim($_POST['text'])`
- Check `pg_num_rows()` before accessing results

```php
$q = pg_query_params($conexion, "SELECT * FROM table WHERE id = $1", [$id]);
if (pg_num_rows($q) > 0) {
    $row = pg_fetch_assoc($q);
}
```

#### Form Validation
- Validate on **both** client (JavaScript) and server (PHP)
- Use regex for format validation:
  - Names: `/^[\p{L}]+(?:[ ]+[\p{L}]+)*$/u` (letters and spaces only)
  - Phone: `/^\d{10}$/` (10 digits)
  - Numbers: `/^\d+$/`
- Throw exceptions with user-friendly messages (prefix with emoji like `⚠️`, `✅`)
- Return JSON from Application layer: `{"success": true, "message": "...", "data": {...}}`

#### AJAX Requests
- Start output buffering in Application files: `ob_start();`
- Set header: `header('Content-Type: application/json; charset=utf-8');`
- Detect AJAX: `$isAjax = !empty($_POST['ajax']);`
- Always wrap in try-catch; return errors as JSON

```php
ob_start();
header('Content-Type: application/json; charset=utf-8');
try {
    // Logic
    echo json_encode(['success' => true, 'message' => '✅ Operación exitosa']);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => '❌ ' . $e->getMessage()]);
}
```

#### Authentication & Authorization
- Use `Session::iniciar()` to start sessions
- Check role with `require_admin()` for admin-only actions
- Always check `$_SESSION['rol']` before returning delete/update icons or performing sensitive operations
- Public pages (login) skip auth checks

#### CSS & UI
- Modern glassmorphism design with CSS variables (`--primary`, `--surface-glass`, etc.)
- Use `.glass-panel` class for translucent card containers
- Responsive grid layout: `grid-template-columns: repeat(auto-fit, minmax(..., 1fr))`
- Modal dialogs for confirmations and forms
- Font Awesome 6.4 icons: `<i class="fas fa-icon-name"></i>`

## Common Tasks

### Adding a New CRUD Module
1. Create `src/NewModule/{Ui,Application,Infrastructure}/` structure
2. **Ui/index.php**: List page with table, add/edit/delete buttons
3. **Ui/formulario.php**: Form for create/edit
4. **Application/acciones.php**: Handle nuevo/editar/eliminar actions (JSON response)
5. Add links in sidebar (`src/Shared/Ui/Layout/sidebar.php`)
6. Add database table in `setup/` SQL migrations

### Form Validation Workflow
1. Add HTML5 validation: `required`, `maxlength`, `pattern`
2. Add JavaScript validation before form submission
3. Add server-side validation in Application layer
4. Throw exceptions with clear messages
5. Return JSON with success/error to frontend
6. Display notifications via modal or toast

### Fixing Database Issues
- Connection errors: Check PostgreSQL is running, credentials in `Connection.php`
- Query errors: Use `pg_last_error($conexion)` to debug
- Transaction failures: Wrap in try-catch, use `pg_query(..., "ROLLBACK")` on exception

## Debugging & Tools

**Local Development**:
- Start XAMPP: Apache + PostgreSQL services
- Access via `http://localhost/proyectocomunitario/public/`
- Check browser console (F12) for JavaScript errors
- Check XAMPP error logs for PHP errors

**Database**:
- PostgreSQL 12+ running on port 5432
- Database: `soyaltepecdb`, user: `postgres`
- View schema: `setup/ver_estructura.php`
- Admin users created via `setup/crear_usuarios.sql`

**Browser DevTools**:
- Network tab: Inspect AJAX requests/responses
- Console: JavaScript errors and validation logs
- Sources: Debug client-side form handling

## Recent Work

- Added form validation and UI confirmations
- Implemented polygon (parcel) selection requirement for some forms
- Fixed minor errors across modules
- Added admin checks for sensitive actions (delete, update)

## Notes for AI Agents

- **Error Handling**: Always catch exceptions and return user-friendly messages
- **Security**: Never trust user input; validate and sanitize always
- **Performance**: Use `pg_query_params()` with indexed queries
- **Testing**: Test forms with both valid and invalid inputs, including edge cases
- **Git Workflow**: Feature branches, PRs, test before merging to main
- **Rollback**: If adding features, ensure previous functionality isn't broken

---

**See Also**: Git history for recent patterns and PR feedback.
