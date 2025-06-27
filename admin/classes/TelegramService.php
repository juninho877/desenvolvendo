<?php
require_once 'TelegramSettings.php';

class TelegramService {
    private $telegramSettings;
    
    public function __construct() {
        $this->telegramSettings = new TelegramSettings();
    }
    
    /**
     * Enviar múltiplas imagens como álbum para o Telegram
     * @param int $userId ID do usuário
     * @param array $imagePaths Array com caminhos das imagens
     * @param string $caption Legenda opcional
     * @return array Resultado do envio
     */
    public function sendImageAlbum($userId, $imagePaths, $caption = '') {
        try {
            // Verificar se o usuário tem configurações do Telegram
            $settings = $this->telegramSettings->getSettings($userId);
            if (!$settings) {
                return ['success' => false, 'message' => 'Configurações do Telegram não encontradas. Configure primeiro em Telegram > Configurações.'];
            }
            
            $botToken = $settings['bot_token'];
            $chatId = $settings['chat_id'];
            
            // Validar se há imagens para enviar
            if (empty($imagePaths)) {
                return ['success' => false, 'message' => 'Nenhuma imagem fornecida para envio'];
            }
            
            // Preparar mídia para o álbum
            $media = [];
            foreach ($imagePaths as $index => $imagePath) {
                if (!file_exists($imagePath)) {
                    error_log("Arquivo não encontrado: " . $imagePath);
                    continue;
                }
                
                $media[] = [
                    'type' => 'photo',
                    'media' => 'attach://photo' . $index,
                    'caption' => ($index === 0 && !empty($caption)) ? $caption : ''
                ];
            }
            
            if (empty($media)) {
                return ['success' => false, 'message' => 'Nenhuma imagem válida encontrada'];
            }
            
            // Se há apenas uma imagem, enviar como foto simples
            if (count($media) === 1) {
                return $this->sendSinglePhoto($botToken, $chatId, $imagePaths[0], $caption);
            }
            
            // Enviar como álbum
            return $this->sendMediaGroup($botToken, $chatId, $imagePaths, $media, $caption);
            
        } catch (Exception $e) {
            error_log("Erro no TelegramService::sendImageAlbum: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()];
        }
    }
    
    /**
     * Enviar uma única foto
     */
    private function sendSinglePhoto($botToken, $chatId, $imagePath, $caption) {
        try {
            $url = "https://api.telegram.org/bot{$botToken}/sendPhoto";
            
            $postFields = [
                'chat_id' => $chatId,
                'photo' => new CURLFile($imagePath),
                'caption' => $caption
            ];
            
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $postFields,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_USERAGENT => 'FutBanner/1.0'
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($response === false) {
                return ['success' => false, 'message' => 'Erro na conexão com o Telegram'];
            }
            
            $data = json_decode($response, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                return ['success' => false, 'message' => 'Erro ao decodificar resposta do Telegram'];
            }
            
            if (!$data['ok']) {
                return ['success' => false, 'message' => 'Erro do Telegram: ' . ($data['description'] ?? 'Erro desconhecido')];
            }
            
            return ['success' => true, 'message' => 'Imagem enviada com sucesso para o Telegram'];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erro no envio: ' . $e->getMessage()];
        }
    }
    
    /**
     * Enviar grupo de mídia (álbum)
     */
    private function sendMediaGroup($botToken, $chatId, $imagePaths, $media, $caption) {
        try {
            $url = "https://api.telegram.org/bot{$botToken}/sendMediaGroup";
            
            $postFields = [
                'chat_id' => $chatId,
                'media' => json_encode($media)
            ];
            
            // Adicionar arquivos
            foreach ($imagePaths as $index => $imagePath) {
                if (file_exists($imagePath)) {
                    $postFields['photo' . $index] = new CURLFile($imagePath);
                }
            }
            
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $postFields,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 60, // Mais tempo para múltiplas imagens
                CURLOPT_USERAGENT => 'FutBanner/1.0'
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($response === false) {
                return ['success' => false, 'message' => 'Erro na conexão com o Telegram'];
            }
            
            $data = json_decode($response, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                return ['success' => false, 'message' => 'Erro ao decodificar resposta do Telegram'];
            }
            
            if (!$data['ok']) {
                return ['success' => false, 'message' => 'Erro do Telegram: ' . ($data['description'] ?? 'Erro desconhecido')];
            }
            
            return [
                'success' => true, 
                'message' => 'Álbum com ' . count($imagePaths) . ' imagens enviado com sucesso para o Telegram',
                'sent_count' => count($imagePaths)
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erro no envio do álbum: ' . $e->getMessage()];
        }
    }
    
    /**
     * Gerar banners e enviar para o Telegram
     * @param int $userId ID do usuário
     * @param string $bannerType Tipo de banner (football_1, football_2, football_3)
     * @param array $jogos Array com dados dos jogos
     * @return array Resultado da operação
     */
    public function generateAndSendBanners($userId, $bannerType, $jogos) {
        try {
            if (empty($jogos)) {
                return ['success' => false, 'message' => 'Nenhum jogo disponível para gerar banners'];
            }
            
            // Determinar script de geração baseado no tipo
            $generatorScript = '';
            switch ($bannerType) {
                case 'football_1':
                    $generatorScript = 'gerar_fut.php';
                    break;
                case 'football_2':
                    $generatorScript = 'gerar_fut_2.php';
                    break;
                case 'football_3':
                    $generatorScript = 'gerar_fut_3.php';
                    break;
                default:
                    return ['success' => false, 'message' => 'Tipo de banner inválido'];
            }
            
            // Dividir jogos em grupos
            $jogosPorBanner = 5;
            $gruposDeJogos = array_chunk(array_keys($jogos), $jogosPorBanner);
            
            $imagePaths = [];
            $tempFiles = [];
            
            // Gerar cada banner
            foreach ($gruposDeJogos as $index => $grupoJogos) {
                $tempFile = $this->generateBannerImage($generatorScript, $index);
                if ($tempFile && file_exists($tempFile)) {
                    $imagePaths[] = $tempFile;
                    $tempFiles[] = $tempFile;
                }
            }
            
            if (empty($imagePaths)) {
                return ['success' => false, 'message' => 'Erro ao gerar banners'];
            }
            
            // Preparar legenda
            $caption = "🏆 Banners de Futebol - " . date('d/m/Y') . "\n";
            $caption .= "📊 " . count($jogos) . " jogos de hoje\n";
            $caption .= "🎨 Gerado pelo FutBanner";
            
            // Enviar para o Telegram
            $result = $this->sendImageAlbum($userId, $imagePaths, $caption);
            
            // Limpar arquivos temporários
            foreach ($tempFiles as $tempFile) {
                if (file_exists($tempFile)) {
                    unlink($tempFile);
                }
            }
            
            return $result;
            
        } catch (Exception $e) {
            error_log("Erro em generateAndSendBanners: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erro ao gerar e enviar banners: ' . $e->getMessage()];
        }
    }
    
    /**
     * Gerar imagem de banner temporária
     */
    private function generateBannerImage($generatorScript, $grupoIndex) {
        try {
            // Gerar URL para o banner
            $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
            $scriptPath = dirname($_SERVER['REQUEST_URI']) . '/' . $generatorScript;
            $bannerUrl = $baseUrl . $scriptPath . '?grupo=' . $grupoIndex . '&_t=' . time();
            
            // Baixar a imagem
            $context = stream_context_create([
                'http' => [
                    'timeout' => 30,
                    'user_agent' => 'FutBanner/1.0'
                ]
            ]);
            
            $imageData = @file_get_contents($bannerUrl, false, $context);
            
            if ($imageData === false) {
                error_log("Erro ao baixar banner: " . $bannerUrl);
                return false;
            }
            
            // Salvar em arquivo temporário
            $tempFile = sys_get_temp_dir() . '/futbanner_telegram_' . uniqid() . '_' . $grupoIndex . '.png';
            
            if (file_put_contents($tempFile, $imageData) === false) {
                error_log("Erro ao salvar arquivo temporário: " . $tempFile);
                return false;
            }
            
            return $tempFile;
            
        } catch (Exception $e) {
            error_log("Erro em generateBannerImage: " . $e->getMessage());
            return false;
        }
    }
}
?>