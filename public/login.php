<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SysComunal | Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #2563eb;
            --secondary: #10b981;
            --danger: #ef4444;
            --border: #e2e8f0;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --transition-fast: 150ms cubic-bezier(0.4, 0, 0.2, 1);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(160deg, #0f172a 0%, #1e3a5f 50%, #1e293b 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            overflow: hidden;
        }

        /* Logos laterales fijos */
        .logo-side {
            pointer-events: none;
            position: fixed;
            top: 50%;
            transform: translateY(-50%);
            z-index: 0;
            opacity: 0.18;
        }
        .logo-side img { width: 480px; height: 480px; object-fit: contain; display: block; }
        .logo-side--left  { left: 5%; }
        .logo-side--right { right: 5%; }

        /* Contenedor del formulario */
        .login-container {
            width: 100%;
            max-width: 440px;
            position: relative;
            z-index: 1;
        }

        /* Cabecera */
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
            color: white;
        }
        .login-header h1 { font-size: 1.8rem; font-weight: 700; margin-bottom: 0.4rem; }
        .login-header p  { font-size: 0.9rem; opacity: 0.75; }

        /* Tarjeta glassmorphism */
        .login-card {
            background: rgba(255, 255, 255, 0.94);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.5);
            border-radius: 16px;
            padding: 2.5rem;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.45);
        }

        /* Formulario */
        .form-group { margin-bottom: 1.4rem; }

        label {
            display: block;
            font-weight: 600;
            margin-bottom: 0.45rem;
            font-size: 0.875rem;
            color: var(--text-main);
        }

        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 0.875rem 1rem;
            border: 2px solid var(--border);
            border-radius: 8px;
            font-size: 0.95rem;
            transition: var(--transition-fast);
            background: #fff;
            color: var(--text-main);
        }
        input[type="text"]:focus,
        input[type="password"]:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
        }
        input::placeholder { color: #94a3b8; }

        .form-remember {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
            font-size: 0.875rem;
            color: var(--text-main);
        }
        .form-remember input[type="checkbox"] { width: 16px; height: 16px; cursor: pointer; }
        .form-remember label { margin: 0; cursor: pointer; font-weight: 500; }

        button[type="submit"] {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, var(--primary) 0%, #1d4ed8 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition-fast);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        }
        button[type="submit"]:hover  { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(37,99,235,0.35); }
        button[type="submit"]:active { transform: translateY(0); }

        /* Alertas */
        .alert {
            padding: 0.875rem 1rem;
            border-radius: 8px;
            margin-bottom: 1.25rem;
            border-left: 4px solid;
            font-size: 0.9rem;
            display: none;
        }
        .alert i { margin-right: 0.5rem; }
        .alert-success { background: rgba(16,185,129,0.1); color: #065f46; border-color: var(--secondary); display: block; }
        .alert-error   { background: rgba(239,68,68,0.1);  color: #7f1d1d; border-color: var(--danger);    display: block; }

        /* Spinner */
        .spinner {
            display: inline-block;
            width: 18px; height: 18px;
            border: 3px solid rgba(255,255,255,0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.7s linear infinite;
            margin-right: 0.5rem;
            vertical-align: middle;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* Footer */
        .login-footer {
            text-align: center;
            margin-top: 1.5rem;
            padding-top: 1.25rem;
            border-top: 1px solid var(--border);
            font-size: 0.8rem;
            color: var(--text-muted);
        }
    </style>
</head>
<body>
    <!-- Logos institucionales laterales -->
    <div class="logo-side logo-side--left" aria-hidden="true">
        <img src="/proyectocomunitario/shared/img/logosinfond.png" alt="">
    </div>
    <div class="logo-side logo-side--right" aria-hidden="true">
        <img src="/proyectocomunitario/shared/img/logosinfond.png" alt="">
    </div>

    <div class="login-container">
        <div class="login-header">
            <h1>SysComunal</h1>
            <p>Sistema de Gestión Comunal · San Bartolo Soyaltepec</p>
        </div>

        <div class="login-card">
            <div id="alert" class="alert"></div>

            <form id="loginForm">
                <div class="form-group">
                    <label for="nombre_usuario">Usuario</label>
                    <input 
                        type="text" 
                        id="nombre_usuario" 
                        name="nombre_usuario" 
                        placeholder="Ingrese su usuario"
                        autofocus
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="contrasena">Contraseña</label>
                    <input 
                        type="password" 
                        id="contrasena" 
                        name="contrasena" 
                        placeholder="Ingrese su contraseña"
                        required
                    >
                </div>

                <div class="form-remember">
                    <input type="checkbox" id="recordar" name="recordar">
                    <label for="recordar">Recuérdame en este navegador</label>
                </div>

                <button type="submit">
                    <span class="spinner" id="spinner" style="display: none;"></span>
                    <span id="btnText">Iniciar Sesión</span>
                </button>
            </form>

            <div class="login-footer">
                <p>SysComunal © 2026 - Sistema de Gestión para Comunidades</p>
            </div>
        </div>
    </div>

    <script>
        const loginForm = document.getElementById('loginForm');
        const alertDiv = document.getElementById('alert');
        const spinner = document.getElementById('spinner');
        const btnText = document.getElementById('btnText');

        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            const usuario = document.getElementById('nombre_usuario').value;
            const contrasena = document.getElementById('contrasena').value;

            // Mostrar spinner
            spinner.style.display = 'inline-block';
            btnText.textContent = 'Verificando...';
            loginForm.querySelector('button').disabled = true;
            alertDiv.className = 'alert';
            alertDiv.style.display = 'none';

            try {
                const formData = new FormData();
                formData.append('nombre_usuario', usuario);
                formData.append('contrasena', contrasena);

                const response = await fetch('/proyectocomunitario/src/Shared/Application/authenticate.php', {
                    method: 'POST',
                    body: formData
                });

                const rawResponse = await response.text();
                let data;

                try {
                    data = JSON.parse(rawResponse);
                } catch (parseError) {
                    throw new Error(rawResponse || 'Respuesta no válida del servidor');
                }

                if (data.success) {
                    alertDiv.className = 'alert alert-success';
                    alertDiv.innerHTML = `
                        <i class="fas fa-check-circle"></i>
                        Autenticación exitosa. Redirigiendo...
                    `;
                    alertDiv.style.display = 'block';

                    // Guardar preferencia de recordar
                    if (document.getElementById('recordar').checked) {
                        localStorage.setItem('ultimoUsuario', usuario);
                    }

                    // Redirigir después de 800ms
                    setTimeout(() => {
                        window.location.href = data.redirect;
                    }, 800);
                } else {
                    alertDiv.className = 'alert alert-error';
                    alertDiv.innerHTML = `
                        <i class="fas fa-exclamation-circle"></i>
                        ${data.error || 'Error en la autenticación'}
                    `;
                    alertDiv.style.display = 'block';

                    spinner.style.display = 'none';
                    btnText.textContent = 'Iniciar Sesión';
                    loginForm.querySelector('button').disabled = false;
                }
            } catch (error) {
                alertDiv.className = 'alert alert-error';
                alertDiv.innerHTML = `
                    <i class="fas fa-exclamation-circle"></i>
                    ${error.message || 'Error de conexión. Intente nuevamente.'}
                `;
                alertDiv.style.display = 'block';

                spinner.style.display = 'none';
                btnText.textContent = 'Iniciar Sesión';
                loginForm.querySelector('button').disabled = false;
            }
        });

        // Cargar último usuario
        window.addEventListener('load', () => {
            const ultimoUsuario = localStorage.getItem('ultimoUsuario');
            if (ultimoUsuario) {
                document.getElementById('nombre_usuario').value = ultimoUsuario;
                document.getElementById('recordar').checked = true;
            }
        });

        // Mostrar mensaje de logout si existe
        const params = new URLSearchParams(window.location.search);
        if (params.get('msg') === 'logout') {
            alertDiv.className = 'alert alert-success';
            alertDiv.innerHTML = `
                <i class="fas fa-sign-out-alt"></i>
                Sesión cerrada correctamente
            `;
            alertDiv.style.display = 'block';
        }
    </script>
</body>
</html>
