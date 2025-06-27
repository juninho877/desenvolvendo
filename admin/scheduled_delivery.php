<?php
/**
 * Script para envio agendado de banners para o Telegram
 * Este script deve ser executado via cron job a cada minuto
 * 
 * Exemplo de cron:
 * * * * * * php /caminho/para/admin/scheduled_delivery.php
 */

// Desativar exibição de erros
ini_set('display_errors', 0);
error_reporting(E_ALL & ~E_NOTICE);

// Definir timezone
date_default_timezone_set('America/Sao_Paulo');

// Verificar se está sendo executado via CLI ou com parâmetro de autenticação
$isAuthorized = (php_sapi_name() === 'cli');

// Se não for CLI, verificar token de autenticação
if (!$isAuthorized && isset($_GET['auth_token'])) {
    $configAuthToken = 'futbanner_scheduled_delivery_token'; // Token fixo para autenticação
    $isAuthorized = ($_GET['auth_token'] === $configAuthToken);
}

if (!$isAuthorized) {
    header('HTTP/1.0 403 Forbidden');
    echo "Acesso não autorizado";
    exit;
}

// Incluir classes necessárias
require_once __DIR__ . '/classes/TelegramSettings.php';
require_once __DIR__ . '/classes/TelegramService.php';
require_once __DIR__ . '/includes/banner_functions.php';

// Função para registrar logs
function logMessage($message) {
    $logFile = __DIR__ . '/logs/scheduled_delivery.log';
    $logDir = dirname($logFile);
    
    // Criar diretório de logs se não existir
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[{$timestamp}] {$message}" . PHP_EOL;
    
    // Limitar tamanho do arquivo de log (1MB)
    if (file_exists($logFile) && filesize($logFile) > 1048576) {
        $oldContent = file_get_contents($logFile);
        $lines = explode(PHP_EOL, $oldContent);
        $newContent = implode(PHP_EOL, array_slice($lines, count($lines) / 2));
        file_put_contents($logFile, $newContent);
    }
    
    file_put_contents($logFile, $logMessage, FILE_APPEND);
    
    // Se estiver rodando via CLI, exibir mensagem no console
    if (php_sapi_name() === 'cli') {
        echo $logMessage;
    }
}

// Iniciar processamento
logMessage("Iniciando verificação de envios agendados");

try {
    // Obter hora atual
    $currentTime = date('H:i');
    logMessage("Hora atual: {$currentTime}");
    
    // Inicializar classes
    $telegramSettings = new TelegramSettings();
    $telegramService = new TelegramService();
    
    // Buscar usuários com envio agendado para este horário
    $usersToSend = $telegramSettings->getUsersWithScheduledDelivery($currentTime);
    $totalUsers = count($usersToSend);
    
    logMessage("Encontrados {$totalUsers} usuários com envio agendado para {$currentTime}");
    
    if ($totalUsers === 0) {
        logMessage("Nenhum envio agendado para este horário. Finalizando.");
        exit;
    }
    
    // Obter jogos do dia (uma vez só para todos os usuários)
    $jogos = obterJogosDeHoje();
    
    if (empty($jogos)) {
        logMessage("Nenhum jogo disponível para hoje. Finalizando.");
        exit;
    }
    
    logMessage("Encontrados " . count($jogos) . " jogos para hoje");
    
    // Processar cada usuário
    foreach ($usersToSend as $index => $user) {
        $userId = $user['user_id'];
        $theme = $user['scheduled_football_theme'];
        
        logMessage("Processando usuário ID {$userId} - Tema {$theme} - {$index+1}/{$totalUsers}");
        
        // Determinar tipo de banner baseado no tema
        $bannerType = 'football_' . $theme;
        
        // Gerar e enviar banners
        $result = $telegramService->generateAndSendBanners($userId, $bannerType, $jogos);
        
        if ($result['success']) {
            logMessage("✅ Sucesso para usuário {$userId}: {$result['message']}");
        } else {
            logMessage("❌ Erro para usuário {$userId}: {$result['message']}");
        }
        
        // Pequena pausa entre envios para não sobrecarregar
        if ($index < $totalUsers - 1) {
            sleep(2);
        }
    }
    
    logMessage("Processamento concluído para {$totalUsers} usuários");
    
} catch (Exception $e) {
    logMessage("❌ ERRO CRÍTICO: " . $e->getMessage());
    logMessage("Trace: " . $e->getTraceAsString());
}

logMessage("Finalizado");
exit;
?>