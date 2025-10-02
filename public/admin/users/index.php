<?php
require_once __DIR__ . '/../../lib/bootstrap.php';
require_once __DIR__ . '/../../lib/admin.php';

// Verificar acceso de administrador
requireAdmin();
requireAdminPermission('view_users');

$admin = getCurrentAdmin();

// Procesar acciones
if (isPost()) {
    validateCsrfRequest();
    
    $action = $_POST['action'] ?? '';
    $userId = (int)($_POST['user_id'] ?? 0);
    
    switch ($action) {
        case 'grant_access':
            if (hasAdminPermission('edit_users')) {
                grantAccess($userId, 1);
                logAdminAction('grant_access', 'user', $userId, ['course_id' => 1]);
                setFlash('success', 'Acceso concedido al usuario');
            }
            break;
            
        case 'revoke_access':
            if (hasAdminPermission('edit_users')) {
                revokeAccess($userId, 1);
                logAdminAction('revoke_access', 'user', $userId, ['course_id' => 1]);
                setFlash('success', 'Acceso revocado al usuario');
            }
            break;
            
        case 'toggle_active':
            if (hasAdminPermission('edit_users')) {
                $db = getDB();
                $user = $db->fetchOne('SELECT active FROM users WHERE id = ?', [$userId]);
                if ($user) {
                    $newStatus = $user['active'] ? 0 : 1;
                    $db->update('users', ['active' => $newStatus], 'id = ?', [$userId]);
                    
                    logAdminAction('toggle_user_status', 'user', $userId, [
                        'new_status' => $newStatus ? 'active' : 'inactive'
                    ]);
                    
                    setFlash('success', 'Estado del usuario actualizado');
                }
            }
            break;
    }
    
    redirect('/admin/users/index.php');
}

// Obtener parámetros de filtrado
$search = $_GET['search'] ?? '';
$filter = $_GET['filter'] ?? 'all';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Construir query
$whereConditions = [];
$params = [];

if ($search) {
    $whereConditions[] = '(name LIKE ? OR email LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($filter === 'with_access') {
    $whereConditions[] = 'id IN (SELECT user_id FROM user_access WHERE course_id = 1)';
} elseif ($filter === 'without_access') {
    $whereConditions[] = 'id NOT IN (SELECT user_id FROM user_access WHERE course_id = 1)';
} elseif ($filter === 'active') {
    $whereConditions[] = 'active = 1';
} elseif ($filter === 'inactive') {
    $whereConditions[] = 'active = 0';
}

$whereClause = $whereConditions ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Obtener usuarios
$db = getDB();
$users = $db->fetchAll(
    "SELECT u.*, 
     CASE WHEN ua.user_id IS NOT NULL THEN 1 ELSE 0 END as has_access,
     ua.granted_at
     FROM users u 
     LEFT JOIN user_access ua ON u.id = ua.user_id AND ua.course_id = 1
     $whereClause
     ORDER BY u.created_at DESC 
     LIMIT ? OFFSET ?",
    array_merge($params, [$perPage, $offset])
);

// Contar total para paginación
$totalCount = $db->fetchOne(
    "SELECT COUNT(*) as count FROM users u $whereClause",
    $params
)['count'];

$totalPages = ceil($totalCount / $perPage);

// Obtener flash message
$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios - Panel de Administración</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: #f8fafc;
            color: #2d3748;
        }
        
        .admin-layout {
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar */
        .sidebar {
            width: 250px;
            background: white;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 1000;
        }
        
        .sidebar-header {
            padding: 20px;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .sidebar-header h2 {
            color: #2d3748;
            font-size: 18px;
            font-weight: 700;
        }
        
        .sidebar-header p {
            color: #718096;
            font-size: 14px;
            margin-top: 4px;
        }
        
        .sidebar-nav {
            padding: 20px 0;
        }
        
        .nav-item {
            display: block;
            padding: 12px 20px;
            color: #4a5568;
            text-decoration: none;
            transition: all 0.3s;
            border-left: 3px solid transparent;
        }
        
        .nav-item:hover {
            background: #f7fafc;
            color: #2d3748;
        }
        
        .nav-item.active {
            background: #ebf8ff;
            color: #3182ce;
            border-left-color: #3182ce;
        }
        
        .nav-item svg {
            width: 20px;
            height: 20px;
            margin-right: 12px;
            vertical-align: middle;
        }
        
        .logout-section {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 20px;
            border-top: 1px solid #e2e8f0;
        }
        
        .logout-btn {
            width: 100%;
            padding: 10px;
            background: #fed7d7;
            color: #c53030;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .logout-btn:hover {
            background: #feb2b2;
        }
        
        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 30px;
        }
        
        .page-header {
            margin-bottom: 30px;
        }
        
        .page-header h1 {
            font-size: 28px;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 8px;
        }
        
        .page-header p {
            color: #718096;
            font-size: 16px;
        }
        
        /* Filters */
        .filters {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .filters-row {
            display: grid;
            grid-template-columns: 1fr 200px 150px;
            gap: 15px;
            align-items: end;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
        }
        
        .filter-label {
            font-size: 14px;
            font-weight: 500;
            color: #4a5568;
            margin-bottom: 8px;
        }
        
        .filter-input,
        .filter-select {
            padding: 10px 12px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        .filter-input:focus,
        .filter-select:focus {
            outline: none;
            border-color: #3b82f6;
        }
        
        .filter-btn {
            padding: 10px 20px;
            background: #3b82f6;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .filter-btn:hover {
            background: #2563eb;
        }
        
        /* Flash Messages */
        .flash-message {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        
        .flash-success {
            background: #f0fff4;
            color: #22543d;
            border: 1px solid #9ae6b4;
        }
        
        .flash-error {
            background: #fed7d7;
            color: #742a2a;
            border: 1px solid #fc8181;
        }
        
        /* Users Table */
        .users-table-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .users-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .users-table th {
            background: #f7fafc;
            padding: 16px;
            text-align: left;
            font-weight: 600;
            color: #4a5568;
            font-size: 14px;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .users-table td {
            padding: 16px;
            border-bottom: 1px solid #f7fafc;
            font-size: 14px;
        }
        
        .users-table tr:hover {
            background: #f8fafc;
        }
        
        .user-info {
            display: flex;
            flex-direction: column;
        }
        
        .user-name {
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 4px;
        }
        
        .user-email {
            color: #718096;
            font-size: 13px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .status-active {
            background: #f0fff4;
            color: #22543d;
        }
        
        .status-inactive {
            background: #fed7d7;
            color: #742a2a;
        }
        
        .access-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .access-yes {
            background: #ebf8ff;
            color: #2c5282;
        }
        
        .access-no {
            background: #f7fafc;
            color: #718096;
        }
        
        .action-buttons {
            display: flex;
            gap: 8px;
        }
        
        .action-btn {
            padding: 6px 12px;
            border: none;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-grant {
            background: #f0fff4;
            color: #22543d;
            border: 1px solid #9ae6b4;
        }
        
        .btn-grant:hover {
            background: #c6f6d5;
        }
        
        .btn-revoke {
            background: #fed7d7;
            color: #742a2a;
            border: 1px solid #fc8181;
        }
        
        .btn-revoke:hover {
            background: #fbb6ce;
        }
        
        .btn-toggle {
            background: #f7fafc;
            color: #4a5568;
            border: 1px solid #e2e8f0;
        }
        
        .btn-toggle:hover {
            background: #edf2f7;
        }
        
        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            margin-top: 20px;
        }
        
        .pagination a,
        .pagination span {
            padding: 8px 12px;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            text-decoration: none;
            color: #4a5568;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .pagination a:hover {
            background: #f7fafc;
            border-color: #cbd5e0;
        }
        
        .pagination .current {
            background: #3b82f6;
            color: white;
            border-color: #3b82f6;
        }
        
        .pagination .disabled {
            color: #a0aec0;
            cursor: not-allowed;
        }
        
        .pagination .disabled:hover {
            background: transparent;
            border-color: #e2e8f0;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s;
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .filters-row {
                grid-template-columns: 1fr;
            }
            
            .users-table-container {
                overflow-x: auto;
            }
            
            .users-table {
                min-width: 800px;
            }
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h2>Panel de Admin</h2>
                <p>Bienvenido, <?= escape($admin['username']) ?></p>
            </div>
            
            <nav class="sidebar-nav">
                <a href="/admin/dashboard/index.php" class="nav-item">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5a2 2 0 012-2h4a2 2 0 012 2v2H8V5z"/>
                    </svg>
                    Dashboard
                </a>
                
                <a href="/admin/users/index.php" class="nav-item active">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"/>
                    </svg>
                    Usuarios
                </a>
                
                <a href="/admin/videos/index.php" class="nav-item">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                    </svg>
                    Videos
                </a>
                
                <a href="/admin/payments/index.php" class="nav-item">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    Pagos
                </a>
                
                <a href="/admin/settings/index.php" class="nav-item">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    Configuración
                </a>
            </nav>
            
            <div class="logout-section">
                <form method="POST" action="/admin/logout.php" style="margin: 0;">
                    <?= csrfInput() ?>
                    <button type="submit" class="logout-btn">
                        <svg style="width: 16px; height: 16px; margin-right: 8px; vertical-align: middle;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                        Cerrar Sesión
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <div class="page-header">
                <h1>Gestión de Usuarios</h1>
                <p>Administrar usuarios y permisos de acceso</p>
            </div>
            
            <?php if ($flash): ?>
                <div class="flash-message flash-<?= $flash['type'] ?>">
                    <?= escape($flash['message']) ?>
                </div>
            <?php endif; ?>
            
            <!-- Filters -->
            <div class="filters">
                <form method="GET" action="">
                    <div class="filters-row">
                        <div class="filter-group">
                            <label class="filter-label">Buscar usuario</label>
                            <input type="text" name="search" class="filter-input" 
                                   value="<?= escape($search) ?>" 
                                   placeholder="Nombre o email...">
                        </div>
                        
                        <div class="filter-group">
                            <label class="filter-label">Filtrar por</label>
                            <select name="filter" class="filter-select">
                                <option value="all" <?= $filter === 'all' ? 'selected' : '' ?>>Todos</option>
                                <option value="with_access" <?= $filter === 'with_access' ? 'selected' : '' ?>>Con acceso</option>
                                <option value="without_access" <?= $filter === 'without_access' ? 'selected' : '' ?>>Sin acceso</option>
                                <option value="active" <?= $filter === 'active' ? 'selected' : '' ?>>Activos</option>
                                <option value="inactive" <?= $filter === 'inactive' ? 'selected' : '' ?>>Inactivos</option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <button type="submit" class="filter-btn">Filtrar</button>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Users Table -->
            <div class="users-table-container">
                <table class="users-table">
                    <thead>
                        <tr>
                            <th>Usuario</th>
                            <th>Estado</th>
                            <th>Acceso</th>
                            <th>Fecha Registro</th>
                            <th>Último Acceso</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td>
                                <div class="user-info">
                                    <div class="user-name"><?= escape($user['name']) ?></div>
                                    <div class="user-email"><?= escape($user['email']) ?></div>
                                </div>
                            </td>
                            <td>
                                <span class="status-badge status-<?= $user['active'] ? 'active' : 'inactive' ?>">
                                    <?= $user['active'] ? 'Activo' : 'Inactivo' ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($user['has_access']): ?>
                                    <span class="access-badge access-yes">Con acceso</span>
                                    <div style="font-size: 12px; color: #718096; margin-top: 2px;">
                                        <?= formatAdminDate($user['granted_at']) ?>
                                    </div>
                                <?php else: ?>
                                    <span class="access-badge access-no">Sin acceso</span>
                                <?php endif; ?>
                            </td>
                            <td><?= formatAdminDate($user['created_at']) ?></td>
                            <td><?= formatAdminDate($user['last_login']) ?></td>
                            <td>
                                <div class="action-buttons">
                                    <?php if (hasAdminPermission('edit_users')): ?>
                                        <?php if ($user['has_access']): ?>
                                            <form method="POST" style="display: inline;">
                                                <?= csrfInput() ?>
                                                <input type="hidden" name="action" value="revoke_access">
                                                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                <button type="submit" class="action-btn btn-revoke">Revocar</button>
                                            </form>
                                        <?php else: ?>
                                            <form method="POST" style="display: inline;">
                                                <?= csrfInput() ?>
                                                <input type="hidden" name="action" value="grant_access">
                                                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                <button type="submit" class="action-btn btn-grant">Conceder</button>
                                            </form>
                                        <?php endif; ?>
                                        
                                        <form method="POST" style="display: inline;">
                                            <?= csrfInput() ?>
                                            <input type="hidden" name="action" value="toggle_active">
                                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                            <button type="submit" class="action-btn btn-toggle">
                                                <?= $user['active'] ? 'Desactivar' : 'Activar' ?>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&filter=<?= urlencode($filter) ?>">« Anterior</a>
                <?php else: ?>
                    <span class="disabled">« Anterior</span>
                <?php endif; ?>
                
                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                    <?php if ($i == $page): ?>
                        <span class="current"><?= $i ?></span>
                    <?php else: ?>
                        <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&filter=<?= urlencode($filter) ?>"><?= $i ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
                
                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&filter=<?= urlencode($filter) ?>">Siguiente »</a>
                <?php else: ?>
                    <span class="disabled">Siguiente »</span>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
