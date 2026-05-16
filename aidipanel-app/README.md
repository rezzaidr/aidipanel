# AidiPanel — Web Application

Panel web app untuk AidiPanel. Stack: **PHP 8.x (no framework) + SQLite + Alpine.js + Tailwind CSS**.

## Struktur Direktori

```
aidipanel-app/
├── public/              ← Nginx webroot
│   └── index.php        ← Single entry point (router)
├── app/
│   ├── Core/            ← Router, Request, Session, Auth, DB, helpers
│   ├── Controllers/     ← 10 controllers (Auth, Dashboard, Site, Cache, DB, PHP, SSL, Service, User, System)
│   ├── Views/           ← PHP templates per halaman
│   └── Middleware/      ← Auth + CSRF middleware
├── storage/
│   ├── db/              ← SQLite database (aidipanel.sqlite)
│   └── logs/            ← Panel logs
├── deploy-panel.sh      ← Deploy script
└── README.md
```

## Deploy ke Server

```bash
# 1. Install server dulu (jika belum)
bash install-aidipanel.sh

# 2. Upload folder ini ke server
scp -r aidipanel-app/ root@<ip-server>:~/

# 3. Deploy
cd ~/aidipanel-app
sudo bash deploy-panel.sh
```

## Default Login

- **Username**: `admin`
- **Password**: `admin`

> ⚠️ Ganti password admin segera setelah login pertama!

## Halaman yang Tersedia

| URL | Halaman |
|-----|---------|
| `/dashboard` | Metrics server, services, recent sites |
| `/sites` | Daftar semua site |
| `/sites/add` | Tambah site baru |
| `/sites/{domain}` | Detail & manage site |
| `/sites/{domain}/nginx` | Nginx config editor |
| `/cache` | FastCGI cache management |
| `/databases` | Database management |
| `/php` | PHP-FPM version management |
| `/ssl` | SSL/TLS certificate management |
| `/services` | Start/stop/restart services |
| `/users` | Panel user management |
| `/logs` | Nginx & panel log viewer |
| `/api/metrics` | JSON — server metrics |
| `/api/services` | JSON — service status |

## SQLite Schema

```sql
users         — Panel users (username, password_hash, role, last_login)
sites         — Registered sites (domain, type, php_version, ssl_type, cache_enabled)
activity_log  — Audit trail semua aksi
settings      — Key-value config panel
```

## Security

- Session-based auth dengan cookie `httponly + secure + samesite=Strict`
- CSRF token pada semua POST request
- Brute-force throttle login (5 attempts / 5 minutes)
- `run_cli()` hanya mengizinkan binary `/usr/local/bin/aidipanel`
- Semua input di-sanitize sebelum dipakai
