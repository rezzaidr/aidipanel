# Installation Guide

## Requirements

| Resource | Minimum |
|---|---|
| OS | Debian 11/12, Ubuntu 22.04/24.04 |
| RAM | 1 GB (2 GB recommended) |
| Disk | 10 GB free |
| Access | Root SSH |

## Step 1 — Run Installer

SSH into your fresh server as root, then:

```bash
curl -fsSL https://raw.githubusercontent.com/rezzaidr/aidipanel/main/install.sh -o install.sh
bash install.sh
```

The installer will:
1. Detect OS and check resources
2. Create swap if needed (2 GB auto)
3. Install Nginx + FastCGI Cache
4. Install PHP 8.1, 8.2, 8.3
5. Install MariaDB (or MySQL — your choice)
6. Install Redis
7. Configure UFW firewall + Fail2ban
8. Install ProFTPD (SFTP only)
9. Setup AidiPanel scaffold
10. Run health check
11. Clean up

## Step 2 — Choose Database Engine (optional)

```bash
# Default: MariaDB 10.11 (best for WordPress)
bash install.sh

# Or pick your preferred engine:
bash install.sh --db-engine mariadb114   # MariaDB 11.4 LTS
bash install.sh --db-engine mariadb118   # MariaDB 11.8
bash install.sh --db-engine mysql80      # MySQL 8.0
bash install.sh --db-engine mysql84      # MySQL 8.4 LTS
```

## Step 3 — Deploy Panel Web App

```bash
# On your local machine — upload panel app
scp -r aidipanel-app/ root@<server-ip>:~/

# On the server
cd ~/aidipanel-app
bash deploy-panel.sh
```

## Step 4 — Access Panel

```
https://<server-ip>:8443
```

Default credentials:
- Username: `admin`
- Password: `admin`

**Change the password immediately after first login.**

## Step 5 — Install SSL for Panel (optional)

Point a domain to your server, then:

```bash
aidipanel ssl:install --domain panel.yourdomain.com --email you@email.com
```

## Firewall Ports

| Port | Service |
|---|---|
| 22 | SSH |
| 80 | HTTP |
| 443 | HTTPS |
| 8443 | AidiPanel web UI |
| 2022 | SFTP (ProFTPD) |

## Credentials File

All generated credentials are saved to:

```
/opt/aidipanel/credentials.conf
```

Keep this file secure. It contains DB root password and panel DB credentials.

## Uninstall

```bash
# Coming soon — manual steps for now:
systemctl stop nginx php8.1-fpm php8.2-fpm php8.3-fpm mariadb redis-server
apt remove nginx php8.1-fpm php8.2-fpm php8.3-fpm mariadb-server redis-server
rm -rf /opt/aidipanel /var/www /var/cache/nginx/fastcgi
userdel aidipanel
```
