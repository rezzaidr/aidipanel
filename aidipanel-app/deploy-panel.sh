#!/usr/bin/env bash
# =============================================================================
#  AidiPanel — Deploy Panel Web App
#  Run AFTER install-aidipanel.sh
#  Usage: bash deploy-panel.sh [--dir /opt/aidipanel]
# =============================================================================

set -Eeuo pipefail

PANEL_DIR="/opt/aidipanel"
PANEL_USER="aidipanel"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

RED='\033[0;31m'; GREEN='\033[0;32m'; CYAN='\033[0;36m'; RESET='\033[0m'

log() { echo -e "${CYAN}[INFO]${RESET}  $*"; }
ok()  { echo -e "${GREEN}[OK]${RESET}    $*"; }
die() { echo -e "${RED}[ERROR]${RESET} $*" >&2; exit 1; }

[[ "$(id -u)" -eq 0 ]] || die "Run as root: sudo bash deploy-panel.sh"
[[ -d "$SCRIPT_DIR/public" ]] || die "Run this script from the aidipanel-app directory."
[[ -d "$PANEL_DIR" ]] || die "AidiPanel base dir not found: ${PANEL_DIR}. Run install-aidipanel.sh first."

log "Deploying AidiPanel web app to ${PANEL_DIR}..."

# Copy app files
cp -r "${SCRIPT_DIR}/public/"* "${PANEL_DIR}/public/"
cp -r "${SCRIPT_DIR}/app"      "${PANEL_DIR}/"

# Ensure storage dirs exist with correct permissions
mkdir -p "${PANEL_DIR}/storage/db" "${PANEL_DIR}/storage/logs"

# Set ownership
chown -R "${PANEL_USER}":www-data "${PANEL_DIR}/app"
chown -R "${PANEL_USER}":www-data "${PANEL_DIR}/public"
chown -R "${PANEL_USER}":www-data "${PANEL_DIR}/storage"

# Permissions
find "${PANEL_DIR}/app"     -type f -exec chmod 640 {} \;
find "${PANEL_DIR}/app"     -type d -exec chmod 750 {} \;
find "${PANEL_DIR}/public"  -type f -exec chmod 644 {} \;
find "${PANEL_DIR}/public"  -type d -exec chmod 755 {} \;
chmod 700 "${PANEL_DIR}/storage"
chmod 750 "${PANEL_DIR}/storage/db" "${PANEL_DIR}/storage/logs"

# Protect sensitive dirs from web access (should be outside webroot, but double-check)
[[ -f "${PANEL_DIR}/public/.htaccess" ]] || cat > "${PANEL_DIR}/public/.htaccess" <<'HTACCESS'
# Deny access to PHP internals
<Files "*.sh">
  Require all denied
</Files>
HTACCESS

# Test Nginx config
nginx -t >> /var/log/aidipanel-install.log 2>&1 || die "Nginx config test failed after deploy."
systemctl reload nginx

ok "AidiPanel web app deployed!"
echo ""
echo "  Panel URL: check https://<your-server-ip>:8443"
echo "  Default login: admin / admin"
echo ""
echo -e "  ${RED}IMPORTANT: Change the default admin password immediately!${RESET}"
echo "  Go to: Panel → Users → Change password"
