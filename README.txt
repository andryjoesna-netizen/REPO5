APLICATIVO WEB - GESTIÓN DE PEDIDOS DE HAMBURGUESERÍA
====================================================

REQUISITOS
- PHP 8.0 o superior con la extensión mysqli habilitada.
- MySQL/MariaDB.
- La base de datos andry_hamburgueseria debe existir y el usuario debe tener permisos para crear tablas, consultar, insertar, actualizar y eliminar registros.

ARCHIVOS
- index.php: formulario, listado, filtros, cambio de estado y eliminación de pedidos.
- config.php: conexión a MySQL y creación automática de la tabla pedidos.
- README.txt: estas instrucciones.

INSTALACIÓN
1. Descomprime hamburgueseria_app.zip.
2. Sube index.php y config.php a una carpeta del servidor web que ejecute PHP.
3. Revisa las credenciales en config.php.
4. Abre index.php en el navegador.
5. Al conectarse, la aplicación crea la tabla pedidos si no existe.

FUNCIONES
- Registrar pedidos con dirección y cantidades de hamburguesas sencillas, dobles y mixtas.
- Estado inicial: preparando.
- Cambiar el estado a preparando o despachado.
- Filtrar pedidos por estado.
- Eliminar pedidos con confirmación.
- Validación de datos, consultas preparadas y token CSRF para las acciones del formulario.

IMPORTANTE
La aplicación crea la tabla, pero no crea automáticamente la base de datos. La base de datos indicada debe estar creada en el panel del proveedor.
Las credenciales están en config.php porque fueron suministradas para este proyecto. No publiques este archivo en repositorios públicos. Si la contraseña ya fue compartida en un lugar público, cámbiala en el panel del proveedor y actualiza config.php.
