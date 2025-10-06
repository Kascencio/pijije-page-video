<?php
require_once __DIR__ . '/lib/bootstrap.php';

// Cerrar sesión
logoutUser();

// Redirigir al inicio
redirect(''); // base URL
