#!/usr/bin/env bash
# =============================================================
# Nexus - Instalación de PHP 5.6 (+ módulo de Apache)
# PHP 5.6 ya no está en los repositorios oficiales de Ubuntu, por
# eso se usa el PPA ppa:ondrej/php.
# Requiere haber ejecutado antes scripts/instalar_apache.sh
# Uso: sudo bash scripts/instalar_php.sh
# =============================================================
set -euo pipefail

if [ "$(id -u)" -ne 0 ]; then
    echo "[ERROR] Ejecuta como root:  sudo bash $0"
    exit 1
fi
if [ -r /etc/os-release ]; then . /etc/os-release; else ID=""; fi
if [ "${ID:-}" != "ubuntu" ]; then
    echo "[ERROR] Este script usa un PPA de Ubuntu. Sistema detectado: ${ID:-desconocido}."
    echo "        En Debian usa el repositorio https://packages.sury.org/php/"
    exit 1
fi
if ! command -v apache2 >/dev/null 2>&1; then
    echo "[ERROR] Apache no está instalado. Ejecuta primero:  sudo bash scripts/instalar_apache.sh"
    exit 1
fi

export DEBIAN_FRONTEND=noninteractive

servicio() {
    if command -v systemctl >/dev/null 2>&1 && [ -d /run/systemd/system ]; then
        systemctl "$1" "$2"
    else
        service "$2" "$1"
    fi
}

echo "==> Agregando el repositorio ppa:ondrej/php..."
apt-get update -y
apt-get install -y software-properties-common ca-certificates apt-transport-https
add-apt-repository -y ppa:ondrej/php
apt-get update -y

echo "==> Instalando PHP 5.6..."
apt-get install -y \
    php5.6 php5.6-cli php5.6-common php5.6-json \
    php5.6-mysql php5.6-mbstring php5.6-xml php5.6-curl \
    libapache2-mod-php5.6

echo "==> Dejando activo únicamente el módulo PHP 5.6 en Apache..."
for modulo in /etc/apache2/mods-enabled/php*.load; do
    [ -e "$modulo" ] || continue
    nombre="$(basename "$modulo" .load)"
    if [ "$nombre" != "php5.6" ]; then
        a2dismod "$nombre" || true
    fi
done
a2dismod mpm_event mpm_worker >/dev/null 2>&1 || true
a2enmod mpm_prefork php5.6 >/dev/null

echo "==> Ajustes de desarrollo (zona horaria y errores visibles)..."
for sapi in apache2 cli; do
    if [ -d "/etc/php/5.6/$sapi/conf.d" ]; then
        cat > "/etc/php/5.6/$sapi/conf.d/99-nexus.ini" << INI
; Ajustes de Nexus (entorno de desarrollo)
date.timezone = America/Mexico_City
display_errors = On
error_reporting = E_ALL & ~E_DEPRECATED & ~E_STRICT
INI
    fi
done

update-alternatives --set php /usr/bin/php5.6 >/dev/null 2>&1 || true
servicio restart apache2

echo
php5.6 -v | head -n 1
echo "[OK] PHP 5.6 instalado. Siguiente paso:  sudo bash scripts/instalar_mariadb.sh"
