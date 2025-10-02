<?php
require_once __DIR__ . '/../lib/bootstrap.php';
require_once __DIR__ . '/../lib/admin.php';

// Verificar CSRF
validateCsrfRequest();

// Cerrar sesión de administrador
logoutAdmin();

// Redirigir al login
redirect('/admin/login.php');
