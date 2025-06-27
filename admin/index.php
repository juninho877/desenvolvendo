<?php
session_start();
if (!isset($_SESSION["usuario"])) {
    header("Location: login.php");
    exit();
}

// Incluir funções necessárias para obter dados reais
require_once 'includes/banner_functions.php';
require_once 'classes/BannerStats.php';

// Obter dados reais dos jogos
$jogos = obterJogosDeHoje();
$totalJogosHoje = count($jogos);

// Obter estatísticas de banners
$bannerStats = new BannerStats();

// Se for admin, mostrar estatísticas globais, senão mostrar apenas do usuário
if ($_SESSION['role'] === 'admin') {
    $globalStats = $bannerStats->getGlobalBannerStats();
    $userBannerStats = $bannerStats->getUserBannerStats($_SESSION['user_id']);
    $isAdmin = true;
} else {
    $userBannerStats = $bannerStats->getUserBannerStats($_SESSION['user_id']);
    $isAdmin = false;
}

$pageTitle = "Página Inicial";
include "includes/header.php";
?>

<div class="page-header">
    <h1 class="page-title">Dashboard <?php echo $isAdmin ? '- Administrador' : ''; ?></h1>
    <p class="page-subtitle">
        Bem-vindo de volta, <?php echo htmlspecialchars($_SESSION["usuario"]); ?>! 
        <?php echo $isAdmin ? 'Gerencie o sistema e monitore todas as atividades.' : 'Gerencie seus banners e configurações.'; ?>
    </p>
</div>

<!-- Stats Cards -->
<?php if ($isAdmin): ?>
    <!-- Admin Global Stats -->
    <div class="admin-stats-section mb-6">
        <h2 class="section-title">
            <i class="fas fa-globe text-primary-500 mr-2"></i>
            Estatísticas Globais do Sistema
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="card admin-stat-card">
                <div class="card-body">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-muted">Total de Banners</p>
                            <p class="text-3xl font-bold text-primary"><?php echo number_format($globalStats['total_banners']); ?></p>
                        </div>
                        <div class="w-14 h-14 bg-primary-50 rounded-lg flex items-center justify-center">
                            <i class="fas fa-images text-primary-500 text-xl"></i>
                        </div>
                    </div>
                    <div class="mt-3">
                        <span class="text-xs text-primary-600 font-medium">
                            <i class="fas fa-chart-line mr-1"></i>
                            Todos os usuários
                        </span>
                    </div>
                </div>
            </div>

            <div class="card admin-stat-card">
                <div class="card-body">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-muted">Banners Hoje</p>
                            <p class="text-3xl font-bold text-success-500"><?php echo number_format($globalStats['today_banners']); ?></p>
                        </div>
                        <div class="w-14 h-14 bg-success-50 rounded-lg flex items-center justify-center">
                            <i class="fas fa-calendar-day text-success-500 text-xl"></i>
                        </div>
                    </div>
                    <div class="mt-3">
                        <span class="text-xs text-success-600 font-medium">
                            <i class="fas fa-clock mr-1"></i>
                            Últimas 24h
                        </span>
                    </div>
                </div>
            </div>

            <div class="card admin-stat-card">
                <div class="card-body">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-muted">Este Mês</p>
                            <p class="text-3xl font-bold text-warning-500"><?php echo number_format($globalStats['month_banners']); ?></p>
                        </div>
                        <div class="w-14 h-14 bg-warning-50 rounded-lg flex items-center justify-center">
                            <i class="fas fa-calendar text-warning-500 text-xl"></i>
                        </div>
                    </div>
                    <div class="mt-3">
                        <span class="text-xs text-warning-600 font-medium">
                            <i class="fas fa-calendar mr-1"></i>
                            <?php echo date('M Y'); ?>
                        </span>
                    </div>
                </div>
            </div>

            <div class="card admin-stat-card">
                <div class="card-body">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-muted">Usuários Ativos</p>
                            <p class="text-3xl font-bold text-info-500"><?php echo number_format($globalStats['active_users']); ?></p>
                        </div>
                        <div class="w-14 h-14 bg-info-50 rounded-lg flex items-center justify-center">
                            <i class="fas fa-users text-info-500 text-xl"></i>
                        </div>
                    </div>
                    <div class="mt-3">
                        <span class="text-xs text-info-600 font-medium">
                            <i class="fas fa-user-check mr-1"></i>
                            Geraram banners
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Admin Personal Stats -->
    <div class="admin-personal-section mb-6">
        <h2 class="section-title">
            <i class="fas fa-user text-secondary mr-2"></i>
            Suas Estatísticas Pessoais
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <div class="card">
                <div class="card-body">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-muted">Seus Banners Hoje</p>
                            <p class="text-2xl font-bold text-primary"><?php echo $userBannerStats['today_banners']; ?></p>
                        </div>
                        <div class="w-12 h-12 bg-primary-50 rounded-lg flex items-center justify-center">
                            <i class="fas fa-image text-primary-500"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-muted">Jogos Hoje</p>
                            <p class="text-2xl font-bold text-success-500"><?php echo $totalJogosHoje; ?></p>
                        </div>
                        <div class="w-12 h-12 bg-success-50 rounded-lg flex items-center justify-center">
                            <i class="fas fa-futbol text-success-500"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-muted">Seus Banners Este Mês</p>
                            <p class="text-2xl font-bold text-warning-500"><?php echo $userBannerStats['month_banners']; ?></p>
                        </div>
                        <div class="w-12 h-12 bg-warning-50 rounded-lg flex items-center justify-center">
                            <i class="fas fa-chart-line text-warning-500"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-muted">Seus Banners Total</p>
                            <p class="text-2xl font-bold text-info-500"><?php echo $userBannerStats['total_banners']; ?></p>
                        </div>
                        <div class="w-12 h-12 bg-info-50 rounded-lg flex items-center justify-center">
                            <i class="fas fa-trophy text-info-500"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php else: ?>
    <!-- Regular User Stats -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
        <div class="card">
            <div class="card-body">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-muted">Banners Gerados Hoje</p>
                        <p class="text-2xl font-bold text-primary"><?php echo $userBannerStats['today_banners']; ?></p>
                    </div>
                    <div class="w-12 h-12 bg-primary-50 rounded-lg flex items-center justify-center">
                        <i class="fas fa-image text-primary-500"></i>
                    </div>
                </div>
                <div class="mt-2">
                    <span class="text-xs text-primary-600 font-medium">
                        <i class="fas fa-calendar-day mr-1"></i>
                        Hoje
                    </span>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-muted">Jogos Hoje</p>
                        <p class="text-2xl font-bold text-success-500"><?php echo $totalJogosHoje; ?></p>
                    </div>
                    <div class="w-12 h-12 bg-success-50 rounded-lg flex items-center justify-center">
                        <i class="fas fa-futbol text-success-500"></i>
                    </div>
                </div>
                <div class="mt-2">
                    <?php if ($totalJogosHoje > 0): ?>
                        <span class="text-xs text-success-600 font-medium">
                            <i class="fas fa-check-circle mr-1"></i>
                            Dados atualizados
                        </span>
                    <?php else: ?>
                        <span class="text-xs text-muted font-medium">
                            <i class="fas fa-info-circle mr-1"></i>
                            Nenhum jogo hoje
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-muted">Total Este Mês</p>
                        <p class="text-2xl font-bold text-warning-500"><?php echo $userBannerStats['month_banners']; ?></p>
                    </div>
                    <div class="w-12 h-12 bg-warning-50 rounded-lg flex items-center justify-center">
                        <i class="fas fa-chart-line text-warning-500"></i>
                    </div>
                </div>
                <div class="mt-2">
                    <span class="text-xs text-warning-600 font-medium">
                        <i class="fas fa-calendar mr-1"></i>
                        <?php echo date('M Y'); ?>
                    </span>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-muted">Total Geral</p>
                        <p class="text-2xl font-bold text-info-500"><?php echo $userBannerStats['total_banners']; ?></p>
                    </div>
                    <div class="w-12 h-12 bg-info-50 rounded-lg flex items-center justify-center">
                        <i class="fas fa-trophy text-info-500"></i>
                    </div>
                </div>
                <div class="mt-2">
                    <span class="text-xs text-info-600 font-medium">
                        <i class="fas fa-star mr-1"></i>
                        Todos os tempos
                    </span>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Quick Actions -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Ações Rápidas</h3>
            <p class="card-subtitle">Acesse rapidamente as funcionalidades principais</p>
        </div>
        <div class="card-body">
            <div class="grid grid-cols-1 gap-3">
                <a href="painel.php" class="btn btn-primary">
                    <i class="fas fa-film"></i>
                    Gerar Banner Filme/Série
                </a>
                <a href="futbanner.php" class="btn btn-secondary">
                    <i class="fas fa-futbol"></i>
                    Gerar Banner Futebol
                </a>
                <?php if ($isAdmin): ?>
                <a href="user_management.php" class="btn btn-warning">
                    <i class="fas fa-users"></i>
                    Gerenciar Usuários
                </a>
                <?php endif; ?>
                <a href="setting.php" class="btn btn-secondary">
                    <i class="fas fa-cog"></i>
                    Configurações
                </a>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Personalização</h3>
            <p class="card-subtitle">Configure a aparência dos seus banners</p>
        </div>
        <div class="card-body">
            <div class="grid grid-cols-1 gap-3">
                <a href="logo.php" class="btn btn-secondary">
                    <i class="fas fa-image"></i>
                    Gerenciar Logos Futebol
                </a>
                <a href="logo_movie.php" class="btn btn-secondary">
                    <i class="fas fa-film"></i>
                    Gerenciar Logo Filmes/Séries
                </a>
                <a href="background.php" class="btn btn-secondary">
                    <i class="fas fa-photo-video"></i>
                    Gerenciar Fundos
                </a>
                <a href="card.php" class="btn btn-secondary">
                    <i class="fas fa-th-large"></i>
                    Gerenciar Cards
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Jogos de Hoje (se houver) -->
<?php if ($totalJogosHoje > 0): ?>
<div class="card mb-6">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-futbol text-success-500 mr-2"></i>
            Jogos de Hoje
        </h3>
        <p class="card-subtitle"><?php echo $totalJogosHoje; ?> jogos disponíveis para gerar banners</p>
    </div>
    <div class="card-body">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php 
            // Mostrar apenas os primeiros 6 jogos no dashboard
            $jogosLimitados = array_slice($jogos, 0, 6);
            foreach ($jogosLimitados as $jogo): 
                $time1 = $jogo['time1'] ?? 'Time 1';
                $time2 = $jogo['time2'] ?? 'Time 2';
                $liga = $jogo['competicao'] ?? 'Liga';
                $hora = $jogo['horario'] ?? '';
            ?>
                <div class="game-card">
                    <div class="game-header">
                        <span class="league-name"><?php echo htmlspecialchars($liga); ?></span>
                        <span class="game-time"><?php echo htmlspecialchars($hora); ?></span>
                    </div>
                    <div class="game-teams">
                        <div class="team">
                            <span class="team-name"><?php echo htmlspecialchars($time1); ?></span>
                        </div>
                        <div class="vs">VS</div>
                        <div class="team">
                            <span class="team-name"><?php echo htmlspecialchars($time2); ?></span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <?php if ($totalJogosHoje > 6): ?>
            <div class="text-center mt-4">
                <a href="jogos_hoje.php" class="btn btn-primary">
                    <i class="fas fa-list"></i>
                    Ver Todos os <?php echo $totalJogosHoje; ?> Jogos
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<!-- Recent Activity -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Atividade Recente</h3>
        <p class="card-subtitle">Últimas ações realizadas no sistema</p>
    </div>
    <div class="card-body">
        <div class="space-y-4">
            <div class="flex items-center gap-4 p-3 bg-gray-50 rounded-lg">
                <div class="w-10 h-10 bg-primary-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-sign-in-alt text-primary-600 text-sm"></i>
                </div>
                <div class="flex-1">
                    <p class="font-medium">Login realizado</p>
                    <p class="text-sm text-muted">Acesso ao painel - agora</p>
                </div>
            </div>
            
            <?php if ($totalJogosHoje > 0): ?>
            <div class="flex items-center gap-4 p-3 bg-gray-50 rounded-lg">
                <div class="w-10 h-10 bg-success-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-futbol text-success-600 text-sm"></i>
                </div>
                <div class="flex-1">
                    <p class="font-medium">Jogos atualizados</p>
                    <p class="text-sm text-muted"><?php echo $totalJogosHoje; ?> jogos disponíveis para hoje</p>
                </div>
            </div>
            <?php endif; ?>
            
            <?php 
            // Mostrar banners recentes
            $recentBanners = $bannerStats->getRecentBanners($_SESSION['user_id'], 3);
            foreach ($recentBanners as $banner): 
                $bannerTypeText = $banner['banner_type'] === 'movie' ? 'Filme/Série' : 'Futebol';
                $bannerIcon = $banner['banner_type'] === 'movie' ? 'fa-film' : 'fa-futbol';
                $timeAgo = time() - strtotime($banner['generated_at']);
                $timeText = $timeAgo < 3600 ? 'há ' . floor($timeAgo/60) . ' min' : 'há ' . floor($timeAgo/3600) . 'h';
            ?>
            <div class="flex items-center gap-4 p-3 bg-gray-50 rounded-lg">
                <div class="w-10 h-10 bg-info-100 rounded-full flex items-center justify-center">
                    <i class="fas <?php echo $bannerIcon; ?> text-info-600 text-sm"></i>
                </div>
                <div class="flex-1">
                    <p class="font-medium">Banner <?php echo $bannerTypeText; ?> gerado</p>
                    <p class="text-sm text-muted">
                        <?php echo $banner['content_name'] ? htmlspecialchars($banner['content_name']) : 'Banner personalizado'; ?> - <?php echo $timeText; ?>
                    </p>
                </div>
            </div>
            <?php endforeach; ?>
            
            <?php if (empty($recentBanners)): ?>
            <div class="flex items-center gap-4 p-3 bg-gray-50 rounded-lg">
                <div class="w-10 h-10 bg-info-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-chart-line text-info-600 text-sm"></i>
                </div>
                <div class="flex-1">
                    <p class="font-medium">Sistema atualizado</p>
                    <p class="text-sm text-muted">Dashboard com dados em tempo real</p>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
    /* Cores adicionais para info */
    :root {
        --info-50: #eff6ff;
        --info-500: #3b82f6;
        --info-600: #2563eb;
    }

    /* Seções específicas do admin */
    .section-title {
        font-size: 1.25rem;
        font-weight: 700;
        color: var(--text-primary);
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        padding-bottom: 0.5rem;
        border-bottom: 2px solid var(--border-color);
    }

    .admin-stats-section {
        background: linear-gradient(135deg, var(--primary-50), var(--primary-100));
        border-radius: var(--border-radius);
        padding: 2rem;
        border: 1px solid var(--primary-200);
    }

    [data-theme="dark"] .admin-stats-section {
        background: linear-gradient(135deg, rgba(59, 130, 246, 0.05), rgba(59, 130, 246, 0.1));
        border-color: rgba(59, 130, 246, 0.2);
    }

    .admin-stat-card {
        border: 2px solid var(--primary-200);
        background: var(--bg-primary);
        transition: all 0.3s ease;
    }

    .admin-stat-card:hover {
        border-color: var(--primary-500);
        transform: translateY(-2px);
        box-shadow: var(--shadow-lg);
    }

    [data-theme="dark"] .admin-stat-card {
        border-color: rgba(59, 130, 246, 0.2);
    }

    .admin-personal-section {
        background: var(--bg-secondary);
        border-radius: var(--border-radius);
        padding: 2rem;
        border: 1px solid var(--border-color);
    }

    [data-theme="dark"] .bg-gray-50 {
        background-color: var(--bg-tertiary);
    }
    
    .space-y-4 > * + * {
        margin-top: 1rem;
    }

    /* Estilos para os cards de jogos */
    .game-card {
        background: var(--bg-secondary);
        border: 1px solid var(--border-color);
        border-radius: var(--border-radius-sm);
        padding: 1rem;
        transition: var(--transition);
    }

    .game-card:hover {
        background: var(--bg-tertiary);
        transform: translateY(-2px);
        box-shadow: var(--shadow-md);
    }

    .game-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 0.75rem;
        padding-bottom: 0.5rem;
        border-bottom: 1px solid var(--border-color);
    }

    .league-name {
        font-size: 0.75rem;
        font-weight: 600;
        color: var(--primary-600);
        text-transform: uppercase;
        letter-spacing: 0.025em;
    }

    .game-time {
        font-size: 0.75rem;
        font-weight: 500;
        color: var(--text-muted);
        background: var(--bg-primary);
        padding: 0.25rem 0.5rem;
        border-radius: 4px;
    }

    .game-teams {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.5rem;
    }

    .team {
        flex: 1;
        text-align: center;
    }

    .team-name {
        font-size: 0.875rem;
        font-weight: 600;
        color: var(--text-primary);
        display: block;
    }

    .vs {
        font-size: 0.75rem;
        font-weight: 700;
        color: var(--text-muted);
        background: var(--bg-primary);
        padding: 0.25rem 0.5rem;
        border-radius: 50%;
        min-width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    /* Utilitários */
    .text-info-500 {
        color: var(--info-500);
    }

    .text-info-600 {
        color: var(--info-600);
    }

    .bg-info-50 {
        background-color: var(--info-50);
    }

    .bg-info-100 {
        background-color: rgba(59, 130, 246, 0.1);
    }

    [data-theme="dark"] .bg-info-100 {
        background-color: rgba(59, 130, 246, 0.1);
    }

    [data-theme="dark"] .league-name {
        color: var(--primary-400);
    }

    .mr-2 {
        margin-right: 0.5rem;
    }

    .mb-8 {
        margin-bottom: 2rem;
    }

    .text-xl {
        font-size: 1.25rem;
    }

    .w-14 {
        width: 3.5rem;
    }

    .h-14 {
        height: 3.5rem;
    }

    .text-3xl {
        font-size: 1.875rem;
        line-height: 2.25rem;
    }

    .mt-3 {
        margin-top: 0.75rem;
    }
</style>

<?php include "includes/footer.php"; ?>