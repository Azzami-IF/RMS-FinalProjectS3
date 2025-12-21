# Arsitektur (Ringkas)

Aplikasi menggunakan PHP native dengan pola OOP ringan.

## Bootstrap & Context
- `classes/AppContext.php`: 1 pintu untuk `session`, `config/env.php`, `PDO db`, `user/role`, dan `pathPrefix`.
- `includes/header.php`: layout + navbar; akan reuse `$GLOBALS['rms_app']` jika sudah diset.
- `classes/PageBootstrap.php`: helper kecil agar halaman konsisten: buat AppContext, set global, lalu guard.

## Alur Request (Web)
```mermaid
flowchart TD
  A[Browser Request] --> B[/public/index.php (router)]
  B --> C[PageBootstrap/AppContext]
  C --> D[includes/header.php]
  C --> E[classes/* Services & Controllers]
  E --> F[(MySQL/MariaDB)]
  D --> G[Page Output]
```

## Alur Cron/CLI (Notifikasi)
```mermaid
flowchart TD
  A[cron] --> B[notifications/*.php]
  B --> C[AppContext (config + db)]
  C --> D[NotificationService]
  D --> E[(notifications table)]
  D --> F[PHPMailer -> SMTP]
```

## Routing Endpoint (High-level)
- Halaman publik/user: file PHP di root (mis. `index.php`, `login.php`, `dashboard.php`, `schedules.php`, dll.)
- Admin: `admin/*.php`
- Actions (POST): `process/*.php`
- JSON charts: `charts/*.php`
- Notifikasi API: `notifications/api.php`
- Export: `export/*.php`

Deployment yang disarankan ada di README.
