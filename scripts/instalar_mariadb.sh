#!/usr/bin/env bash
# =============================================================
# Nexus - Instalación de MariaDB, base de datos y configuración
#  1. Instala mariadb-server (Ubuntu 18.04 trae la 10.1)
#  2. Importa database/nexus.sql (crea la BD "nexus" y el admin)
#  3. Crea un usuario de BD para la aplicación
#  4. Genera config/config.php con esas credenciales
#
# Uso: sudo bash scripts/instalar_mariadb.sh
# Opcional: DB_USER=miusuario DB_PASS=miclave sudo -E bash scripts/instalar_mariadb.sh
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
SQL_FILE="$REPO_DIR/database/nexus.sql"
CONFIG_DIR="$REPO_DIR/config"
CONFIG_FILE="$CONFIG_DIR/config.php"

DB_NAME="nexus"
DB_USER="${DB_USER:-nexus}"
if [ -z "${DB_PASS:-}" ]; then
    if command -v openssl >/dev/null 2>&1; then
        DB_PASS="$(openssl rand -hex 8)"
    else
        DB_PASS="$(date +%s%N | sha256sum | cut -c1-16)"
    fi
fi

if [ ! -f "$SQL_FILE" ]; then
    echo "[ERROR] No se encontró $SQL_FILE"
    exit 1
fi

servicio() {
    if command -v systemctl >/dev/null 2>&1 && [ -d /run/systemd/system ]; then
        systemctl "$1" "$2"
    else
        service "$2" "$1"
    fi
}

echo "==> Instalando MariaDB..."
apt-get update -y
apt-get install -y mariadb-server

echo "==> Iniciando el servicio..."
servicio enable mariadb 2>/dev/null || true
servicio start mariadb 2>/dev/null || servicio start mysql

# Espera a que acepte conexiones (root entra por socket en Ubuntu)
for i in $(seq 1 20); do
    if mysql -e "SELECT 1" >/dev/null 2>&1; then break; fi
    sleep 1
done

echo "==> Importando $SQL_FILE ..."
mysql < "$SQL_FILE"

echo "==> Creando el usuario '$DB_USER' para la aplicación..."
# GRANT ... IDENTIFIED BY crea el usuario o actualiza su contraseña (válido en MariaDB 10.1)
mysql -e "GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}'; FLUSH PRIVILEGES;"

echo "==> Generando $CONFIG_FILE ..."
if [ -f "$CONFIG_FILE" ]; then
    cp "$CONFIG_FILE" "$CONFIG_FILE.bak-$(date +%Y%m%d%H%M%S)"
fi
cat > "$CONFIG_FILE" << PHP
<?php
// Generado por scripts/instalar_mariadb.sh  (no subir a GitHub)
define('DB_HOST', 'localhost');
define('DB_NAME', '${DB_NAME}');
define('DB_USER', '${DB_USER}');
define('DB_PASS', '${DB_PASS}');
PHP
# Dueño: quien ejecutó sudo; grupo www-data para que Apache pueda leerlo
chown "${SUDO_USER:-root}:www-data" "$CONFIG_FILE" 2>/dev/null || true
chmod 640 "$CONFIG_FILE"

echo
mysql --version
echo "[OK] Base de datos lista."
echo
echo "  Base de datos : $DB_NAME"
echo "  Usuario BD    : $DB_USER"
echo "  Contraseña BD : $DB_PASS   (guardada en config/config.php)"
echo
echo "  Abre http://localhost/nexus"
echo "  Administrador: admin@escuela.com / admin123   <-- cámbiala después de entrar"
