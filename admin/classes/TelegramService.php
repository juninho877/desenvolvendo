<?php
require_once 'TelegramSettings.php';
require_once __DIR__ . '/../includes/banner_functions.php';

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
            
            // Verificar se o arquivo existe e é legível
            if (!file_exists($imagePath) || !is_readable($imagePath)) {
                error_log("Arquivo não existe ou não é legível: " . $imagePath);
                return ['success' => false, 'message' => 'Arquivo não existe ou não é legível: ' . $imagePath];
            }
            
            // Verificar tamanho do arquivo
            $fileSize = filesize($imagePath);
            if ($fileSize === false) {
                error_log("Não foi possível obter o tamanho do arquivo: " . $imagePath);
                return ['success' => false, 'message' => 'Não foi possível obter o tamanho do arquivo'];
            }
            
            if ($fileSize > 10 * 1024 * 1024) { // 10MB
                error_log("Arquivo muito grande (> 10MB): " . $imagePath . " - " . $fileSize . " bytes");
                return ['success' => false, 'message' => 'Arquivo muito grande (> 10MB)'];
            }
            
            // Criar CURLFile
            $curlFile = new CURLFile($imagePath);
            if (!$curlFile) {
                error_log("Falha ao criar CURLFile para: " . $imagePath);
                return ['success' => false, 'message' => 'Falha ao criar CURLFile'];
            }
            
            $postFields = [
                'chat_id' => $chatId,
                'photo' => $curlFile,
                'caption' => $caption,
                'parse_mode' => 'HTML'
            ];
            
            // Inicializar cURL
            $ch = curl_init();
            if (!$ch) {
                error_log("Falha ao inicializar cURL");
                return ['success' => false, 'message' => 'Falha ao inicializar cURL'];
            }
            
            // Configurar opções do cURL
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $postFields,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_USERAGENT => 'FutBanner/1.0',
                CURLOPT_VERBOSE => true,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_STDERR => fopen('php://temp', 'w+')
            ]);
            
            // Executar cURL
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            $errno = curl_errno($ch);
            
            // Obter informações de erro detalhadas
            $verbose = stream_get_contents(fopen('php://temp', 'r'));
            
            if ($response === false) {
                curl_close($ch);
                error_log("Erro cURL ao enviar foto: " . $error . " (código: " . $errno . ")\nVerbose: " . $verbose);
                return ['success' => false, 'message' => 'Erro na conexão com o Telegram: ' . $error . ' (código: ' . $errno . ')'];
            }
            
            curl_close($ch);
            
            // Decodificar resposta JSON
            $data = json_decode($response, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log("Erro ao decodificar resposta JSON: " . json_last_error_msg() . "\nResposta: " . $response);
                return ['success' => false, 'message' => 'Erro ao decodificar resposta do Telegram: ' . json_last_error_msg()];
            }
            
            if (!isset($data['ok']) || $data['ok'] !== true) {
                error_log("Erro da API do Telegram: " . ($data['description'] ?? 'Erro desconhecido') . "\nCódigo: " . $httpCode);
                return ['success' => false, 'message' => 'Erro do Telegram: ' . ($data['description'] ?? 'Erro desconhecido')];
            }
            
            return ['success' => true, 'message' => 'Imagem enviada com sucesso para o Telegram'];
            
        } catch (Exception $e) {
            error_log("Exceção ao enviar foto: " . $e->getMessage() . "\n" . $e->getTraceAsString());
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
                if (file_exists($imagePath) && is_readable($imagePath)) {
                    $postFields['photo' . $index] = new CURLFile($imagePath);
                } else {
                    error_log("Arquivo não existe ou não é legível: " . $imagePath);
                }
            }
            
            $ch = curl_init();
            if (!$ch) {
                error_log("Falha ao inicializar cURL para álbum");
                return ['success' => false, 'message' => 'Falha ao inicializar cURL para álbum'];
            }
            
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $postFields,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 60, // Mais tempo para múltiplas imagens
                CURLOPT_USERAGENT => 'FutBanner/1.0',
                CURLOPT_VERBOSE => true,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_STDERR => fopen('php://temp', 'w+')
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            $errno = curl_errno($ch);
            
            // Obter informações de erro detalhadas
            $verbose = stream_get_contents(fopen('php://temp', 'r'));
            
            if ($response === false) {
                curl_close($ch);
                error_log("Erro cURL ao enviar álbum: " . $error . " (código: " . $errno . ")\nVerbose: " . $verbose);
                return ['success' => false, 'message' => 'Erro na conexão com o Telegram: ' . $error . ' (código: ' . $errno . ')'];
            }
            
            curl_close($ch);
            
            $data = json_decode($response, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log("Erro ao decodificar resposta JSON do álbum: " . json_last_error_msg() . "\nResposta: " . $response);
                return ['success' => false, 'message' => 'Erro ao decodificar resposta do Telegram: ' . json_last_error_msg()];
            }
            
            if (!isset($data['ok']) || $data['ok'] !== true) {
                error_log("Erro da API do Telegram (álbum): " . ($data['description'] ?? 'Erro desconhecido') . "\nCódigo: " . $httpCode);
                return ['success' => false, 'message' => 'Erro do Telegram: ' . ($data['description'] ?? 'Erro desconhecido')];
            }
            
            return [
                'success' => true, 
                'message' => 'Álbum com ' . count($imagePaths) . ' imagens enviado com sucesso para o Telegram',
                'sent_count' => count($imagePaths)
            ];
            
        } catch (Exception $e) {
            error_log("Exceção ao enviar álbum: " . $e->getMessage() . "\n" . $e->getTraceAsString());
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
            
            // Determinar modelo de banner baseado no tipo
            $bannerModel = 1; // Padrão
            switch ($bannerType) {
                case 'football_1':
                    $bannerModel = 1;
                    break;
                case 'football_2':
                    $bannerModel = 2;
                    break;
                case 'football_3':
                    $bannerModel = 3;
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
                try {
                    // Usar a função para gerar o recurso de imagem diretamente
                    $imageResource = generateFootballBannerResource($userId, $bannerModel, $index, $jogos);
                    
                    if ($imageResource) {
                        // Salvar em arquivo temporário
                        $tempFile = sys_get_temp_dir() . '/futbanner_telegram_' . uniqid() . '_' . $index . '.png';
                        
                        if (imagepng($imageResource, $tempFile)) {
                            $imagePaths[] = $tempFile;
                            $tempFiles[] = $tempFile;
                        } else {
                            error_log("Falha ao salvar imagem temporária: " . $tempFile);
                        }
                        
                        // Liberar memória
                        imagedestroy($imageResource);
                    } else {
                        error_log("Falha ao gerar recurso de imagem para o grupo " . $index);
                    }
                } catch (Exception $e) {
                    error_log("Exceção ao gerar banner para grupo " . $index . ": " . $e->getMessage());
                }
            }
            
            if (empty($imagePaths)) {
                return ['success' => false, 'message' => 'Erro ao gerar banners. Nenhuma imagem foi criada.'];
            }
            
            // Obter configurações do usuário
            $settings = $this->telegramSettings->getSettings($userId);
            if (!$settings) {
                // Limpar arquivos temporários
                foreach ($tempFiles as $tempFile) {
                    if (file_exists($tempFile)) {
                        @unlink($tempFile);
                    }
                }
                return ['success' => false, 'message' => 'Configurações do Telegram não encontradas para o usuário'];
            }
            
            // Preparar legenda personalizada ou usar padrão
            $caption = "🏆 Banners de Futebol - " . date('d/m/Y') . "\n";
            
            if (!empty($settings['football_message'])) {
                // Substituir variáveis na mensagem personalizada
                $customMessage = $settings['football_message'];
                $data = date('d/m/Y');
                $hora = date('H:i');
                $jogosCount = count($jogos);
                
                $customMessage = str_replace('$data', $data, $customMessage);
                $customMessage = str_replace('$hora', $hora, $customMessage);
                $customMessage = str_replace('$jogos', $jogosCount, $customMessage);
                
                $caption = $customMessage;
            } else {
                // Mensagem padrão
                $caption .= "📊 " . count($jogos) . " jogos de hoje\n";
                $caption .= "🎨 Gerado pelo FutBanner";
            }
            
            // Enviar para o Telegram
            $result = $this->sendImageAlbum($userId, $imagePaths, $caption);
            
            // Limpar arquivos temporários
            foreach ($tempFiles as $tempFile) {
                if (file_exists($tempFile)) {
                    @unlink($tempFile);
                }
            }
            
            return $result;
            
        } catch (Exception $e) {
            error_log("Erro em generateAndSendBanners: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            return ['success' => false, 'message' => 'Erro ao gerar e enviar banners: ' . $e->getMessage()];
        }
    }
    
    /**
     * Enviar banner de filme/série para o Telegram
     * @param int $userId ID do usuário
     * @param string $bannerPath Caminho do arquivo do banner
     * @param string $contentName Nome do filme ou série
     * @param string $contentType Tipo do conteúdo (filme ou série)
     * @return array Resultado da operação
     */
    public function sendMovieSeriesBanner($userId, $bannerPath, $contentName, $contentType = 'filme') {
        try {
            if (!file_exists($bannerPath)) {
                return ['success' => false, 'message' => 'Arquivo do banner não encontrado: ' . $bannerPath];
            }
            
            // Obter configurações do usuário
            $settings = $this->telegramSettings->getSettings($userId);
            if (!$settings) {
                return ['success' => false, 'message' => 'Configurações do Telegram não encontradas. Configure primeiro em Telegram > Configurações.'];
            }
            
            // Preparar legenda personalizada ou usar padrão
            $caption = "🎬 Banner: " . $contentName . "\n";
            
            if (!empty($settings['movie_series_message'])) {
                // Substituir variáveis na mensagem personalizada
                $customMessage = $settings['movie_series_message'];
                $data = date('d/m/Y');
                $hora = date('H:i');
                
                $customMessage = str_replace('$data', $data, $customMessage);
                $customMessage = str_replace('$hora', $hora, $customMessage);
                $customMessage = str_replace('$nomedofilme', $contentName, $customMessage);
                
                $caption = $customMessage;
            } else {
                // Mensagem padrão
                $caption .= "📅 Gerado em: " . date('d/m/Y H:i') . "\n";
                $caption .= "🎨 FutBanner";
            }
            
            // Enviar para o Telegram
            $result = $this->sendSinglePhoto($settings['bot_token'], $settings['chat_id'], $bannerPath, $caption);
            
            return $result;
            
        } catch (Exception $e) {
            error_log("Erro em sendMovieSeriesBanner: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            return ['success' => false, 'message' => 'Erro ao enviar banner: ' . $e->getMessage()];
        }
    }
}
?>