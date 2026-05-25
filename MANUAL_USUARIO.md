# Manual de Usuario Técnico – SysComunal

## 1. Objetivo de la guía

Esta guía describe el uso operativo del sistema **SysComunal** para personal técnico, administrativos y operativos que deben registrar, consultar, actualizar y reportar información comunitaria.

La guía está orientada a:

- ejecutar tareas de captura y validación de datos;
- seguir flujos de trabajo paso a paso;
- identificar las restricciones del sistema y los puntos de verificación antes de guardar cambios;
- reducir errores por falta de validación o uso incorrecto de módulos.

---

## 2. Requisitos previos y entorno

### 2.1 Requisitos mínimos

- Navegador moderno: Chrome, Edge o Firefox.
- Servidor local con PHP y PostgreSQL configurado (por ejemplo XAMPP o equivalente).
- Acceso a la ruta del proyecto en el servidor.
- Credenciales activas de usuario.

### 2.2 Punto de entrada recomendado

- Página de inicio: `public/login.php`
- Ruta principal del sistema: `public/index.php`
- Ruta de logout: `public/logout.php`

### 2.3 Variables de ruta usadas por la aplicación

- `BASE_URL = /proyectocomunitario`
- `ASSETS_URL = /proyectocomunitario/src/Shared/Ui`
- `PUBLIC_URL = /proyectocomunitario/public`

> Si cambia la ruta base del proyecto, todas las rutas internas de navegación y acciones deben ajustarse en el código.

---

## 3. Flujo de autenticación

### 3.1 Iniciar sesión

1. Abra el navegador y acceda a `public/login.php`.
2. Escriba el **usuario** en el campo **Usuario**.
3. Escriba la **contraseña** en el campo **Contraseña**.
4. Pulse **Iniciar Sesión**.
5. El sistema enviará el formulario a `src/Shared/Application/authenticate.php`.
6. Si la autenticación es correcta, el sistema crea la sesión y redirige al dashboard.

### 3.2 Validaciones que realiza el sistema

- El usuario y la contraseña son obligatorios.
- El usuario debe existir en la tabla `usuario`.
- El usuario debe estar marcado como activo.
- La contraseña se compara de forma directa con `password_hash`.

### 3.3 Errores comunes en login

- **Usuario o contraseña incorrectos**: credenciales inválidas.
- **Usuario desactivado**: el usuario existe pero está inactivo.
- **Respuesta no válida del servidor**: error de backend o de respuesta JSON.

### 3.4 Recuérdame

- Si marca **Recuérdame en este navegador**, el sistema guarda `ultimoUsuario` en `localStorage`.
- Esto solo guarda el nombre de usuario, no la contraseña.

### 3.5 Cerrar sesión

1. Pulse **Cerrar Sesión** en el menú lateral.
2. El sistema ejecuta `public/logout.php` y destruye la sesión.

---

## 4. Navegación del sistema

### 4.1 Menú lateral disponible

El menú se renderiza desde `src/Shared/Ui/Layout/sidebar.php` y contiene:

- Dashboard
- Comuneros
- Actas de Posesión
- Pago Predial
- Tequios
- Asambleas
- Mapa de Predios
- Reportes
- Usuarios (solo administradores)

### 4.2 Comportamiento del router

Las páginas se envían por el parámetro `page` en `public/index.php`.

Ejemplos:

- `page=dashboard`
- `page=comuneros`
- `page=actas`
- `page=predial`
- `page=tequios`
- `page=asambleas`
- `page=mapa`
- `page=reportes`
- `page=usuarios`

### 4.3 Control de acceso por rol

El sistema identifica el rol en sesión mediante `Session::esAdmin()`.

- Si el usuario no es administrador, el router bloquea el acceso a:
  - `usuarios`
  - `usuarios_formulario`

### 4.4 Página activa

La opción activa del menú se resalta visualmente según `$activePage`.

---

## 5. Roles y permisos

### 5.1 Administrador

Un administrador puede:

- gestionar usuarios;
- crear, editar y eliminar tequios y asambleas;
- administrar todo el catálogo operativo;
- acceder a la sección **Usuarios**.

### 5.2 Usuario regular

Un usuario regular puede:

- manejar la operación diaria de comuneros, actas, predial, tequios, asambleas y reportes;
- no puede acceder a **Usuarios**;
- no puede editar ni eliminar usuarios.

### 5.3 Validación de rol en vistas y acciones

El acceso se controla en dos niveles:

1. **UI / navegación**: el router restringe la vista.
2. **Acciones backend**: el módulo de usuarios exige `require_admin.php`.

---

## 6. Dashboard

### 6.1 Propósito

El Dashboard entrega un resumen ejecutivo del estado del sistema.

### 6.2 Datos que resume

- comuneros activos;
- avecindados activos;
- total de habitantes;
- actas registradas;
- asambleas totales y asambleas del año actual;
- tequios totales y tequios del año actual;
- participaciones registradas en asambleas y tequios;
- últimas asambleas y tequios programadas.

### 6.3 Uso técnico recomendado

1. Abra **Dashboard** al inicio de la jornada.
2. Verifique rápidamente si existen valores anómalos.
3. Identifique si se requiere revisar algún módulo en detalle.

---

## 7. Módulo de Comuneros

### 7.1 Alcance del módulo

Este módulo administra la información de comuneros y avecindados. Incluye:

- alta y edición de registros;
- visualización del detalle completo;
- gestión de sucesores;
- reactivación o baja de registros;
- asignación de color para el mapa.

### 7.2 Estructura de datos relevante

Principales tablas y relaciones:

- `comunero`
- `situacion`
- `localidad`
- `sucesor`

### 7.3 Procedimiento paso a paso: registrar un comunero

1. Ingrese a **Comuneros**.
2. Pulse **Nuevo comunero**.
3. Verifique que el **número progresivo** se haya generado automáticamente.
4. Capture el **Nombre Completo**.
5. Seleccione la **Situación Agraria**.
6. Seleccione la **Localidad / Paraje**.
7. Capture **Número R.A.N.** si aplica.
8. Capture **Número de Certificado** si aplica.
9. Capture **Lugar de Residencia Actual**.
10. Capture **Teléfono** en formato de 10 dígitos.
11. Capture **Observaciones** si existen datos adicionales.
12. Seleccione o genere el **Color en Mapa**.
13. Agregue uno o más **Sucesores** si corresponde.
14. Pulse **Guardar Registro**.

### 7.4 Validaciones del formulario

- **Nombre completo**: solo letras y espacios.
- **Teléfono**: exactamente 10 dígitos si se captura.
- **Color**: debe cumplir con formato hexadecimal `#RRGGBB`.
- **Sucesor**: nombre solo letras y espacios.

### 7.5 Ver y editar un comunero

1. Abra **Comuneros**.
2. Localice la fila del comunero.
3. Seleccione **Ver** para revisar el detalle completo.
4. Seleccione **Editar** para modificar datos.
5. Guarde el formulario al terminar.

### 7.6 Reactivar o dar de baja

1. Abra **Comuneros**.
2. Cambie a la pestaña **Inactivos** si el registro está inactivo.
3. Pulse **Reactivar**.
4. Si el comunero debe quedar inactivo, use la acción correspondiente desde la lista activa.

### 7.7 Validación posterior

Después de guardar:

- confirme que el comunero aparece en la lista correspondiente;
- verifique que el color se muestra correctamente en el mapa si se usa posteriormente.

---

## 8. Módulo de Actas de Posesión

### 8.1 Alcance del módulo

Este módulo registra actas de posesión, adjunta documentación y guarda la ubicación espacial del predio.

### 8.2 Datos técnicos relevantes

- La acta se guarda en `acta_posesion`.
- Los documentos se guardan en `archivo`.
- La ubicación geográfica se guarda como geometría PostGIS en `ubicacion`.
- El formato de geometría se convierte desde GeoJSON usando `ST_GeomFromGeoJSON`.

### 8.3 Procedimiento paso a paso: registrar una acta

1. Ingrese a **Actas de Posesión**.
2. Pulse **Registrar Acta**.
3. Seleccione el **Comunero**.
4. Capture la **Fecha de Acta**.
5. Escriba la **Descripción del Predio**.
6. Capture las **Colindancias**.
7. Use el mapa para definir la ubicación del predio.
8. Verifique que la geometría esté cargada en el formulario.
9. Adjunte un archivo en formato **PDF, JPG, JPEG o PNG**.
10. Pulse **Guardar**.

### 8.4 Validaciones del formulario

- El comunero debe existir y estar activo.
- El archivo debe tener una extensión permitida.
- Si se adjunta una geometría, debe ser GeoJSON válido.
- El sistema guarda la ubicación con SRID 4326.

### 8.5 Ver, editar y eliminar

1. En la lista, pulse **Ver** para revisar el acta.
2. Pulse **Editar** si se requiere corrección.
3. Pulse **Eliminar** para borrar la acta y los archivos asociados.

### 8.6 Resultado esperado

- La acta queda visible en la lista.
- El documento queda almacenado en `documentos/actas/`.
- La geometría queda disponible para el módulo de **Mapa de Predios**.

---

## 9. Módulo de Pago Predial

### 9.1 Alcance del módulo

Este módulo registra y revisa el estatus de pago predial por año.

### 9.2 Datos técnicos relevantes

- La consulta principal une `comunero` con `pago_predial` filtrando por año.
- El estatus se calcula con `pagado = TRUE`.
- El monto y la fecha se muestran en la tabla.

### 9.3 Procedimiento paso a paso: registrar un pago

1. Ingrese a **Pago Predial**.
2. Seleccione el **Año** con el selector superior.
3. Busque al comunero por nombre o número progresivo.
4. Verifique el campo **Monto Aportación**.
5. Pulse **Cobrar**.
6. Confirme que el estatus cambie a **Pagado**.
7. Verifique que aparezca la fecha de pago.

### 9.4 Procedimiento paso a paso: deshacer un pago

1. Seleccione el mismo año.
2. Localice el registro con estatus **Pagado**.
3. Pulse el botón **Deshacer**.
4. Confirme la acción.
5. Verifique que vuelva a aparecer como **Adeudo**.

### 9.5 Validaciones operativas

- Un pago no se registra si no existe un comunero activo.
- El año de auditoría debe ser el correcto antes de guardar.
- La búsqueda filtra en tiempo real por nombre y número progresivo.

---

## 10. Módulo de Tequios

### 10.1 Alcance del módulo

Este módulo administra tequios programados, edición y control de asistencia.

### 10.2 Tablas involucradas

- `tequio`
- `cumplimiento_tequio`

### 10.3 Procedimiento paso a paso: programar un tequio

1. Ingrese a **Tequios**.
2. Pulse **Programar Tequio**.
3. Capture la **Descripción de Faena**.
4. Capture **Observaciones** si existen.
5. Seleccione la **Fecha**.
6. Guarde el tequio.

### 10.4 Procedimiento paso a paso: registrar asistencia

1. En la lista, pulse **Lista**.
2. Revise los comuneros disponibles.
3. Marque quienes cumplieron.
4. Guarde los cambios.

### 10.5 Operación de eliminación

- La eliminación está habilitada solo para administradores.
- Se elimina el tequio y su pase de lista asociado.

---

## 11. Módulo de Asambleas

### 11.1 Alcance del módulo

Este módulo administra asambleas y asistencia.

### 11.2 Tablas involucradas

- `asamblea`
- `asistencia_asamblea`

### 11.3 Procedimiento paso a paso: registrar una asamblea

1. Ingrese a **Asambleas**.
2. Pulse **Nueva Asamblea**.
3. Capture la **Descripción / Motivo**.
4. Capture **Observaciones** si aplica.
5. Seleccione la **Fecha**.
6. Guarde el registro.

### 11.4 Procedimiento paso a paso: registrar asistencia

1. En la lista, pulse **Pase Lista**.
2. Revise los comuneros disponibles.
3. Marque los asistentes.
4. Guarde el pase de lista.

### 11.5 Eliminación

- Disponible solo para administradores.
- Elimina la asamblea y su asistencia asociada.

---

## 12. Módulo de Mapa de Predios

### 12.1 Alcance del módulo

El mapa muestra las ubicaciones georreferenciadas de actas registradas.

### 12.2 Funcionalidades técnicas

- renderización de predios con Leaflet;
- filtros por localidades;
- navegación directa a comuneros con controles de salto;
- visualización de información contextual al hacer clic en el marcador.

### 12.3 Procedimiento para revisar un predio

1. Ingrese a **Mapa de Predios**.
2. Seleccione una localidad si desea acotar la vista.
3. Use los controles de salto para ir a un comunero específico.
4. Haga clic sobre el predio para revisar su información.

### 12.4 Validación operativa

La información mostrada depende de que la acta tenga una geometría válida y persistida en `ubicacion`.

---

## 13. Módulo de Reportes

### 13.1 Tipos de reporte disponibles

#### Reportes predefinidos

- Padrón de Comuneros
- Adeudo Predial
- Sin Sucesores
- Por Localidad
- Actas de Posesión
- Asambleas
- Tequios

#### Reportes con filtros

- Comuneros por localidad y situación
- Pagos Prediales por año y localidad
- Asambleas por año
- Tequios por año

### 13.2 Procedimiento paso a paso: generar un reporte personalizado

1. Ingrese a **Reportes**.
2. Seleccione el **Tipo de Reporte**.
3. Si el reporte lo requiere, seleccione **Localidad**.
4. Si aplica, seleccione **Situación**.
5. Seleccione el **Año**.
6. Pulse **Generar Reporte**.
7. Revise el reporte en la nueva pestaña o ventana.

### 13.3 Reportes predefinidos

1. Seleccione el reporte deseado.
2. Pulse el bloque del reporte.
3. El sistema abre `public/reportes_generar.php` en una nueva pestaña.

---

## 14. Módulo de Usuarios

### 14.1 Alcance del módulo

Este módulo solo está disponible para administradores y permite administrar cuentas de acceso al sistema.

### 14.2 Procedimiento paso a paso: crear un usuario

1. Ingrese a **Usuarios**.
2. Pulse **Nuevo Usuario**.
3. Capture **Nombre Completo**.
4. Capture **Usuario**.
5. Seleccione el **Rol**.
6. Marque **Usuario Activo** si debe estar habilitado.
7. Capture **Contraseña**.
8. Confirme la **Contraseña**.
9. Pulse **Guardar Usuario**.

### 14.3 Procedimiento paso a paso: editar un usuario

1. Ingrese a **Usuarios**.
2. Localice el usuario.
3. Pulse **Editar**.
4. Modifique los campos requeridos.
5. Guarde los cambios.

### 14.4 Procedimiento paso a paso: cambiar contraseña

1. Ingrese a **Usuarios**.
2. Localice el usuario.
3. Pulse **Contraseña**.
4. Capture la nueva contraseña.
5. Confirme la nueva contraseña.
6. Envie el formulario.

### 14.5 Procedimiento paso a paso: eliminar un usuario

1. Ingrese a **Usuarios**.
2. Localice el usuario.
3. Pulse **Eliminar**.
4. Revise la confirmación.
5. Confirme la eliminación.

### 14.6 Restricciones importantes

- No se permite eliminar la propia cuenta.
- El rol **consulta** o **consultor** no se puede asignar desde este módulo.
- La contraseña se guarda en texto plano en el campo `password_hash` de la tabla `usuario`.

> Esta implementación debe considerarse una debilidad de seguridad y se recomienda migrar a hash seguro en una fase posterior.

---

## 15. Flujo operativo recomendado

### 15.1 Alta inicial de la comunidad

1. Registrar comuneros.
2. Registrar actas de posesión.
3. Georreferenciar actas.
4. Confirmar predial.
5. Crear tequios y asambleas.
6. Generar reportes base.

### 15.2 Operación diaria

1. Revisar el Dashboard.
2. Actualizar registros de comuneros si hay cambios.
3. Registrar o revisar actas nuevas.
4. Registrar pagos prediales.
5. Capturar asistencia en tequios y asambleas.
6. Generar reportes de seguimiento.

### 15.3 Cierre de mes o año

1. Validar predial por año.
2. Verificar tequios y asambleas cerradas.
3. Revisar reportes finales.
4. Confirmar que no existan datos incompletos.

---

## 16. Validaciones y troubleshooting

### 16.1 Si el acceso es denegado

1. Verifique el rol del usuario en sesión.
2. Confirme que no está intentando entrar a **Usuarios**.
3. Revise si el usuario está activo.

### 16.2 Si falla el login

1. Confirme que el usuario existe.
2. Confirme que el usuario no esté desactivado.
3. Verifique que la contraseña coincida exactamente.
4. Revise si el servidor responde correctamente.

### 16.3 Si una acta no guarda correctamente

1. Verifique que el comunero esté seleccionado.
2. Confirme que la fecha está capturada.
3. Verifique que el archivo tenga extensión permitida.
4. Verifique que la geometría sea válida si aplica.
5. Reintente después de revisar el formulario.

### 16.4 Si el predial no responde como esperado

1. Confirmar el año seleccionado.
2. Revisar el filtro de búsqueda.
3. Confirmar que el comunero esté activo.
4. Volver a cargar la vista.

### 16.5 Si no aparece un predio en el mapa

1. Verifique que la acta tenga geometría guardada.
2. Confirme que el valor de `ubicacion` no sea nulo.
3. Revise la pestaña **Mapa de Predios**.

### 16.6 Si no se pueden generar reportes

1. Confirme que el tipo de reporte esté seleccionado.
2. Verifique los filtros requeridos.
3. Revise que haya datos disponibles en la base.

---

## 17. Checklist rápido de uso

### Antes de guardar

- [ ] El formulario está completo.
- [ ] Los campos obligatorios están llenos.
- [ ] El formato de los datos es correcto.
- [ ] El archivo adjunto cumple el formato permitido.
- [ ] El año o la fecha son correctos.

### Después de guardar

- [ ] El registro aparece en la lista.
- [ ] El estatus o la información se actualizó correctamente.
- [ ] Si aplica, el reporte o el mapa refleja el cambio.

---

## 18. Capturas recomendadas por módulo

| Módulo | Capturas recomendadas |
| --- | --- |
| Login | pantalla de autenticación, mensaje de error si aplica |
| Dashboard | resumen general |
| Comuneros | formulario completo, lista activa/inactiva |
| Actas | formulario con mapa, vista de detalle |
| Predial | selector de año, estado antes/después |
| Tequios | formulario de alta, pase de lista |
| Asambleas | formulario de alta, pase de lista |
| Mapa | vista general, popup de información |
| Reportes | formulario de filtros, reporte generado |
| Usuarios | formulario de alta, modal de contraseña, modal de eliminación |

---

## 19. Notas técnicas y observaciones

- El sistema usa una arquitectura procedural en PHP con scripts UI y scripts de acción separados.
- La validación de negocio se concentra principalmente en las páginas de UI y los scripts de acción.
- El mapa depende de datos geográficos almacenados en PostGIS.
- El módulo de usuarios utiliza `password_hash` sin hash criptográfico, por lo que debe considerarse una mejora de seguridad pendiente.

---

## 20. Resumen ejecutivo

El uso correcto del sistema requiere:

1. autenticarse con credenciales activas;
2. validar que el rol permita la operación;
3. completar los campos requeridos;
4. verificar el estado final del registro;
5. usar los reportes para validar consistencia.

Si necesitas que esta guía se convierta en un manual más breve para impresión o en una guía de entrenamiento con capturas reales, puedo prepararla en una segunda versión.

---

## 15. Mensajes y situaciones comunes

### 15.1 Acceso denegado

Si aparece **Acceso denegado**, el usuario no tiene permisos para esa sección. Verifique su rol.

### 15.2 Usuario o contraseña incorrectos

Revise que el usuario y la contraseña estén correctos y que el usuario no esté desactivado.

### 15.3 No se guarda el acta

Revise que:

- El comunero esté seleccionado.
- La fecha esté ingresada.
- El archivo sea **PDF, JPG, JPEG o PNG**.
- La ubicación esté capturada si aplica.

### 15.4 La fila de predial no aparece

Use el filtro de año o la búsqueda por nombre o número progresivo.

---

## 16. Consejos prácticos

- Mantenga la información de comuneros actualizada para que actas, mapear y reportes reflejen el estado real.
- Use siempre el mapa para georreferenciar actas con precisión.
- Revise regularmente los reportes para verificar que no existan datos faltantes.
- Si un usuario deja de operar, desactívelo en lugar de eliminarlo cuando no sea necesario.

---

## 17. Soporte y mantenimiento

Si se presenta un problema que no pueda resolver con este manual, el siguiente punto de revisión recomendado es:

1. Verificar el acceso del usuario.
2. Confirmar los datos obligatorios en el formulario.
3. Revisar si el archivo y el formato son válidos.
4. Verificar el filtro de año o la búsqueda activa.

Este manual es una guía operativa para el uso diario del sistema. Si se desea, puede extenderse con capturas de pantalla o un procedimiento de instalación del entorno local.
