<?php
require_once __DIR__ . '/../../lib/bootstrap.php';
require_once __DIR__ . '/../../lib/admin.php';

// Verificar acceso de administrador
requireAdmin();
requireAdminPermission('view_orders');
$cid = courseId();

$admin = getCurrentAdmin();

// Procesar acciones
if (isPost()) {
    validateCsrfRequest();
    
    $action = $_POST['action'] ?? '';
    $orderId = (int)($_POST['order_id'] ?? 0);
    
    switch ($action) {
        case 'process_refund':
            if (hasAdminPermission('edit_orders')) {
                // Aquí implementarías la lógica de reembolso con PayPal
                logAdminAction('process_refund', 'order', $orderId, [
                    'admin_note' => 'Reembolso procesado manualmente'
                ]);
                setFlash('success', 'Reembolso procesado (funcionalidad pendiente de implementar)');
            }
            break;
            
        case 'mark_paid':
            if (hasAdminPermission('edit_orders')) {
                $db = getDB();
                $db->update('orders', ['status' => 'paid'], 'id = ?', [$orderId]);
                
                // Conceder acceso si no lo tiene
                $order = $db->fetchOne('SELECT user_id FROM orders WHERE id = ?', [$orderId]);
                if ($order) {
                    grantAccess($order['user_id'], $cid);
                }
                
                logAdminAction('mark_paid', 'order', $orderId, [
                    'admin_override' => true
                ]);
                
                setFlash('success', 'Orden marcada como pagada y acceso concedido');
            }
            break;
    }
    
    redirect('/admin/payments/index.php');
}

// Obtener parámetros de filtrado
$status = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Construir query
$whereConditions = [];
$params = [];

if ($status !== 'all') {
    $whereConditions[] = 'o.status = ?';
    $params[] = $status;
}

if ($search) {
    $whereConditions[] = '(u.name LIKE ? OR u.email LIKE ? OR o.provider_order_id LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$whereClause = $whereConditions ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Obtener órdenes
$db = getDB();
$orders = $db->fetchAll(
    "SELECT o.*, u.name as user_name, u.email as user_email,
     CASE WHEN ua.user_id IS NOT NULL THEN 1 ELSE 0 END as user_has_access
     FROM orders o 
     JOIN users u ON o.user_id = u.id
    LEFT JOIN user_access ua ON u.id = ua.user_id AND ua.course_id = '.$cid.'
     $whereClause
     ORDER BY o.created_at DESC 
     LIMIT ? OFFSET ?",
    array_merge($params, [$perPage, $offset])
);

// Contar total para paginación
$totalCount = $db->fetchOne(
    "SELECT COUNT(*) as count FROM orders o JOIN users u ON o.user_id = u.id $whereClause",
    $params
)['count'];

$totalPages = ceil($totalCount / $perPage);

// Estadísticas rápidas
$stats = [
    'total' => $db->fetchOne('SELECT COUNT(*) as count FROM orders')['count'],
    'paid' => $db->fetchOne('SELECT COUNT(*) as count FROM orders WHERE status = "paid"')['count'],
    'pending' => $db->fetchOne('SELECT COUNT(*) as count FROM orders WHERE status = "pending"')['count'],
    'failed' => $db->fetchOne('SELECT COUNT(*) as count FROM orders WHERE status = "failed"')['count'],
    'revenue' => $db->fetchOne('SELECT SUM(amount_mxn) as total FROM orders WHERE status = "paid"')['total'] ?? 0
];

// Obtener flash message
$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Pagos - Panel de Administración</title>
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
        
        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .stat-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 12px;
        }
        
        .stat-title {
            font-size: 14px;
            color: #718096;
            font-weight: 500;
        }
        
        .stat-icon {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .stat-icon.total { background: #ebf8ff; color: #3182ce; }
        .stat-icon.paid { background: #f0fff4; color: #38a169; }
        .stat-icon.pending { background: #fffbeb; color: #d69e2e; }
        .stat-icon.failed { background: #fed7d7; color: #e53e3e; }
        .stat-icon.revenue { background: #faf5ff; color: #805ad5; }
        
        .stat-value {
            font-size: 24px;
            font-weight: 700;
            color: #2d3748;
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
        
        /* Orders Table */
        .orders-table-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .orders-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .orders-table th {
            background: #f7fafc;
            padding: 16px;
            text-align: left;
            font-weight: 600;
            color: #4a5568;
            font-size: 14px;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .orders-table td {
            padding: 16px;
            border-bottom: 1px solid #f7fafc;
            font-size: 14px;
        }
        
        .orders-table tr:hover {
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
        
        .status-paid {
            background: #f0fff4;
            color: #22543d;
        }
        
        .status-pending {
            background: #fffbeb;
            color: #92400e;
        }
        
        .status-failed {
            background: #fed7d7;
            color: #742a2a;
        }
        
        .amount {
            font-weight: 600;
            color: #2d3748;
        }
        
        .order-id {
            font-family: monospace;
            font-size: 12px;
            color: #718096;
            background: #f7fafc;
            padding: 2px 6px;
            border-radius: 4px;
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
        
        .btn-refund {
            background: #fed7d7;
            color: #742a2a;
            border: 1px solid #fc8181;
        }
        
        .btn-refund:hover {
            background: #fbb6ce;
        }
        
        .btn-mark-paid {
            background: #f0fff4;
            color: #22543d;
            border: 1px solid #9ae6b4;
        }
        
        .btn-mark-paid:hover {
            background: #c6f6d5;
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
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .orders-table-container {
                overflow-x: auto;
            }
            
            .orders-table {
                min-width: 1000px;
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
                <a href="<?= adminUrl('dashboard/index.php') ?>" class="nav-item">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5a2 2 0 012-2h4a2 2 0 012 2v2H8V5z"/>
                    </svg>
                    Dashboard
                </a>
                
                <a href="<?= adminUrl('users/index.php') ?>" class="nav-item">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"/>
                    </svg>
                    Usuarios
                </a>
                
                <a href="<?= adminUrl('videos/index.php') ?>" class="nav-item">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                    </svg>
                    Videos
                </a>
                
                <a href="<?= adminUrl('payments/index.php') ?>" class="nav-item active">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    Pagos
                </a>
                
                <a href="<?= adminUrl('settings/index.php') ?>" class="nav-item">
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
                <h1>Gestión de Pagos</h1>
                <p>Administrar órdenes y transacciones de PayPal</p>
            </div>
            
            <?php if ($flash): ?>
                <div class="flash-message flash-<?= $flash['type'] ?>">
                    <?= escape($flash['message']) ?>
                </div>
            <?php endif; ?>
            
            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-title">Total Órdenes</div>
                        <div class="stat-icon total">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                        </div>
                    </div>
                    <div class="stat-value"><?= number_format($stats['total']) ?></div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-title">Pagadas</div>
                        <div class="stat-icon paid">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="stat-value"><?= number_format($stats['paid']) ?></div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-title">Pendientes</div>
                        <div class="stat-icon pending">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="stat-value"><?= number_format($stats['pending']) ?></div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-title">Fallidas</div>
                        <div class="stat-icon failed">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="stat-value"><?= number_format($stats['failed']) ?></div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-title">Ingresos</div>
                        <div class="stat-icon revenue">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                            </svg>
                        </div>
                    </div>
                    <div class="stat-value">$<?= number_format($stats['revenue'] / 100, 2) ?></div>
                </div>
            </div>
            
            <!-- Filters -->
            <div class="filters">
                <form method="GET" action="">
                    <div class="filters-row">
                        <div class="filter-group">
                            <label class="filter-label">Buscar</label>
                            <input type="text" name="search" class="filter-input" 
                                   value="<?= escape($search) ?>" 
                                   placeholder="Usuario, email o ID de orden...">
                        </div>
                        
                        <div class="filter-group">
                            <label class="filter-label">Estado</label>
                            <select name="status" class="filter-select">
                                <option value="all" <?= $status === 'all' ? 'selected' : '' ?>>Todos</option>
                                <option value="paid" <?= $status === 'paid' ? 'selected' : '' ?>>Pagadas</option>
                                <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Pendientes</option>
                                <option value="failed" <?= $status === 'failed' ? 'selected' : '' ?>>Fallidas</option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <button type="submit" class="filter-btn">Filtrar</button>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Orders Table -->
            <div class="orders-table-container">
                <table class="orders-table">
                    <thead>
                        <tr>
                            <th>Usuario</th>
                            <th>Orden</th>
                            <th>Estado</th>
                            <th>Monto</th>
                            <th>Fecha</th>
                            <th>Acceso</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                        <tr>
                            <td>
                                <div class="user-info">
                                    <div class="user-name"><?= escape($order['user_name']) ?></div>
                                    <div class="user-email"><?= escape($order['user_email']) ?></div>
                                </div>
                            </td>
                            <td>
                                <div class="order-id"><?= escape($order['provider_order_id']) ?></div>
                            </td>
                            <td>
                                <span class="status-badge status-<?= $order['status'] ?>">
                                    <?= ucfirst($order['status']) ?>
                                </span>
                            </td>
                            <td>
                                <div class="amount"><?= formatAdminPrice($order['amount_mxn']) ?></div>
                            </td>
                            <td><?= formatAdminDate($order['created_at']) ?></td>
                            <td>
                                <?php if ($order['user_has_access']): ?>
                                    <span class="status-badge status-paid">Con acceso</span>
                                <?php else: ?>
                                    <span style="color: #a0aec0;">Sin acceso</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <?php if (hasAdminPermission('edit_orders')): ?>
                                        <?php if ($order['status'] === 'pending'): ?>
                                            <form method="POST" style="display: inline;">
                                                <?= csrfInput() ?>
                                                <input type="hidden" name="action" value="mark_paid">
                                                <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                                <button type="submit" class="action-btn btn-mark-paid" 
                                                        onclick="return confirm('¿Marcar como pagada y conceder acceso?')">
                                                    Marcar Pagada
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        
                                        <?php if ($order['status'] === 'paid'): ?>
                                            <form method="POST" style="display: inline;">
                                                <?= csrfInput() ?>
                                                <input type="hidden" name="action" value="process_refund">
                                                <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                                <button type="submit" class="action-btn btn-refund" 
                                                        onclick="return confirm('¿Procesar reembolso? (Funcionalidad pendiente)')">
                                                    Reembolsar
                                                </button>
                                            </form>
                                        <?php endif; ?>
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
                    <a href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>">« Anterior</a>
                <?php else: ?>
                    <span class="disabled">« Anterior</span>
                <?php endif; ?>
                
                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                    <?php if ($i == $page): ?>
                        <span class="current"><?= $i ?></span>
                    <?php else: ?>
                        <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>"><?= $i ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
                
                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>">Siguiente »</a>
                <?php else: ?>
                    <span class="disabled">Siguiente »</span>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
