<?php
require_once __DIR__ . '/lib/bootstrap.php';

$config = require_once __DIR__ . '/../secure/config.php';
$isLoggedIn = isLoggedIn();
$hasAccess = false;

// Verificar si el usuario tiene acceso al curso
if ($isLoggedIn) {
    $hasAccess = hasAccess(getCurrentUserId(), $config['app']['course_id']);
}

// Obtener flash message si existe
$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Curso de Ganadería Regenerativa - Cursos Orgánicos</title>
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
        
        .gradient-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        /* Top Bar */
        .top-bar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 0;
        }
        
        .top-bar-content {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .top-bar-links {
            display: flex;
            gap: 24px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .top-bar-links a {
            color: white;
            text-decoration: none;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 4px;
            transition: opacity 0.3s;
        }
        
        .top-bar-links a:hover {
            opacity: 0.8;
        }
        
        /* Header */
        .header {
            background: rgba(248, 250, 252, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 50;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 0;
        }
        
        .logo img {
            height: 48px;
            width: auto;
            transition: transform 0.3s;
        }
        
        .logo img:hover {
            transform: scale(1.05);
        }
        
        .nav-links {
            display: flex;
            gap: 32px;
            align-items: center;
        }
        
        .nav-links a {
            color: #2d3748;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }
        
        .nav-links a:hover {
            color: #667eea;
        }
        
        .nav-links a.active {
            color: #667eea;
            font-weight: 600;
            position: relative;
        }
        
        .nav-links a.active::after {
            content: '';
            position: absolute;
            bottom: -8px;
            left: 0;
            width: 100%;
            height: 2px;
            background: #667eea;
            border-radius: 1px;
        }
        
        .btn {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            font-size: 14px;
        }
        
        .btn-primary {
            background: #667eea;
            color: white;
        }
        
        .btn-primary:hover {
            background: #5a67d8;
        }
        
        .btn-outline {
            border: 1px solid #667eea;
            color: #667eea;
            background: transparent;
        }
        
        .btn-outline:hover {
            background: #667eea;
            color: white;
        }
        
        .btn-lg {
            padding: 12px 24px;
            font-size: 16px;
            border-radius: 8px;
        }
        
        .btn-xl {
            padding: 16px 32px;
            font-size: 18px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .btn-xl:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        /* Hero Section */
        .hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 80px 0;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .hero::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.2) 0%, rgba(118, 75, 162, 0.1) 50%, rgba(255, 193, 7, 0.2) 100%);
        }
        
        .hero::after {
            content: '';
            position: absolute;
            inset: 0;
            background: url('/assets/lush-green-pasture-with-cattle-grazing-sustainable.jpg') center/cover;
            opacity: 0.1;
        }
        
        .hero-content {
            position: relative;
            z-index: 10;
        }
        
        .hero h1 {
            font-size: 48px;
            font-weight: 700;
            margin-bottom: 16px;
            line-height: 1.2;
            animation: slideInLeft 0.8s ease-out;
        }
        
        .hero h2 {
            font-size: 36px;
            font-weight: 600;
            margin-bottom: 24px;
            opacity: 0.9;
            animation: slideInLeft 0.8s ease-out 0.3s both;
        }
        
        .hero p {
            font-size: 20px;
            margin-bottom: 32px;
            opacity: 0.9;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
            animation: slideInLeft 0.8s ease-out 0.6s both;
        }
        
        .hero-buttons {
            display: flex;
            gap: 16px;
            justify-content: center;
            flex-wrap: wrap;
            animation: slideInLeft 0.8s ease-out 0.9s both;
        }
        
        /* Course Card */
        .course-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
            margin: 40px auto;
            max-width: 400px;
            animation: slideInRight 0.8s ease-out 0.6s both;
        }
        
        .course-image {
            height: 200px;
            background: url('/assets/images/curso-promocion.jpg') center/cover;
            position: relative;
        }
        
        .course-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(transparent, rgba(0,0,0,0.7));
            padding: 20px;
            color: white;
        }
        
        .course-content {
            padding: 24px;
        }
        
        .course-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
            font-size: 14px;
            color: #718096;
        }
        
        .course-price {
            font-size: 24px;
            font-weight: 700;
            color: #667eea;
        }
        
        /* Stats Section */
        .stats-section {
            padding: 80px 0;
            background: rgba(248, 250, 252, 0.3);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 32px;
            text-align: center;
        }
        
        .stat-item {
            animation: scaleIn 0.8s ease-out;
        }
        
        .stat-number {
            font-size: 48px;
            font-weight: 700;
            margin-bottom: 8px;
        }
        
        .stat-label {
            color: #718096;
            font-weight: 500;
        }
        
        /* Course Info Section */
        .course-info {
            padding: 96px 0;
            background: rgba(248, 250, 252, 0.3);
        }
        
        .course-info-content {
            max-width: 1280px;
            margin: 0 auto;
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            border: 2px solid rgba(102, 126, 234, 0.2);
            overflow: hidden;
        }
        
        .course-info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
        }
        
        .course-info-left {
            padding: 32px;
        }
        
        .course-info-right {
            background: rgba(102, 126, 234, 0.05);
            padding: 32px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
        }
        
        .info-title {
            font-size: 32px;
            font-weight: 700;
            color: #667eea;
            margin-bottom: 16px;
        }
        
        .info-list {
            list-style: none;
            padding: 0;
        }
        
        .info-item {
            display: flex;
            gap: 16px;
            margin-bottom: 16px;
            align-items: flex-start;
        }
        
        .info-icon {
            color: #764ba2;
            margin-top: 4px;
            flex-shrink: 0;
        }
        
        .info-content h4 {
            font-weight: 600;
            margin-bottom: 4px;
        }
        
        .info-content p {
            color: #718096;
            font-size: 14px;
            line-height: 1.6;
        }
        
        .price-section {
            margin-bottom: 24px;
        }
        
        .price-badge {
            background: #fbbf24;
            color: #92400e;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 16px;
            display: inline-block;
        }
        
        .price-title {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 8px;
        }
        
        .price-amount {
            display: flex;
            align-items: baseline;
            gap: 8px;
            margin-bottom: 16px;
            justify-content: center;
        }
        
        .price-number {
            font-size: 48px;
            font-weight: 700;
            color: #667eea;
        }
        
        .price-currency {
            font-size: 20px;
            color: #718096;
        }
        
        /* PayPal Button */
        .paypal-button {
            width: 100%;
            height: 45px;
            background: #0070ba;
            border: none;
            border-radius: 8px;
            color: white;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .paypal-button:hover {
            background: #005ea6;
        }
        
        .paypal-button:disabled {
            background: #a0aec0;
            cursor: not-allowed;
        }
        
        /* Animations */
        @keyframes slideInLeft {
            from {
                opacity: 0;
                transform: translateX(-30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        @keyframes scaleIn {
            from {
                opacity: 0;
                transform: scale(0.9);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }
        
        /* Animation delays */
        .animation-delay-300 {
            animation-delay: 0.3s;
        }
        
        .animation-delay-600 {
            animation-delay: 0.6s;
        }
        
        .animation-delay-900 {
            animation-delay: 0.9s;
        }
        
        /* Hover effects */
        .course-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .stat-item:hover {
            transform: scale(1.05);
        }
        
        /* Badge styles */
        .badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            background: #fbbf24;
            color: #92400e;
            animation: fadeIn 0.8s ease-out;
        }
        
        /* Gradient card background */
        .gradient-card {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.05) 100%);
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .top-bar-links {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .nav-links {
                display: none;
            }
            
            .hero h1 { font-size: 36px; }
            .hero h2 { font-size: 28px; }
            .hero-buttons { flex-direction: column; align-items: center; }
            
            .stats-grid {
                grid-template-columns: 1fr;
                gap: 24px;
            }
            
            .course-info-grid {
                grid-template-columns: 1fr;
            }
            
            .course-info-left,
            .course-info-right {
                padding: 24px;
            }
        }
    </style>
</head>
<body>
    <!-- Top Bar -->
    <div class="top-bar">
        <div class="container">
            <div class="top-bar-content">
                <div class="top-bar-links">
                    <a href="https://www.youtube.com/@OrganicosdelTropico" target="_blank" rel="noopener noreferrer">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M2.5 17a24.12 24.12 0 0 1 0-10 2 2 0 0 1 1.4-1.4 49.56 49.56 0 0 1 16.2 0A2 2 0 0 1 21.5 7a24.12 24.12 0 0 1 0 10 2 2 0 0 1-1.4 1.4 49.55 49.55 0 0 1-16.2 0A2 2 0 0 1 2.5 17"/>
                            <path d="m10 15 5-3-5-3z"/>
                        </svg>
                    </a>
                </div>
                <div class="top-bar-links">
                    <a href="https://maps.google.com/" target="_blank" rel="noopener noreferrer">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/>
                            <circle cx="12" cy="10" r="3"/>
                        </svg>
                        Carretera Santa Adelaida-Palizada Km 3.5, C.P. 24200, Palizada Campeche Mex.
                    </a>
                    <a href="mailto:organicosdeltropico@yahoo.com.mx">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect width="20" height="16" x="2" y="4" rx="2" ry="2"/>
                            <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>
                        </svg>
                        organicosdeltropico@yahoo.com.mx
                    </a>
                    <a href="tel:+529932878909">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                        </svg>
                        +52 993 287 8909
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Header -->
    <header class="header">
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <img src="/assets/images/pijije-logo.jpg" alt="Pijije Regenerativo">
                </div>
                <nav class="nav-links">
                    <a href="/">Orgánicos del Trópico</a>
                    <a href="/que-hacemos">¿Qué Hacemos?</a>
                    <a href="/eventos">Eventos</a>
                    <a href="/cursos" class="active">Cursos</a>
                    <a href="/contacto">Contacto</a>
                    <?php if ($isLoggedIn): ?>
                        <a href="/logout.php" class="btn btn-outline">Cerrar Sesión</a>
                        <a href="/mis-videos.php" class="btn btn-primary">Mis Videos</a>
                    <?php else: ?>
                        <a href="/login.php" class="btn btn-outline">Iniciar Sesión</a>
                        <a href="/register.php" class="btn btn-primary">Registrarse</a>
                    <?php endif; ?>
                </nav>
            </div>
        </div>
    </header>

    <!-- Flash Messages -->
    <?php if ($flash): ?>
        <div class="container" style="margin-top: 20px;">
            <div style="padding: 16px; border-radius: 8px; margin-bottom: 20px; background: <?= $flash['type'] === 'error' ? '#fed7d7; border: 1px solid #feb2b2; color: #742a2a' : ($flash['type'] === 'success' ? '#f0fff4; border: 1px solid #9ae6b4; color: #22543d' : '#fffbeb; border: 1px solid #fcd34d; color: #92400e') ?>">
                <?= escape($flash['message']) ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <div class="hero-content">
                <h1>Plataforma de <span style="color: #667eea;">Cursos</span></h1>
                <h2>Pijije Regenerativo</h2>
                <p>Aprende ganadería regenerativa de expertos y accede a nuestro programa de bonos de carbono con guía especializada</p>
                
                <div class="hero-buttons">
                    <?php if ($hasAccess): ?>
                        <a href="/mis-videos.php" class="btn btn-xl" style="background: #38a169; color: white;">
                            ✅ Ya tienes acceso - Ver Videos
                        </a>
                    <?php elseif ($isLoggedIn): ?>
                        <a href="#comprar" class="btn btn-xl" style="background: #667eea; color: white;">
                            Comprar Curso
                        </a>
                    <?php else: ?>
                        <a href="/register.php" class="btn btn-xl" style="background: #667eea; color: white;">
                            Comenzar Ahora
                        </a>
                        <a href="/login.php" class="btn btn-xl" style="border: 2px solid white; color: white; background: transparent;">
                            Iniciar Sesión
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Course Card -->
    <section id="comprar" class="container">
        <div class="course-card">
            <div class="course-image">
                <div class="course-overlay">
                    <h3>Curso Completo Disponible</h3>
                </div>
            </div>
            <div class="course-content">
                <div class="course-meta">
                    <div>
                        <span>📹 8+ horas</span>
                        <span style="margin-left: 16px;">💻 Online</span>
                    </div>
                    <div class="course-price">$<?= number_format($config['app']['amount'] / 100, 2) ?> MXN</div>
                </div>
                
                <?php if ($hasAccess): ?>
                    <a href="/mis-videos.php" class="btn btn-primary" style="width: 100%; display: block; text-align: center; background: #38a169;">
                        ✅ Acceder a Mis Videos
                    </a>
                <?php elseif ($isLoggedIn): ?>
                    <!-- PayPal Smart Button Container -->
                    <div id="paypal-button-container"></div>
                    <p style="font-size: 14px; color: #718096; text-align: center; margin-top: 12px;">
                        Pago seguro con PayPal
                    </p>
                <?php else: ?>
                    <a href="/register.php" class="btn btn-primary" style="width: 100%; display: block; text-align: center;">
                        Registrarse para Comprar
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats-section">
        <div class="container">
            <div class="stats-grid">
                <div class="stat-item" style="animation-delay: 0.3s;">
                    <div class="stat-number" style="color: #764ba2;">5</div>
                    <p class="stat-label">Módulos Especializados</p>
                </div>
                <div class="stat-item" style="animation-delay: 0.6s;">
                    <div class="stat-number" style="color: #fbbf24;">100%</div>
                    <p class="stat-label">Satisfacción</p>
                </div>
                <div class="stat-item" style="animation-delay: 0.9s;">
                    <div class="stat-number" style="color: #667eea;">24/7</div>
                    <p class="stat-label">Acceso Total</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Course Information Section -->
    <section class="course-info">
        <div class="container">
            <div class="course-info-content">
                <div class="course-info-grid">
                    <!-- Left Column: What Includes -->
                    <div class="course-info-left">
                        <h3 class="info-title">¿Qué Incluye tu Inscripción?</h3>
                        <ul class="info-list">
                            <li class="info-item">
                                <svg class="info-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <polygon points="23 7 16 12 23 17 23 7"/>
                                    <rect x="1" y="5" width="15" height="14" rx="2" ry="2"/>
                                </svg>
                                <div class="info-content">
                                    <h4>8+ Horas de Contenido en Video HD</h4>
                                    <p>Accede a 5 módulos especializados con lecciones prácticas y teóricas.</p>
                                </div>
                            </li>
                            <li class="info-item">
                                <svg class="info-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="10"/>
                                    <polyline points="12,6 12,12 16,14"/>
                                </svg>
                                <div class="info-content">
                                    <h4>Acceso por 3 Meses</h4>
                                    <p>Aprende a tu propio ritmo con acceso ilimitado a la plataforma 24/7.</p>
                                </div>
                            </li>
                            <li class="info-item">
                                <svg class="info-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="8" r="7"/>
                                    <polyline points="8.21,13.89 7,23 12,20 17,23 15.79,13.88"/>
                                </svg>
                                <div class="info-content">
                                    <h4>Certificado Oficial</h4>
                                    <p>Obtén un certificado digital que avala tu conocimiento al finalizar el curso.</p>
                                </div>
                            </li>
                            <li class="info-item">
                                <svg class="info-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="2" y="3" width="20" height="14" rx="2" ry="2"/>
                                    <line x1="8" y1="21" x2="16" y2="21"/>
                                    <line x1="12" y1="17" x2="12" y2="21"/>
                                </svg>
                                <div class="info-content">
                                    <h4>Videoconferencias Exclusivas</h4>
                                    <p>Acceso por 1 año al grupo de Facebook "Manejo Holístico Tropical" para resolver dudas.</p>
                                </div>
                            </li>
                        </ul>
                    </div>

                    <!-- Right Column: Price and CTA -->
                    <div class="course-info-right">
                        <div class="price-badge">🎯 Requisito para Bonos de Carbono</div>
                        <h3 class="price-title">Inversión Única</h3>
                        <div class="price-amount">
                            <span class="price-number">$<?= number_format($config['app']['amount'] / 100, 0) ?></span>
                            <span class="price-currency">MXN</span>
                        </div>
                        <p style="color: #718096; max-width: 300px; margin-bottom: 24px;">
                            Este curso es el primer paso para unirte a nuestro programa de Bonos de Carbono y generar ingresos adicionales.
                        </p>
                        
                        <?php if ($hasAccess): ?>
                            <a href="/mis-videos.php" class="btn btn-xl" style="width: 100%; background: #38a169; color: white; text-align: center; display: block;">
                                ✅ Acceder a Mis Videos
                            </a>
                        <?php elseif ($isLoggedIn): ?>
                            <div id="paypal-button-container-2"></div>
                        <?php else: ?>
                            <a href="/register.php" class="btn btn-xl" style="width: 100%; background: #667eea; color: white; text-align: center; display: block;">
                                Inscribirme Ahora
                            </a>
                        <?php endif; ?>
                        
                        <p style="font-size: 14px; color: #718096; text-align: center; margin-top: 12px;">
                            Acceso inmediato después del pago
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Syllabus Section -->
    <?php include __DIR__ . '/sections/syllabus.php'; ?>

    <!-- Bonos de Carbono Section -->
    <?php include __DIR__ . '/sections/bonos-carbono.php'; ?>

    <!-- Gallery Section -->
    <?php include __DIR__ . '/sections/gallery.php'; ?>

    <!-- Contact & Footer -->
    <?php include __DIR__ . '/sections/footer.php'; ?>

    <!-- PayPal Smart Button Script -->
    <?php if ($isLoggedIn && !$hasAccess): ?>
    <script src="https://www.paypal.com/sdk/js?client-id=<?= e($config['paypal']['client_id']) ?>&currency=MXN"
            nonce="<?= e(csp_nonce()) ?>"></script>
    <script nonce="<?= e(csp_nonce()) ?>">
        const csrf = "<?= e(csrf_token()) ?>";
        
        function createPayPalButtons(containerId) {
            paypal.Buttons({
                createOrder: () =>
                    fetch('/checkout/create-order.php', {
                        method: 'POST',
                        headers: {'Content-Type':'application/json'},
                        body: JSON.stringify({ csrf })
                    }).then(r => r.json()).then(d => d.orderID),
                onApprove: (data) =>
                    fetch('/checkout/capture-order.php', {
                        method: 'POST',
                        headers: {'Content-Type':'application/json'},
                        body: JSON.stringify({ csrf, orderID: data.orderID })
                    }).then(r => r.json()).then(d => {
                        if (d.ok) {
                            window.location.href = '/success.php';
                        } else {
                            alert(d.error || 'No se pudo confirmar el pago');
                        }
                    }),
                onError: (err) => {
                    console.error('PayPal Error:', err);
                    alert('Error de PayPal: ' + err);
                }
            }).render(containerId);
        }
        
        // Renderizar botones en ambas ubicaciones
        createPayPalButtons('#paypal-button-container');
        createPayPalButtons('#paypal-button-container-2');
    </script>
    <?php endif; ?>
</body>
</html>