<?php
/**
 * 📅 Sistema de Envio Agendado de Banners
 * 
 * Este script é executado via cron job para enviar banners automaticamente
 * nos horários agendados pelos usuários.
 * 
 * Configuração do Cron (executar a cada minuto):
 * * * * * * /usr/bin/php /caminho/para/admin/scheduled_delivery.php
 */

// Configurar error reporting e logging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/scheduled_delivery.log');

// Criar diretório de logs se não existir
$logDir = __DIR__ . '/logs';
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

// Função de log personalizada
function writeLog($message, $level = 'INFO') {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] [$level] $message" . PHP_EOL;
    
    // Log no arquivo
    $logFile = __DIR__ . '/logs/scheduled_delivery.log';
    file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
    
    // Log no error_log do sistema também
    error_log("SCHEDULED_DELIVERY [$level]: $message");
}

try {
    writeLog("=== INICIANDO EXECUÇÃO DO ENVIO AGENDADO ===");
    
    // Verificar se é execução via linha de comando ou web
    $isCLI = php_sapi_name() === 'cli';
    writeLog("Modo de execução: " . ($isCLI ? "CLI" : "WEB"));
    
    if (!$isCLI) {
        // Se for via web, verificar autenticação básica ou token
        $validTokens = ['cron_token_2024', 'scheduled_delivery_token'];
        $providedToken = $_GET['token'] ?? $_POST['token'] ?? '';
        
        if (!in_array($providedToken, $validTokens)) {
            writeLog("Acesso negado - Token inválido: $providedToken", 'ERROR');
            http_response_code(403);
            die(json_encode(['error' => 'Token inválido']));
        }
        
        writeLog("Acesso via web autorizado com token válido");
        header('Content-Type: application/json');
    }
    
    // Incluir dependências
    writeLog("Carregando dependências...");
    
    if (!file_exists(__DIR__ . '/config/database.php')) {
        throw new Exception("Arquivo de configuração do banco não encontrado");
    }
    
    require_once __DIR__ . '/config/database.php';
    
    if (!file_exists(__DIR__ . '/classes/TelegramBot.php')) {
        throw new Exception("Classe TelegramBot não encontrada");
    }
    
    require_once __DIR__ . '/classes/TelegramBot.php';
    
    writeLog("Dependências carregadas com sucesso");
    
    // Conectar ao banco de dados
    writeLog("Conectando ao banco de dados...");
    
    try {
        $db = Database::getInstance()->getConnection();
        writeLog("Conexão com banco estabelecida");
    } catch (Exception $e) {
        throw new Exception("Erro de conexão com banco: " . $e->getMessage());
    }
    
    // Verificar se a tabela existe
    writeLog("Verificando estrutura do banco...");
    
    try {
        $stmt = $db->query("SHOW TABLES LIKE 'scheduled_deliveries'");
        if ($stmt->rowCount() === 0) {
            writeLog("Tabela scheduled_deliveries não existe, criando...");
            createScheduledDeliveriesTable($db);
        }
    } catch (Exception $e) {
        writeLog("Erro ao verificar tabela: " . $e->getMessage(), 'ERROR');
        throw $e;
    }
    
    // Buscar envios agendados que devem ser executados agora
    $now = new DateTime();
    $currentTime = $now->format('H:i');
    $currentDate = $now->format('Y-m-d');
    
    writeLog("Buscando envios agendados para $currentDate às $currentTime");
    
    $stmt = $db->prepare("
        SELECT sd.*, u.username 
        FROM scheduled_deliveries sd
        JOIN usuarios u ON sd.user_id = u.id
        WHERE sd.status = 'pending'
        AND sd.scheduled_date = ?
        AND sd.scheduled_time = ?
        AND sd.attempts < 3
    ");
    
    $stmt->execute([$currentDate, $currentTime]);
    $scheduledDeliveries = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    writeLog("Encontrados " . count($scheduledDeliveries) . " envios para processar");
    
    if (empty($scheduledDeliveries)) {
        writeLog("Nenhum envio agendado encontrado para este horário");
        
        if (!$isCLI) {
            echo json_encode([
                'success' => true,
                'message' => 'Nenhum envio agendado para este horário',
                'processed' => 0,
                'timestamp' => $now->format('Y-m-d H:i:s')
            ]);
        }
        
        writeLog("=== EXECUÇÃO FINALIZADA (NENHUM ENVIO) ===");
        exit(0);
    }
    
    // Processar cada envio agendado
    $processedCount = 0;
    $successCount = 0;
    $errorCount = 0;
    
    foreach ($scheduledDeliveries as $delivery) {
        $deliveryId = $delivery['id'];
        $userId = $delivery['user_id'];
        $username = $delivery['username'];
        
        writeLog("Processando envio ID: $deliveryId para usuário: $username");
        
        try {
            // Atualizar tentativas
            $newAttempts = $delivery['attempts'] + 1;
            $updateStmt = $db->prepare("
                UPDATE scheduled_deliveries 
                SET attempts = ?, last_attempt = NOW() 
                WHERE id = ?
            ");
            $updateStmt->execute([$newAttempts, $deliveryId]);
            
            // Verificar se o arquivo ainda existe
            $bannerPath = $delivery['banner_path'];
            if (!file_exists($bannerPath)) {
                throw new Exception("Arquivo do banner não encontrado: $bannerPath");
            }
            
            writeLog("Arquivo do banner encontrado: $bannerPath");
            
            // Inicializar bot do Telegram
            $telegramBot = new TelegramBot();
            
            // Buscar configurações do Telegram do usuário
            $configStmt = $db->prepare("
                SELECT telegram_bot_token, telegram_chat_id 
                FROM user_telegram_config 
                WHERE user_id = ? AND is_active = 1
            ");
            $configStmt->execute([$userId]);
            $telegramConfig = $configStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$telegramConfig) {
                throw new Exception("Configuração do Telegram não encontrada para o usuário");
            }
            
            writeLog("Configuração do Telegram encontrada para usuário $username");
            
            // Configurar bot
            $telegramBot->setBotToken($telegramConfig['telegram_bot_token']);
            $telegramBot->setChatId($telegramConfig['telegram_chat_id']);
            
            // Preparar mensagem
            $message = $delivery['message'] ?: "Banner gerado automaticamente";
            $bannerName = $delivery['banner_name'] ?: basename($bannerPath);
            
            writeLog("Enviando banner via Telegram...");
            
            // Enviar banner
            $result = $telegramBot->sendPhoto($bannerPath, $message, $bannerName);
            
            if ($result['success']) {
                // Marcar como enviado com sucesso
                $updateStmt = $db->prepare("
                    UPDATE scheduled_deliveries 
                    SET status = 'sent', sent_at = NOW(), telegram_message_id = ?
                    WHERE id = ?
                ");
                $updateStmt->execute([$result['message_id'] ?? null, $deliveryId]);
                
                writeLog("Banner enviado com sucesso! ID: $deliveryId");
                $successCount++;
                
                // Remover arquivo após envio bem-sucedido (opcional)
                if ($delivery['delete_after_send']) {
                    if (unlink($bannerPath)) {
                        writeLog("Arquivo removido após envio: $bannerPath");
                    }
                }
                
            } else {
                throw new Exception("Erro no envio via Telegram: " . $result['error']);
            }
            
        } catch (Exception $e) {
            writeLog("Erro ao processar envio ID $deliveryId: " . $e->getMessage(), 'ERROR');
            $errorCount++;
            
            // Se excedeu o número máximo de tentativas, marcar como falhou
            if ($newAttempts >= 3) {
                $updateStmt = $db->prepare("
                    UPDATE scheduled_deliveries 
                    SET status = 'failed', error_message = ?
                    WHERE id = ?
                ");
                $updateStmt->execute([$e->getMessage(), $deliveryId]);
                
                writeLog("Envio ID $deliveryId marcado como falhou após 3 tentativas", 'ERROR');
            }
        }
        
        $processedCount++;
    }
    
    writeLog("Processamento concluído: $processedCount total, $successCount sucessos, $errorCount erros");
    
    // Limpeza: remover registros antigos (mais de 7 dias)
    $cleanupStmt = $db->prepare("
        DELETE FROM scheduled_deliveries 
        WHERE scheduled_date < DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        AND status IN ('sent', 'failed')
    ");
    $cleanupStmt->execute();
    $cleanedCount = $cleanupStmt->rowCount();
    
    if ($cleanedCount > 0) {
        writeLog("Limpeza: removidos $cleanedCount registros antigos");
    }
    
    // Resposta final
    $response = [
        'success' => true,
        'message' => "Processamento concluído",
        'processed' => $processedCount,
        'success_count' => $successCount,
        'error_count' => $errorCount,
        'cleaned_count' => $cleanedCount,
        'timestamp' => $now->format('Y-m-d H:i:s')
    ];
    
    writeLog("=== EXECUÇÃO FINALIZADA COM SUCESSO ===");
    
    if (!$isCLI) {
        echo json_encode($response, JSON_PRETTY_PRINT);
    } else {
        echo "Envio agendado processado: $successCount sucessos, $errorCount erros\n";
    }
    
} catch (Exception $e) {
    $errorMsg = "ERRO FATAL: " . $e->getMessage();
    writeLog($errorMsg, 'FATAL');
    
    if (!$isCLI) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage(),
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    } else {
        echo "ERRO: " . $e->getMessage() . "\n";
        exit(1);
    }
}

/**
 * Criar tabela de envios agendados se não existir
 */
function createScheduledDeliveriesTable($db) {
    $sql = "
    CREATE TABLE IF NOT EXISTS scheduled_deliveries (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        banner_path VARCHAR(500) NOT NULL,
        banner_name VARCHAR(255) NOT NULL,
        message TEXT,
        scheduled_date DATE NOT NULL,
        scheduled_time TIME NOT NULL,
        status ENUM('pending', 'sent', 'failed') DEFAULT 'pending',
        attempts INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        sent_at TIMESTAMP NULL,
        last_attempt TIMESTAMP NULL,
        telegram_message_id VARCHAR(100) NULL,
        error_message TEXT NULL,
        delete_after_send BOOLEAN DEFAULT FALSE,
        
        FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE CASCADE,
        INDEX idx_scheduled_datetime (scheduled_date, scheduled_time),
        INDEX idx_status (status),
        INDEX idx_user_id (user_id)
    );
    ";
    
    $db->exec($sql);
    writeLog("Tabela scheduled_deliveries criada com sucesso");
}
?>