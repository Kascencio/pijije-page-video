<?php
// Alias para acceder a /cursos/admin/login.php redirigiendo al login real en /cursos/public/admin/login.php
header('Location: /cursos/public/admin/login.php', true, 302);
exit;