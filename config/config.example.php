<?php
// ---------------------------------------------------------
// Configuración de Nexus
//
// Para usar credenciales propias copia este archivo como
//   config/config.php
// (config.php está en .gitignore y NO se sube a GitHub).
// scripts/instalar_mariadb.sh lo genera automáticamente en Linux.
//
// Estos valores por defecto sirven para XAMPP en Windows.
// ---------------------------------------------------------
define('DB_HOST', 'localhost');
define('DB_NAME', 'nexus');
define('DB_USER', 'root');
define('DB_PASS', '');

// Opcionales:
// define('ZONA_HORARIA', 'America/Mexico_City');
// define('BASE_URL', '/nexus');   // normalmente se detecta sola
