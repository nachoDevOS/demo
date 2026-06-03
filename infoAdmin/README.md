# MensaPanel — Panel de Administración

Sistema de mensajería interna empresarial. Permite enviar mensajes, PDFs e imágenes a todas las PCs de la organización en tiempo real.

---

## Requisitos del servidor

| Herramienta | Versión mínima |
|-------------|---------------|
| PHP         | 8.3+          |
| Composer    | 2.x           |
| SQLite      | Incluido en PHP (predeterminado) |
| Node.js     | No requerido (Bootstrap vía CDN) |

> Para usar **MySQL** en producción ver sección [Cambiar a MySQL](#cambiar-a-mysql).

---

## Instalación desde cero

### 1. Instalar dependencias PHP

```bash
cd infoAdmin
composer install
```

### 2. Configurar el entorno

Copiar `.env.example` a `.env` si no existe:

```bash
copy .env.example .env
php artisan key:generate
```

> Si ya existe `.env` con la clave generada, **no ejecutar** `key:generate`.

### 3. Crear la base de datos y el usuario admin

```bash
php artisan migrate --seed
```

Esto crea todas las tablas y el usuario administrador:

| Campo    | Valor           |
|----------|-----------------|
| Email    | admin@admin.com |
| Password | password        |

> Cambiar la contraseña tras el primer acceso.

### 4. Crear carpeta de uploads

```bash
mkdir public\uploads
```

La carpeta ya existe si el proyecto fue clonado de este repositorio.

---

## Levantar el sistema

Se necesitan **2 terminales** abiertas simultáneamente:

### Terminal 1 — Servidor web Laravel

```bash
cd infoAdmin
php artisan serve
```

Panel disponible en: **http://localhost:8000**

### Terminal 2 — Servidor WebSocket Reverb

```bash
cd infoAdmin
php artisan reverb:start
```

Servidor WebSocket escuchando en: **localhost:8080**

> Ambos procesos deben estar corriendo para que los mensajes lleguen a las PCs en tiempo real.

---

## Usar el panel

### Login
- Ir a `http://localhost:8000/login`
- Ingresar con `admin@admin.com` / `password`

### Enviar un mensaje
1. Ir a **Enviar Mensaje** (menú lateral)
2. Elegir el tipo: Notificación / Instructivo / Urgente / Reunión
3. Completar Título y Mensaje
4. Adjuntar archivo PDF, JPG, PNG o GIF (opcional, máx. 20 MB)
5. Clic en **Enviar a todas las PCs**
6. El toast confirma cuántas PCs recibieron el mensaje

### Ver historial
- Ir a **Historial** — tabla paginada con todos los mensajes enviados
- Filtrar por tipo y rango de fechas
- Clic en una fila para ver el detalle completo

### Ver confirmaciones
- Ir a **Confirmaciones**
- Seleccionar un mensaje del dropdown
- Ver qué PCs recibieron / vieron / descargaron el mensaje

---

## Estructura de archivos

```
infoAdmin/
├── app/
│   ├── Events/
│   │   └── MensajeEnviado.php        <- Evento broadcast a Reverb
│   ├── Http/Controllers/
│   │   ├── Auth/LoginController.php  <- Login / logout
│   │   ├── PanelController.php       <- Enviar mensajes
│   │   ├── HistorialController.php   <- Historial
│   │   ├── ConfirmacionesController.php
│   │   └── Api/
│   │       ├── RegistroController.php       <- Recibe heartbeat de las PCs
│   │       └── ConfirmacionApiController.php <- Recibe confirmaciones del .exe
│   └── Models/
│       ├── Mensaje.php
│       ├── Confirmacion.php
│       └── PcActiva.php              <- Registra PCs conectadas
├── database/
│   ├── migrations/                   <- Tablas: mensajes, confirmaciones, pcs_activas
│   └── database.sqlite               <- Base de datos (SQLite por defecto)
├── public/
│   └── uploads/                      <- PDFs e imagenes subidos por el admin
├── resources/views/
│   ├── layouts/app.blade.php         <- Layout Bootstrap 5
│   ├── auth/login.blade.php
│   ├── panel.blade.php
│   ├── historial.blade.php
│   └── confirmaciones.blade.php
├── routes/
│   ├── web.php                       <- Rutas del panel web
│   ├── api.php                       <- Rutas para el cliente Python
│   └── channels.php                  <- Canal broadcast 'mensajes'
└── .env                              <- Configuracion del entorno
```

---

## Variables de entorno importantes (.env)

```env
APP_URL=http://localhost:8000         # URL publica del servidor

DB_CONNECTION=sqlite                  # Base de datos

BROADCAST_CONNECTION=reverb           # Motor WebSocket

REVERB_APP_KEY=xk2z9tboo2ajmwtwl2qn  # Clave que deben tener los clientes .exe
REVERB_HOST=localhost
REVERB_PORT=8080
REVERB_SCHEME=http                    # Cambiar a https en produccion
```

> El `REVERB_APP_KEY` debe coincidir exactamente con el `app_key` en el `config.ini` del cliente MensaDesk.

---

## APIs para el cliente Python

| Método | Ruta              | Descripción |
|--------|-------------------|-------------|
| POST   | /api/registro     | PC registra su presencia (heartbeat cada 60s) |
| POST   | /api/confirmacion | PC confirma recibido / visto / descargado |
| GET    | /api/pcs          | Cantidad de PCs activas (usada por el panel) |

---

## Agregar más administradores

**Opción 1 — Artisan Tinker (recomendado):**
```bash
php artisan tinker
```
```php
\App\Models\User::create([
    'name'     => 'Otro Admin',
    'email'    => 'otro@empresa.com',
    'password' => \Illuminate\Support\Facades\Hash::make('contraseña_segura'),
]);
```

**Opción 2 — Editar DatabaseSeeder y re-sembrar:**
Editar `database/seeders/DatabaseSeeder.php` y agregar otro `updateOrCreate`, luego:
```bash
php artisan db:seed
```

---

## Cambiar a MySQL

1. Crear la base de datos en MySQL:
```sql
CREATE DATABASE mensapanel CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

2. Editar `.env`:
```env
# Comentar estas dos lineas:
# DB_CONNECTION=sqlite
# DB_DATABASE=...ruta...database.sqlite

# Descomentar y completar estas:
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=mensapanel
DB_USERNAME=root
DB_PASSWORD=tu_contraseña
```

3. Correr migraciones:
```bash
php artisan migrate --seed
```

---

## Despliegue en produccion (VPS / servidor)

### Puertos que deben estar abiertos en el firewall

| Puerto | Protocolo | Uso |
|--------|-----------|-----|
| 80     | HTTP      | Panel de administracion |
| 443    | HTTPS     | Panel con SSL (recomendado) |
| 8080   | WebSocket | Conexiones de los clientes MensaDesk |

### Pasos adicionales
1. Configurar Nginx para servir la carpeta `public/`
2. Cambiar `APP_ENV=production` y `APP_DEBUG=false` en `.env`
3. Cambiar `APP_URL` al dominio o IP real del servidor
4. Cambiar `REVERB_HOST` al dominio o IP real
5. Cambiar `REVERB_SCHEME=https` con certificado SSL para WebSocket seguro
6. Ejecutar `php artisan optimize` para cachear rutas y configuracion
7. Usar Supervisor o systemd para mantener `reverb:start` corriendo en background

---

## Solucion de problemas

| Problema | Solucion |
|----------|----------|
| "No hay PCs conectadas" | Verificar que `reverb:start` esta corriendo y el `app_key` del cliente coincide con `REVERB_APP_KEY` |
| Error 500 al enviar | Revisar `storage/logs/laravel.log` |
| Archivo no se sube | Verificar que existe `public/uploads/` con permisos de escritura |
| Login falla | Correr `php artisan db:seed` para recrear el usuario admin |
| WebSocket no conecta | Verificar que el puerto 8080 no esta bloqueado por firewall |
| Mensajes no llegan al .exe | Verificar que `REVERB_APP_KEY` en `.env` del servidor coincide con `app_key` en `config.ini` del cliente |
