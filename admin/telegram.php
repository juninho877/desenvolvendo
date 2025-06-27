<?php
session_start();
if (!isset($_SESSION["usuario"])) {
    header("Location: login.php");
    exit();
}

require_once 'classes/TelegramSettings.php';

$telegramSettings = new TelegramSettings();
$userId = $_SESSION['user_id'];
$message = '';
$messageType = '';

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'save_settings':
                $botToken = trim($_POST['bot_token']);
                $chatId = trim($_POST['chat_id']);
                $footballMessage = trim($_POST['football_message'] ?? '');
                $movieSeriesMessage = trim($_POST['movie_series_message'] ?? '');
                
                $result = $telegramSettings->saveSettings($userId, $botToken, $chatId, $footballMessage, $movieSeriesMessage);
                $message = $result['message'];
                $messageType = $result['success'] ? 'success' : 'error';
                break;
                
            case 'test_bot':
                $botToken = trim($_POST['bot_token']);
                $result = $telegramSettings->testBotConnection($botToken);
                
                header('Content-Type: application/json');
                echo json_encode($result);
                exit;
                
            case 'test_chat':
                $botToken = trim($_POST['bot_token']);
                $chatId = trim($_POST['chat_id']);
                $result = $telegramSettings->testChatConnection($botToken, $chatId);
                
                header('Content-Type: application/json');
                echo json_encode($result);
                exit;
                
            case 'send_test':
                $botToken = trim($_POST['bot_token']);
                $chatId = trim($_POST['chat_id']);
                $result = $telegramSettings->sendTestMessage($botToken, $chatId);
                
                header('Content-Type: application/json');
                echo json_encode($result);
                exit;
                
            case 'delete_settings':
                $result = $telegramSettings->deleteSettings($userId);
                $message = $result['message'];
                $messageType = $result['success'] ? 'success' : 'error';
                break;
        }
    }
}

// Buscar configurações atuais
$currentSettings = $telegramSettings->getSettings($userId);
$hasSettings = $currentSettings !== false;

$pageTitle = "Configurações do Telegram";
include "includes/header.php";
?>

<div class="page-header">
    <h1 class="page-title">
        <i class="fab fa-telegram text-primary-500 mr-3"></i>
        Configurações do Telegram
    </h1>
    <p class="page-subtitle">Configure seu bot do Telegram para envio automático de banners</p>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Formulário de Configuração -->
    <div class="lg:col-span-2">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <?php echo $hasSettings ? 'Atualizar' : 'Configurar'; ?> Bot do Telegram
                </h3>
                <p class="card-subtitle">
                    <?php echo $hasSettings ? 'Suas configurações atuais' : 'Configure seu bot para enviar banners automaticamente'; ?>
                </p>
            </div>
            <div class="card-body">
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $messageType; ?> mb-6">
                        <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" id="telegramForm">
                    <input type="hidden" name="action" value="save_settings">
                    
                    <div class="form-group">
                        <label for="bot_token" class="form-label required">
                            <i class="fas fa-robot mr-2"></i>
                            Token do Bot
                        </label>
                        <input type="text" id="bot_token" name="bot_token" class="form-input" 
                               value="<?php echo htmlspecialchars($currentSettings['bot_token'] ?? ''); ?>" 
                               placeholder="123456789:AAAA-BBBB-CCCC-DDDD" required>
                        <p class="text-xs text-muted mt-1">
                            Obtenha o token criando um bot com o @BotFather no Telegram
                        </p>
                        <button type="button" class="btn btn-secondary btn-sm mt-2" id="testBotBtn">
                            <i class="fas fa-vial"></i>
                            Testar Bot
                        </button>
                    </div>

                    <div class="form-group">
                        <label for="chat_id" class="form-label required">
                            <i class="fas fa-comments mr-2"></i>
                            Chat ID
                        </label>
                        <input type="text" id="chat_id" name="chat_id" class="form-input" 
                               value="<?php echo htmlspecialchars($currentSettings['chat_id'] ?? ''); ?>" 
                               placeholder="-1001234567890 ou 123456789" required>
                        <p class="text-xs text-muted mt-1">
                            ID do chat privado (positivo) ou grupo (negativo). Use @userinfobot para descobrir
                        </p>
                        <button type="button" class="btn btn-secondary btn-sm mt-2" id="testChatBtn">
                            <i class="fas fa-search"></i>
                            Verificar Chat
                        </button>
                    </div>
                    
                    <div class="form-group">
                        <label for="football_message" class="form-label">
                            <i class="fas fa-futbol mr-2"></i>
                            Mensagem para Banners de Futebol
                        </label>
                        <textarea id="football_message" name="football_message" class="form-input" rows="4" 
                                  placeholder="Mensagem personalizada para banners de futebol"><?php echo htmlspecialchars($currentSettings['football_message'] ?? ''); ?></textarea>
                        <p class="text-xs text-muted mt-1">
                            Variáveis disponíveis: $data (data atual), $hora (hora atual), $jogos (quantidade de jogos)
                        </p>
                        <p class="text-xs text-muted">
                            Deixe em branco para usar a mensagem padrão
                        </p>
                    </div>
                    
                    <div class="form-group">
                        <label for="movie_series_message" class="form-label">
                            <i class="fas fa-film mr-2"></i>
                            Mensagem para Banners de Filmes/Séries
                        </label>
                        <textarea id="movie_series_message" name="movie_series_message" class="form-input" rows="4" 
                                  placeholder="Mensagem personalizada para banners de filmes e séries"><?php echo htmlspecialchars($currentSettings['movie_series_message'] ?? ''); ?></textarea>
                        <p class="text-xs text-muted mt-1">
                            Variáveis disponíveis: $data (data atual), $hora (hora atual), $nomedofilme (nome do filme/série)
                        </p>
                        <p class="text-xs text-muted">
                            Deixe em branco para usar a mensagem padrão
                        </p>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i>
                            <?php echo $hasSettings ? 'Atualizar' : 'Salvar'; ?> Configurações
                        </button>
                        
                        <button type="button" class="btn btn-success" id="sendTestBtn" 
                                <?php echo !$hasSettings ? 'disabled' : ''; ?>>
                            <i class="fas fa-paper-plane"></i>
                            Enviar Teste
                        </button>
                        
                        <?php if ($hasSettings): ?>
                        <button type="button" class="btn btn-danger" id="deleteBtn">
                            <i class="fas fa-trash"></i>
                            Remover Configurações
                        </button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Painel de Informações -->
    <div class="space-y-6">
        <!-- Status -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">📊 Status</h3>
            </div>
            <div class="card-body">
                <div class="status-item">
                    <div class="status-icon <?php echo $hasSettings ? 'status-success' : 'status-warning'; ?>">
                        <i class="fas fa-<?php echo $hasSettings ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                    </div>
                    <div>
                        <p class="font-medium">
                            <?php echo $hasSettings ? 'Configurado' : 'Não Configurado'; ?>
                        </p>
                        <p class="text-sm text-muted">
                            <?php echo $hasSettings ? 'Bot pronto para envio' : 'Configure seu bot primeiro'; ?>
                        </p>
                    </div>
                </div>
                
                <?php if ($hasSettings): ?>
                <div class="mt-4 p-3 bg-gray-50 rounded-lg">
                    <p class="text-sm font-medium mb-2">Última atualização:</p>
                    <p class="text-xs text-muted">
                        <?php echo date('d/m/Y H:i', strtotime($currentSettings['updated_at'])); ?>
                    </p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Como Configurar -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">🤖 Como Configurar</h3>
            </div>
            <div class="card-body">
                <div class="space-y-4 text-sm">
                    <div class="step-item">
                        <div class="step-number">1</div>
                        <div>
                            <p class="font-medium">Criar Bot</p>
                            <p class="text-muted">Converse com @BotFather no Telegram e use /newbot</p>
                        </div>
                    </div>
                    
                    <div class="step-item">
                        <div class="step-number">2</div>
                        <div>
                            <p class="font-medium">Obter Token</p>
                            <p class="text-muted">Copie o token fornecido pelo BotFather</p>
                        </div>
                    </div>
                    
                    <div class="step-item">
                        <div class="step-number">3</div>
                        <div>
                            <p class="font-medium">Descobrir Chat ID</p>
                            <p class="text-muted">Use @userinfobot ou adicione o bot ao grupo</p>
                        </div>
                    </div>
                    
                    <div class="step-item">
                        <div class="step-number">4</div>
                        <div>
                            <p class="font-medium">Testar</p>
                            <p class="text-muted">Use os botões de teste antes de salvar</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dicas -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">💡 Dicas</h3>
            </div>
            <div class="card-body">
                <div class="space-y-3 text-sm">
                    <div class="tip-item">
                        <i class="fas fa-info-circle text-primary-500"></i>
                        <p>Para grupos, o Chat ID é negativo (ex: -1001234567890)</p>
                    </div>
                    <div class="tip-item">
                        <i class="fas fa-shield-alt text-success-500"></i>
                        <p>Seu token é criptografado e armazenado com segurança</p>
                    </div>
                    <div class="tip-item">
                        <i class="fas fa-users text-warning-500"></i>
                        <p>Para grupos, adicione o bot como administrador</p>
                    </div>
                    <div class="tip-item">
                        <i class="fas fa-clock text-info-500"></i>
                        <p>Teste sempre antes de usar em produção</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Variáveis Disponíveis -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">🔤 Variáveis para Mensagens</h3>
            </div>
            <div class="card-body">
                <div class="space-y-3 text-sm">
                    <div class="variable-item">
                        <code>$data</code>
                        <p>Data atual (ex: 25/06/2025)</p>
                    </div>
                    <div class="variable-item">
                        <code>$hora</code>
                        <p>Hora atual (ex: 14:30)</p>
                    </div>
                    <div class="variable-item">
                        <code>$jogos</code>
                        <p>Quantidade de jogos (apenas para banners de futebol)</p>
                    </div>
                    <div class="variable-item">
                        <code>$nomedofilme</code>
                        <p>Nome do filme ou série (apenas para banners de filmes/séries)</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .required::after {
        content: ' *';
        color: var(--danger-500);
    }

    .alert {
        padding: 1rem;
        border-radius: 12px;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-weight: 500;
    }
    
    .alert-success {
        background: var(--success-50);
        color: var(--success-600);
        border: 1px solid rgba(34, 197, 94, 0.2);
    }
    
    .alert-error {
        background: var(--danger-50);
        color: var(--danger-600);
        border: 1px solid rgba(239, 68, 68, 0.2);
    }

    .form-actions {
        display: flex;
        gap: 1rem;
        align-items: center;
        flex-wrap: wrap;
        padding-top: 1rem;
        border-top: 1px solid var(--border-color);
        margin-top: 2rem;
    }

    .btn-sm {
        padding: 0.5rem 1rem;
        font-size: 0.75rem;
    }

    .status-item {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .status-icon {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
    }

    .status-success {
        background: var(--success-50);
        color: var(--success-600);
    }

    .status-warning {
        background: var(--warning-50);
        color: var(--warning-600);
    }

    .step-item {
        display: flex;
        align-items: flex-start;
        gap: 1rem;
    }

    .step-number {
        width: 24px;
        height: 24px;
        background: var(--primary-500);
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 0.75rem;
        flex-shrink: 0;
    }

    .tip-item {
        display: flex;
        align-items: flex-start;
        gap: 0.75rem;
    }

    .tip-item i {
        margin-top: 0.125rem;
        flex-shrink: 0;
    }
    
    .variable-item {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.5rem;
        background: var(--bg-tertiary);
        border-radius: var(--border-radius-sm);
    }
    
    .variable-item code {
        font-family: monospace;
        background: var(--bg-primary);
        padding: 0.25rem 0.5rem;
        border-radius: 4px;
        font-weight: 600;
        color: var(--primary-600);
        border: 1px solid var(--border-color);
        min-width: 100px;
        display: inline-block;
        text-align: center;
    }

    .space-y-3 > * + * {
        margin-top: 0.75rem;
    }

    .space-y-4 > * + * {
        margin-top: 1rem;
    }

    .space-y-6 > * + * {
        margin-top: 1.5rem;
    }

    .mt-2 {
        margin-top: 0.5rem;
    }

    .mt-4 {
        margin-top: 1rem;
    }

    .mb-2 {
        margin-bottom: 0.5rem;
    }

    .mb-6 {
        margin-bottom: 1.5rem;
    }

    .mr-2 {
        margin-right: 0.5rem;
    }

    .p-3 {
        padding: 0.75rem;
    }

    .bg-gray-50 {
        background-color: var(--bg-tertiary);
    }

    .rounded-lg {
        border-radius: var(--border-radius);
    }

    /* Dark theme adjustments */
    [data-theme="dark"] .alert-success {
        background: rgba(34, 197, 94, 0.1);
        color: var(--success-400);
    }
    
    [data-theme="dark"] .alert-error {
        background: rgba(239, 68, 68, 0.1);
        color: var(--danger-400);
    }

    [data-theme="dark"] .bg-gray-50 {
        background-color: var(--bg-tertiary);
    }

    [data-theme="dark"] .status-success {
        background: rgba(34, 197, 94, 0.1);
        color: var(--success-400);
    }

    [data-theme="dark"] .status-warning {
        background: rgba(245, 158, 11, 0.1);
        color: var(--warning-400);
    }
    
    [data-theme="dark"] .variable-item code {
        background: var(--bg-secondary);
        color: var(--primary-400);
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const testBotBtn = document.getElementById('testBotBtn');
    const testChatBtn = document.getElementById('testChatBtn');
    const sendTestBtn = document.getElementById('sendTestBtn');
    const deleteBtn = document.getElementById('deleteBtn');
    const botTokenInput = document.getElementById('bot_token');
    const chatIdInput = document.getElementById('chat_id');

    // Testar Bot
    testBotBtn.addEventListener('click', function() {
        const botToken = botTokenInput.value.trim();
        
        if (!botToken) {
            showAlert('error', 'Digite o token do bot primeiro');
            return;
        }

        this.disabled = true;
        this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Testando...';

        fetch('telegram.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=test_bot&bot_token=${encodeURIComponent(botToken)}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const botInfo = data.bot_info;
                showAlert('success', `Bot conectado: @${botInfo.username} (${botInfo.first_name})`);
            } else {
                showAlert('error', data.message);
            }
        })
        .catch(error => {
            showAlert('error', 'Erro na conexão: ' + error.message);
        })
        .finally(() => {
            this.disabled = false;
            this.innerHTML = '<i class="fas fa-vial"></i> Testar Bot';
        });
    });

    // Testar Chat
    testChatBtn.addEventListener('click', function() {
        const botToken = botTokenInput.value.trim();
        const chatId = chatIdInput.value.trim();
        
        if (!botToken || !chatId) {
            showAlert('error', 'Digite o token do bot e Chat ID primeiro');
            return;
        }

        this.disabled = true;
        this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verificando...';

        fetch('telegram.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=test_chat&bot_token=${encodeURIComponent(botToken)}&chat_id=${encodeURIComponent(chatId)}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const chatInfo = data.chat_info;
                showAlert('success', `Chat encontrado: ${chatInfo.title} (${chatInfo.type})`);
            } else {
                showAlert('error', data.message);
            }
        })
        .catch(error => {
            showAlert('error', 'Erro na verificação: ' + error.message);
        })
        .finally(() => {
            this.disabled = false;
            this.innerHTML = '<i class="fas fa-search"></i> Verificar Chat';
        });
    });

    // Enviar Teste
    sendTestBtn.addEventListener('click', function() {
        const botToken = botTokenInput.value.trim();
        const chatId = chatIdInput.value.trim();
        
        if (!botToken || !chatId) {
            showAlert('error', 'Digite o token do bot e Chat ID primeiro');
            return;
        }

        this.disabled = true;
        this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';

        fetch('telegram.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=send_test&bot_token=${encodeURIComponent(botToken)}&chat_id=${encodeURIComponent(chatId)}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('success', 'Mensagem de teste enviada com sucesso!');
            } else {
                showAlert('error', data.message);
            }
        })
        .catch(error => {
            showAlert('error', 'Erro no envio: ' + error.message);
        })
        .finally(() => {
            this.disabled = false;
            this.innerHTML = '<i class="fas fa-paper-plane"></i> Enviar Teste';
        });
    });

    // Deletar Configurações
    if (deleteBtn) {
        deleteBtn.addEventListener('click', function() {
            Swal.fire({
                title: 'Remover Configurações?',
                text: 'Isso irá remover todas as suas configurações do Telegram. Você precisará configurar novamente.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sim, remover',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#ef4444',
                background: document.body.getAttribute('data-theme') === 'dark' ? '#1e293b' : '#ffffff',
                color: document.body.getAttribute('data-theme') === 'dark' ? '#f1f5f9' : '#1e293b'
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.innerHTML = '<input type="hidden" name="action" value="delete_settings">';
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        });
    }

    function showAlert(type, message) {
        Swal.fire({
            title: type === 'success' ? 'Sucesso!' : 'Erro!',
            text: message,
            icon: type,
            background: document.body.getAttribute('data-theme') === 'dark' ? '#1e293b' : '#ffffff',
            color: document.body.getAttribute('data-theme') === 'dark' ? '#f1f5f9' : '#1e293b',
            confirmButtonColor: type === 'success' ? '#22c55e' : '#ef4444'
        });
    }
});
</script>

<?php include "includes/footer.php"; ?>