# Guía de instalación — Nexus

## Versiones objetivo

| Componente | Versión |
|---|---|
| PHP | 5.6.x |
| MariaDB | 10.1 |
| Apache | 2.4.x |

**Ubuntu 18.04** incluye en sus repositorios Apache 2.4.29 y MariaDB 10.1, igual que el entorno
XAMPP de desarrollo. PHP 5.6 se instala desde el PPA `ppa:ondrej/php`. En otras versiones de
Ubuntu los scripts funcionan, pero MariaDB será más reciente (la aplicación es compatible).

---

## Opción A — Linux con los scripts

Ejecuta los scripts **en este orden**, desde la raíz del repositorio:

```bash
sudo bash scripts/instalar_apache.sh    # 1. Apache + publica /nexus
sudo bash scripts/instalar_php.sh       # 2. PHP 5.6 y módulo de Apache
sudo bash scripts/instalar_mariadb.sh   # 3. MariaDB, BD, usuario y config/config.php
```

Qué hace cada uno:

1. **instalar_apache.sh** — instala Apache, activa `rewrite`/`headers` y crea la configuración
   `/etc/apache2/conf-available/nexus.conf`, que publica la carpeta `app/` en `http://localhost/nexus`.
2. **instalar_php.sh** — agrega el PPA de Ondřej Surý, instala PHP 5.6 (`mysql`, `mbstring`, `xml`,
   `curl`, `json`), deja activo solo ese módulo en Apache y ajusta zona horaria y visualización de errores.
3. **instalar_mariadb.sh** — instala MariaDB, importa `database/nexus.sql`, crea el usuario `nexus`
   con una contraseña aleatoria y genera `config/config.php`. Al final imprime las credenciales.

Después abre **http://localhost/nexus**.

Los scripts se pueden ejecutar más de una vez sin romper nada. Si vuelves a correr
`instalar_mariadb.sh`, se genera una contraseña nueva y se guarda una copia del `config.php` anterior.

---

## Opción B — Windows con XAMPP

1. Instala XAMPP con PHP 5.6 y arranca **Apache** y **MySQL** desde su panel.
2. Copia el repositorio completo (con `app/`, `config/`, `database/`…) a `C:\xampp\htdocs\nexus\`.
3. Abre `http://localhost/phpmyadmin`, ve a **Importar** y selecciona `database/nexus.sql`.
4. Abre **http://localhost/nexus/app/**.

No necesitas crear `config/config.php`: si no existe se usan los valores de `config/config.example.php`
(usuario `root` sin contraseña, BD `nexus`). Si tu MySQL tiene otra contraseña, copia
`config.example.php` como `config.php` y edítalo.

---

## Primer acceso

| Usuario | Contraseña |
|---|---|
| `admin@escuela.com` | `admin123` |

Pasos sugeridos para probar:

1. Como administrador crea una **materia** y un **grupo**.
2. Cierra sesión y regístrate como **alumno** y como **profesor** (`Regístrate` en la pantalla de login).
3. Entra como administrador: en **Grupos → Materias y alumnos** asigna la materia y el profesor al grupo;
   en **Alumnos → Editar** asigna el grupo al alumno.
4. Entra como profesor, captura calificaciones; entra como alumno y revisa la boleta.

---

## Configuración

`config/config.php` (se crea a partir de `config/config.example.php`) admite:

| Constante | Descripción | Por defecto |
|---|---|---|
| `DB_HOST` | Servidor de base de datos | `localhost` |
| `DB_NAME` | Nombre de la base de datos | `nexus` |
| `DB_USER` / `DB_PASS` | Credenciales | `root` / vacío |
| `ZONA_HORARIA` | Zona horaria (opcional) | `America/Mexico_City` |
| `BASE_URL` | Ruta base de la app (opcional; se detecta sola) | — |

Otros valores (número de parciales, calificación mínima aprobatoria) están en
`app/includes/funciones.php` (`NUM_PARCIALES`, `CAL_APROBATORIA`).

---

## Solución de problemas

**Error 403 / "Forbidden" al abrir /nexus (Linux).** Apache no puede leer la carpeta del proyecto.
Permite el paso por tu carpeta personal: `chmod o+x "$HOME"` (y por cada carpeta intermedia hasta el repositorio).

**Se descarga o se muestra el código PHP.** El módulo PHP no está activo: ejecuta
`sudo a2enmod php5.6 && sudo systemctl restart apache2`.

**"Error de conexión a la base de datos".** Revisa `config/config.php` (usuario y contraseña) y que el servicio
esté activo: `sudo systemctl status mariadb`. En XAMPP, que MySQL esté iniciado.

**Los enlaces llevan a una ruta incorrecta.** Define `BASE_URL` manualmente en `config/config.php`
(por ejemplo `define('BASE_URL', '/nexus');`).

**Sin estilos (página sin formato).** Bootstrap se carga por CDN; se necesita conexión a internet.

**`bad interpreter: /usr/bin/env bash^M` al ejecutar un script.** El archivo tiene saltos de línea de
Windows. Corrige con `sed -i 's/\r$//' scripts/*.sh` (el repositorio incluye `.gitattributes` para evitarlo).

**No puedo iniciar sesión como administrador.** Reimporta `database/nexus.sql`: el `INSERT IGNORE` solo
crea al administrador si no existe.
