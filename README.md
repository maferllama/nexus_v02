# Nexus — Sistema de Control Escolar

Aplicación web para administrar un centro escolar: alumnos, profesores, materias, grupos y
calificaciones, con acceso diferenciado por rol (administrador, profesor y alumno).

Desarrollada en **PHP 5.6** y **MariaDB 10.1** (Apache 2.4), pensada para correr en XAMPP o en
un servidor Linux (Ubuntu 18.04) preparado con los scripts de este repositorio.

## Funcionalidades

| Rol | Qué puede hacer |
|---|---|
| **Público** | Registrarse como alumno o profesor e iniciar sesión. |
| **Administrador** | CRUD de usuarios, alumnos, profesores, materias y grupos; asignar materias y profesores a cada grupo; asignar alumnos a grupos. |
| **Profesor** | Ver sus grupos/materias y capturar calificaciones por parcial (0–10). |
| **Alumno** | Consultar su boleta con calificaciones por parcial y promedios. |

> Un profesor o alumno recién registrado no ve información hasta que el administrador lo asigna
> a una materia/grupo.

## Estructura del repositorio

```
nexus/
├── README.md
├── app/                     # Código de la aplicación (lo que sirve Apache)
│   ├── admin/               #   módulos del administrador
│   ├── profesor/            #   módulos del profesor
│   ├── alumno/              #   boleta del alumno
│   ├── config/conexion.php  #   conexión PDO y utilidades de sesión
│   ├── includes/            #   header, footer y funciones compartidas
│   ├── index.php            #   login
│   ├── register.php         #   registro
│   ├── dashboard.php        #   panel principal
│   └── logout.php
├── scripts/                 # Instalación del entorno en Ubuntu/Debian
│   ├── instalar_apache.sh
│   ├── instalar_php.sh
│   └── instalar_mariadb.sh
├── database/
│   └── nexus.sql            # Esquema + administrador por defecto
├── config/
│   └── config.example.php   # Plantilla de configuración (config.php no se sube)
└── docs/
    └── instalacion.md       # Guía de instalación detallada
```

## Instalación rápida

### Linux (Ubuntu 18.04 recomendado)

```bash
git clone https://github.com/<tu-usuario>/nexus.git
cd nexus
sudo bash scripts/instalar_apache.sh
sudo bash scripts/instalar_php.sh
sudo bash scripts/instalar_mariadb.sh
```

Abre <http://localhost/nexus>.

### Windows (XAMPP)

1. Copia el repositorio completo a `C:\xampp\htdocs\nexus\`.
2. En phpMyAdmin importa `database/nexus.sql`.
3. Abre <http://localhost/nexus/app/>.

La guía completa está en [docs/instalacion.md](docs/instalacion.md).

## Acceso por defecto

| Usuario | Contraseña |
|---|---|
| `admin@escuela.com` | `admin123` |

**Cambia esta contraseña** (Administrador → Usuarios) en cualquier instalación real.

## Notas técnicas

- Compatible con PHP 5.6: sin `??`, sin tipos escalares ni tipos de retorno.
- Acceso a datos con PDO y consultas preparadas; contraseñas con `password_hash()` / `password_verify()`.
- Protección CSRF en todos los formularios y `session_regenerate_id()` al iniciar sesión.
- Interfaz con Bootstrap 4.6 por CDN (requiere conexión a internet para los estilos).
- La configuración (`config/`) vive fuera de `app/`, por lo que no queda expuesta por el servidor web.

## Autores

_Agrega aquí los nombres del equipo._
