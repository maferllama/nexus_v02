#!/usr/bin/env bash
# =============================================================
# Nexus - Instalación de Apache 2.4
# Sistema objetivo: Ubuntu 18.04 (trae Apache 2.4.29). Funciona
# también en otras versiones de Ubuntu/Debian.
# Uso: sudo bash scripts/instalar_apache.sh
# =============================================================
set -euo pipefail

if [ "$(id -u)" -ne 0 ]; then
    echo "[ERROR] Ejecuta como root:  sudo bash $0"
    exit 1
fi
if ! command -v apt-get >/dev/null 2>&1; then
    echo "[ERROR] Este script requiere un sistema Debian/Ubuntu (apt-get)."
    exit 1
fi

export DEBIAN_FRONTEND=noninteractive
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_DIR="$(dirname "$SCRIPT_DIR")"
APP_DIR="$REPO_DIR/app"

servicio() {   # servicio <accion> <nombre>
    if command -v systemctl >/dev/null 2>&1 && [ -d /run/systemd/system ]; then
        systemctl "$1" "$2"
    else
        service "$2" "$1"
    fi
}

echo "==> Instalando Apache..."
apt-get update -y
apt-get install -y apache2

echo "==> Activando módulos..."
a2enmod rewrite headers >/dev/null

echo "==> Configurando ServerName..."
echo "ServerName localhost" > /etc/apache2/conf-available/servername.conf
a2enconf servername >/dev/null

echo "==> Publicando la aplicación en http://localhost/nexus  (carpeta: $APP_DIR)"
cat > /etc/apache2/conf-available/nexus.conf << CONF
Alias /nexus "${APP_DIR}"
<Directory "${APP_DIR}">
    Options -Indexes +FollowSymLinks
    AllowOverride All
    Require all granted
    DirectoryIndex index.php
</Directory>
CONF
a2enconf nexus >/dev/null

apache2ctl configtest
servicio enable apache2 2>/dev/null || true
servicio restart apache2

# Apache (usuario www-data) debe poder leer la carpeta del proyecto
if command -v sudo >/dev/null 2>&1 && ! sudo -u www-data test -r "$APP_DIR/index.php" 2>/dev/null; then
    echo
    echo "[AVISO] El usuario www-data no puede leer $APP_DIR."
    echo "        Suele resolverse permitiendo 'paso' por tu carpeta personal:"
    echo "        chmod o+x \"\$HOME\"   (y en cada carpeta intermedia del repositorio)"
fi

echo
apache2 -v | head -n 1
echo "[OK] Apache instalado. Siguiente paso:  sudo bash scripts/instalar_php.sh"
