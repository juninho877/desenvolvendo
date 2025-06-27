<?php
session_start();
date_default_timezone_set('America/Sao_Paulo');
if (!isset($_SESSION["usuario"])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - Painel' : 'Painel Administrativo'; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* CSS Variables for Theme Management */
        :root {
            /* Light Theme */
            --bg-primary: #ffffff;
            --bg-secondary: #f8fafc;
            --bg-tertiary: #f1f5f9;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --text-muted: #94a3b8;
            --border-color: #e2e8f0;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
            --shadow-xl: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
            
            /* Brand Colors */
            --primary-50: #eff6ff;
            --primary-100: #dbeafe;
            --primary-500: #3b82f6;
            --primary-600: #2563eb;
            --primary-700: #1d4ed8;
            --primary-900: #1e3a8a;
            
            /* Status Colors */
            --success-50: #f0fdf4;
            --success-500: #22c55e;
            --success-600: #16a34a;
            --danger-50: #fef2f2;
            --danger-500: #ef4444;
            --danger-600: #dc2626;
            --warning-50: #fffbeb;
            --warning-500: #f59e0b;
            --warning-600: #d97706;
            
            /* Layout */
            --sidebar-width: 280px;
            --header-height: 70px;
            --border-radius: 12px;
            --border-radius-sm: 8px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Dark Theme */
        [data-theme="dark"] {
            --bg-primary: #0f172a;
            --bg-secondary: #1e293b;
            --bg-tertiary: #334155;
            --text-primary: #f8fafc;
            --text-secondary: #cbd5e1;
            --text-muted: #64748b;
            --border-color: #334155;
        }

        /* Reset and Base Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background-color: var(--bg-secondary);
            color: var(--text-primary);
            line-height: 1.6;
            transition: var(--transition);
        }

        /* Layout Structure */
        .admin-layout {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Styles */
        .sidebar {
            width: var(--sidebar-width);
            background: var(--bg-primary);
            border-right: 1px solid var(--border-color);
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            z-index: 1000;
            transform: translateX(0);
            transition: var(--transition);
            display: flex;
            flex-direction: column;
            box-shadow: var(--shadow-lg);
        }

        .sidebar.collapsed {
            transform: translateX(-100%);
        }

        /* Sidebar Header */
        .sidebar-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .sidebar-logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-primary);
            text-decoration: none;
        }

        .sidebar-logo i {
            width: 32px;
            height: 32px;
            background: linear-gradient(135deg, var(--primary-500), var(--primary-600));
            border-radius: var(--border-radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1rem;
        }

        /* Theme Toggle */
        .theme-toggle {
            background: none;
            border: none;
            color: var(--text-secondary);
            cursor: pointer;
            padding: 0.5rem;
            border-radius: var(--border-radius-sm);
            transition: var(--transition);
        }

        .theme-toggle:hover {
            background: var(--bg-tertiary);
            color: var(--text-primary);
        }

        /* Sidebar Navigation */
        .sidebar-nav {
            flex: 1;
            padding: 1rem 0;
            overflow-y: auto;
        }

        .nav-section {
            margin-bottom: 2rem;
        }

        .nav-section-title {
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-muted);
            padding: 0 1.5rem;
            margin-bottom: 0.5rem;
        }

        .nav-item {
            display: flex;
            align-items: center;
            padding: 0.75rem 1.5rem;
            color: var(--text-secondary);
            text-decoration: none;
            transition: var(--transition);
            position: relative;
            font-weight: 500;
        }

        .nav-item:hover {
            background: var(--bg-tertiary);
            color: var(--text-primary);
        }

        .nav-item.active {
            background: var(--primary-50);
            color: var(--primary-700);
            font-weight: 600;
        }

        [data-theme="dark"] .nav-item.active {
            background: rgba(59, 130, 246, 0.1);
            color: var(--primary-400);
        }

        .nav-item.active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 3px;
            background: var(--primary-500);
        }

        .nav-item i {
            width: 20px;
            margin-right: 0.75rem;
            font-size: 1.125rem;
        }

        /* Sidebar Footer */
        .sidebar-footer {
            padding: 1rem 1.5rem;
            border-top: 1px solid var(--border-color);
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem;
            background: var(--bg-tertiary);
            border-radius: var(--border-radius);
            margin-bottom: 0.75rem;
        }

        .user-avatar {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, var(--primary-500), var(--primary-600));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 0.875rem;
        }

        .user-details h4 {
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--text-primary);
        }

        .user-details p {
            font-size: 0.75rem;
            color: var(--text-muted);
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: var(--sidebar-width);
            transition: var(--transition);
        }

        .main-content.expanded {
            margin-left: 0;
        }

        /* Top Header */
        .top-header {
            height: var(--header-height);
            background: var(--bg-primary);
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 2rem;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: var(--shadow-sm);
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            color: var(--text-secondary);
            cursor: pointer;
            padding: 0.5rem;
            border-radius: var(--border-radius-sm);
            transition: var(--transition);
        }

        .mobile-menu-btn:hover {
            background: var(--bg-tertiary);
            color: var(--text-primary);
        }

        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-muted);
            font-size: 0.875rem;
        }

        .breadcrumb a {
            color: var(--text-secondary);
            text-decoration: none;
            transition: var(--transition);
        }

        .breadcrumb a:hover {
            color: var(--primary-500);
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        /* Content Area */
        .content-area {
            padding: 2rem;
        }

        .page-header {
            margin-bottom: 2rem;
        }

        .page-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .page-subtitle {
            color: var(--text-secondary);
            font-size: 1rem;
        }

        /* Cards */
        .card {
            background: var(--bg-primary);
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-sm);
            transition: var(--transition);
        }

        .card:hover {
            box-shadow: var(--shadow-md);
        }

        .card-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border-color);
        }

        .card-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.25rem;
        }

        .card-subtitle {
            color: var(--text-secondary);
            font-size: 0.875rem;
        }

        .card-body {
            padding: 1.5rem;
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            font-size: 0.875rem;
            font-weight: 500;
            border-radius: var(--border-radius-sm);
            border: none;
            cursor: pointer;
            text-decoration: none;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }

        .btn-primary {
            background: var(--primary-500);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-600);
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
        }

        .btn-secondary {
            background: var(--bg-tertiary);
            color: var(--text-primary);
            border: 1px solid var(--border-color);
        }

        .btn-secondary:hover {
            background: var(--bg-secondary);
        }

        .btn-success {
            background: var(--success-500);
            color: white;
        }

        .btn-success:hover {
            background: var(--success-600);
        }

        .btn-danger {
            background: var(--danger-500);
            color: white;
        }

        .btn-danger:hover {
            background: var(--danger-600);
        }

        /* Form Elements */
        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .form-input {
            width: 100%;
            padding: 0.75rem 1rem;
            background: var(--bg-primary);
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius-sm);
            color: var(--text-primary);
            font-size: 0.875rem;
            transition: var(--transition);
        }

        .form-input:focus {
            outline: none;
            border-color: var(--primary-500);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .form-select {
            appearance: none;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
            background-position: right 0.5rem center;
            background-repeat: no-repeat;
            background-size: 1.5em 1.5em;
            padding-right: 2.5rem;
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.open {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }

            .mobile-menu-btn {
                display: block;
            }

            .content-area {
                padding: 1rem;
            }
        }

        @media (max-width: 640px) {
            .top-header {
                padding: 0 1rem;
            }

            .page-title {
                font-size: 1.5rem;
            }

            .card-header,
            .card-body {
                padding: 1rem;
            }
        }

        /* Overlay for mobile */
        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
            opacity: 0;
            visibility: hidden;
            transition: var(--transition);
        }

        .overlay.active {
            opacity: 1;
            visibility: visible;
        }

        /* Animations */
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-slide-in {
            animation: slideIn 0.3s ease-out;
        }

        /* Utilities */
        .flex {
            display: flex;
        }

        .items-center {
            align-items: center;
        }

        .justify-between {
            justify-content: space-between;
        }

        .gap-4 {
            gap: 1rem;
        }

        .text-sm {
            font-size: 0.875rem;
        }

        .text-xs {
            font-size: 0.75rem;
        }

        .font-medium {
            font-weight: 500;
        }

        .font-semibold {
            font-weight: 600;
        }

        .text-muted {
            color: var(--text-muted);
        }

        .mb-4 {
            margin-bottom: 1rem;
        }

        .mb-6 {
            margin-bottom: 1.5rem;
        }

        .grid {
            display: grid;
        }

        .grid-cols-1 {
            grid-template-columns: repeat(1, minmax(0, 1fr));
        }

        .grid-cols-2 {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .grid-cols-3 {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .gap-6 {
            gap: 1.5rem;
        }

        @media (min-width: 768px) {
            .md\\:grid-cols-2 {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (min-width: 1024px) {
            .lg\\:grid-cols-3 {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <a href="index.php" class="sidebar-logo">
                    <i class="fas fa-futbol"></i>
                    <span>FutBanner</span>
                </a>
                <button class="theme-toggle" id="themeToggle">
                    <i class="fas fa-moon"></i>
                </button>
            </div>

            <nav class="sidebar-nav">
                <div class="nav-section">
                    <div class="nav-section-title">Principal</div>
                    <a href="index.php" class="nav-item">
                        <i class="fas fa-home"></i>
                        <span>Dashboard</span>
                    </a>
                </div>

                <div class="nav-section">
                    <div class="nav-section-title">Geradores</div>
                    <a href="painel.php" class="nav-item">
                        <i class="fas fa-film"></i>
                        <span>Banner Filmes/Séries</span>
                    </a>
                    <a href="futbanner.php" class="nav-item">
                        <i class="fas fa-futbol"></i>
                        <span>Banner Futebol</span>
                    </a>
                </div>

                <div class="nav-section">
                    <div class="nav-section-title">Personalização</div>
                    <a href="logo.php" class="nav-item">
                        <i class="fas fa-image"></i>
                        <span>Logos Futebol</span>
                    </a>
                    <a href="logo_movie.php" class="nav-item">
                        <i class="fas fa-film"></i>
                        <span>Logo Filmes/Séries</span>
                    </a>
                    <a href="background.php" class="nav-item">
                        <i class="fas fa-photo-video"></i>
                        <span>Fundos</span>
                    </a>
                    <a href="card.php" class="nav-item">
                        <i class="fas fa-th-large"></i>
                        <span>Cards</span>
                    </a>
                </div>

                <div class="nav-section">
                    <div class="nav-section-title">Integração</div>
                    <a href="telegram.php" class="nav-item">
                        <i class="fab fa-telegram"></i>
                        <span>Telegram</span>
                    </a>
                </div>

                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                <div class="nav-section">
                    <div class="nav-section-title">Administração</div>
                    <a href="user_management.php" class="nav-item">
                        <i class="fas fa-users"></i>
                        <span>Gerenciar Usuários</span>
                    </a>
                </div>
                <?php endif; ?>

                <div class="nav-section">
                    <div class="nav-section-title">Sistema</div>
                    <a href="setting.php" class="nav-item">
                        <i class="fas fa-cog"></i>
                        <span>Configurações</span>
                    </a>
                </div>
            </nav>

            <div class="sidebar-footer">
                <div class="user-info">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($_SESSION["usuario"], 0, 2)); ?>
                    </div>
                    <div class="user-details">
                        <h4><?php echo htmlspecialchars($_SESSION["usuario"]); ?></h4>
                        <p><?php echo isset($_SESSION['role']) && $_SESSION['role'] === 'admin' ? 'Administrador' : 'Usuário'; ?></p>
                    </div>
                </div>
                <a href="logout.php" class="nav-item">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Sair</span>
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content" id="mainContent">
            <!-- Top Header -->
            <header class="top-header">
                <div class="header-left">
                    <button class="mobile-menu-btn" id="mobileMenuBtn">
                        <i class="fas fa-bars"></i>
                    </button>
                    <nav class="breadcrumb">
                        <a href="index.php">Dashboard</a>
                        <?php if (isset($pageTitle) && $pageTitle !== 'Página Inicial'): ?>
                            <i class="fas fa-chevron-right text-xs"></i>
                            <span><?php echo $pageTitle; ?></span>
                        <?php endif; ?>
                    </nav>
                </div>
                <div class="header-right">
                    <span class="text-sm text-muted">
                        <?php echo date('d/m/Y H:i'); ?>
                    </span>
                </div>
            </header>

            <!-- Content Area -->
            <div class="content-area">