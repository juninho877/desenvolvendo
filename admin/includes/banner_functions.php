<?php
// Configurações otimizadas
define('CLOUDINARY_CLOUD_NAME', 'dwrikepvg');
define('LOGO_OVERRIDES', [
    'St. Louis City' => 'https://a.espncdn.com/combiner/i?img=/i/teamlogos/soccer/500/21812.png',
    'Guarulhos' => 'https://upload.wikimedia.org/wikipedia/pt/d/d5/GuarulhosGRU.png',
    'Estados Unidos' => 'https://a.espncdn.com/combiner/i?img=/i/teamlogos/countries/500/usa.png',
    'Tupa' => 'https://static.flashscore.com/res/image/data/8SqNKfdM-27lsDqoa.png',
    'Guadeloupe' => 'https://static.flashscore.com/res/image/data/z7uwX5e5-Qw31eZbP.png',
    'Tanabi' => 'https://ssl.gstatic.com/onebox/media/sports/logos/_0PCb1YBKcxp8eXBCCtZpg_96x96.png',
    'Mundial de Clubes FIFA' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/a/ad/2025_FIFA_Club_World_Cup.svg/1200px-2025_FIFA_Club_World_Cup.svg.png',
]);

// Cache simples
$imageCache = [];

// Incluir a classe UserImage
require_once __DIR__ . '/../classes/UserImage.php';

function desenhar_retangulo_arredondado($image, $x, $y, $width, $height, $radius, $color) {
    $x1 = $x; $y1 = $y; $x2 = $x + $width; $y2 = $y + $height;
    if ($radius > $width / 2) $radius = $width / 2;
    if ($radius > $height / 2) $radius = $height / 2;

    imagefilledrectangle($image, $x1 + $radius, $y1, $x2 - $radius, $y2, $color);
    imagefilledrectangle($image, $x1, $y1 + $radius, $x2, $y2 - $radius, $color);
    imagefilledarc($image, $x1 + $radius, $y1 + $radius, $radius * 2, $radius * 2, 180, 270, $color, IMG_ARC_PIE);
    imagefilledarc($image, $x2 - $radius, $y1 + $radius, $radius * 2, $radius * 2, 270, 360, $color, IMG_ARC_PIE);
    imagefilledarc($image, $x1 + $radius, $y2 - $radius, $radius * 2, $radius * 2, 90, 180, $color, IMG_ARC_PIE);
    imagefilledarc($image, $x2 - $radius, $y2 - $radius, $radius * 2, $radius * 2, 0, 90, $color, IMG_ARC_PIE);
}

function carregarImagemDeUrl(string $url, int $maxSize) {
    global $imageCache;
    
    $cacheKey = md5($url . $maxSize);
    if (isset($imageCache[$cacheKey])) {
        return $imageCache[$cacheKey];
    }

    $urlParaCarregar = $url;
    $extensao = strtolower(pathinfo($url, PATHINFO_EXTENSION));

    if ($extensao === 'svg') {
        $cloudinaryCloudName = CLOUDINARY_CLOUD_NAME;
        if (empty($cloudinaryCloudName)) return $imageCache[$cacheKey] = false;
        $urlParaCarregar = "https://res.cloudinary.com/{$cloudinaryCloudName}/image/fetch/f_png/" . urlencode($url);
    }
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $urlParaCarregar,
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_CONNECTTIMEOUT => 1,
        CURLOPT_TIMEOUT => 3,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; FutBanner/1.0)',
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1
    ]);
    
    $imageContent = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($imageContent === false || $httpCode >= 400) {
        return $imageCache[$cacheKey] = false;
    }
    
    $img = @imagecreatefromstring($imageContent);
    if (!$img) {
        return $imageCache[$cacheKey] = false;
    }

    $w = imagesx($img); $h = imagesy($img);
    if ($w == 0 || $h == 0) {
        imagedestroy($img);
        return $imageCache[$cacheKey] = false;
    }
    
    $scale = min($maxSize / $w, $maxSize / $h, 1.0);
    $newW = (int)($w * $scale); $newH = (int)($h * $scale);
    $imgResized = imagecreatetruecolor($newW, $newH);
    imagealphablending($imgResized, false); 
    imagesavealpha($imgResized, true);
    imagecopyresampled($imgResized, $img, 0, 0, 0, 0, $newW, $newH, $w, $h);
    imagedestroy($img);
    
    return $imageCache[$cacheKey] = $imgResized;
}

function carregarLogoCanalComAlturaFixa(string $url, int $alturaFixa = 50) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_CONNECTTIMEOUT => 1,
        CURLOPT_TIMEOUT => 3,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; FutBanner/1.0)'
    ]);
    
    $imageContent = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($imageContent === false || $httpCode >= 400) return false;
    
    $img = @imagecreatefromstring($imageContent);
    if (!$img) return false;
    
    $origW = imagesx($img); $origH = imagesy($img);
    if ($origH == 0) { imagedestroy($img); return false; }
    
    $ratio = $origW / $origH;
    $newW = (int)($alturaFixa * $ratio);
    $newH = $alturaFixa;
    $imgResized = imagecreatetruecolor($newW, $newH);
    imagealphablending($imgResized, false); 
    imagesavealpha($imgResized, true);
    $transparent = imagecolorallocatealpha($imgResized, 0, 0, 0, 127);
    imagefill($imgResized, 0, 0, $transparent);
    imagecopyresampled($imgResized, $img, 0, 0, 0, 0, $newW, $newH, $origW, $origH);
    imagedestroy($img);
    return $imgResized;
}

function criarPlaceholderComNome(string $nomeTime, int $size = 68) {
    $img = imagecreatetruecolor($size, $size);
    imagealphablending($img, false); 
    imagesavealpha($img, true);
    $transparent = imagecolorallocatealpha($img, 0, 0, 0, 127);
    imagefill($img, 0, 0, $transparent);
    $textColor = imagecolorallocate($img, 80, 80, 80);
    $fontePath = __DIR__ . '/../fonts/RobotoCondensed-Bold.ttf';
    
    if (!file_exists($fontePath)) { 
        imagestring($img, 2, 2, $size/2 - 5, "No Logo", $textColor); 
        return $img; 
    }
    
    $nomeLimpo = trim(preg_replace('/\s*\([^)]*\)/', '', $nomeTime));
    $palavras = explode(' ', $nomeLimpo);
    $linhas = []; $linhaAtual = '';
    
    foreach ($palavras as $palavra) {
        $testeLinha = $linhaAtual . ($linhaAtual ? ' ' : '') . $palavra;
        $bbox = imagettfbbox(10.5, 0, $fontePath, $testeLinha);
        if (($bbox[2] - $bbox[0]) > ($size - 8) && $linhaAtual !== '') { 
            $linhas[] = $linhaAtual; 
            $linhaAtual = $palavra; 
        } else { 
            $linhaAtual = $testeLinha; 
        }
    }
    $linhas[] = $linhaAtual;
    
    $bbox = imagettfbbox(10.5, 0, $fontePath, "A");
    $alturaLinha = abs($bbox[7] - $bbox[1]);
    $alturaTotalTexto = (count($linhas) * $alturaLinha) + ((count($linhas) - 1) * 2);
    $y = ($size - $alturaTotalTexto) / 2 + $alturaLinha;
    
    foreach ($linhas as $linha) {
        $bboxLinha = imagettfbbox(10.5, 0, $fontePath, $linha);
        $x = ($size - ($bboxLinha[2] - $bboxLinha[0])) / 2;
        imagettftext($img, 10.5, 0, (int)$x, (int)$y, $textColor, $fontePath, $linha);
        $y += $alturaLinha + 2;
    }
    return $img;
}

function carregarEscudo(string $nomeTime, ?string $url, int $maxSize = 60) {
    if (!empty($url)) {
        $imagem = carregarImagemDeUrl($url, $maxSize);
        if ($imagem) return $imagem;
    }
    return criarPlaceholderComNome($nomeTime, $maxSize);
}

function getChaveRemota() {
    $url = base64_decode('aHR0cHM6Ly9hcGlmdXQucHJvamVjdHguY2xpY2svQXV0b0FwaS9BRVMvY29uZmlna2V5LnBocA==');
    $auth = base64_decode('dmFxdW9UQlpFb0U4QmhHMg==');
    $postData = json_encode(['auth' => $auth]);
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Content-Length: ' . strlen($postData)]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData); 
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
    
    $response = curl_exec($ch);
    curl_close($ch);
    return $response ? json_decode($response, true)['chave'] ?? null : null;
}

function descriptografarURL($urlCodificada, $chave) {
    $parts = explode('::', base64_decode($urlCodificada), 2);
    if (count($parts) < 2) return null;
    list($url_criptografada, $iv) = $parts;
    return openssl_decrypt($url_criptografada, 'aes-256-cbc', $chave, 0, $iv);
}

function desenharTexto($im, $texto, $x, $y, $cor, $tamanho=12, $angulo=0, $fonteCustom = null) {
    $fontPath = __DIR__ . '/../fonts/CalSans-Regular.ttf';
    $fonteUsada = $fonteCustom ?? $fontPath;
    if (file_exists($fonteUsada)) {
        $bbox = imagettfbbox($tamanho, $angulo, $fonteUsada, $texto);
        $alturaTexto = abs($bbox[7] - $bbox[1]);
        imagettftext($im, $tamanho, $angulo, $x, $y + $alturaTexto, $cor, $fonteUsada, $texto);
    } else {
        imagestring($im, 5, $x, $y, $texto, $cor);
    }
}

/**
 * Nova função para buscar configuração de imagem do usuário
 * Substitui a antiga função getImageFromJson
 * @param int $userId ID do usuário
 * @param string $imageKey Chave da imagem (ex: logo_banner_1, background_banner_2, etc.)
 * @return string|false Conteúdo da imagem ou false se não encontrado
 */
function getUserImageConfig($userId, $imageKey) {
    static $userImageInstance = null;
    
    if ($userImageInstance === null) {
        $userImageInstance = new UserImage();
    }
    
    return $userImageInstance->getImageContent($userId, $imageKey);
}

/**
 * Função para carregar imagem do usuário como resource GD
 * @param int $userId ID do usuário
 * @param string $imageKey Chave da imagem
 * @return resource|false Resource da imagem ou false se não encontrado
 */
function loadUserImage($userId, $imageKey) {
    $imageContent = getUserImageConfig($userId, $imageKey);
    
    if ($imageContent === false) {
        return false;
    }
    
    return @imagecreatefromstring($imageContent);
}

function centralizarTextoX($larguraImagem, $tamanhoFonte, $fonte, $texto) { 
    if (!file_exists($fonte)) return $larguraImagem / 2;
    $caixa = imagettfbbox($tamanhoFonte, 0, $fonte, $texto); 
    return ($larguraImagem - ($caixa[2] - $caixa[0])) / 2; 
}

function centralizarTexto($larguraImagem, $tamanhoFonte, $fonte, $texto) {
    if (!file_exists($fonte)) return 0;
    $caixa = imagettfbbox($tamanhoFonte, 0, $fonte, $texto);
    $larguraTexto = $caixa[2] - $caixa[0];
    return ($larguraImagem - $larguraTexto) / 2;
}

function obterJogosDeHoje() {
    $chave_secreta = getChaveRemota();
    $parametro_criptografado = 'SVI0Sjh1MTJuRkw1bmFyeFdPb3cwOXA2TFo3RWlSQUxLbkczaGE4MXBiMWhENEpOWkhkSFZoeURaWFVDM1lTZzo6RNBu5BBhzmFRkTPPSikeJg==';
    $json_url = $chave_secreta ? descriptografarURL($parametro_criptografado, $chave_secreta) : null;

    $jogos = [];
    if ($json_url) {
        $ch = curl_init($json_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
        $json_content = curl_exec($ch);
        curl_close($ch);
        
        if ($json_content) {
            $todos_jogos = json_decode($json_content, true);
            if (is_array($todos_jogos)) {
                foreach ($todos_jogos as $jogo) {
                    if (isset($jogo['data_jogo']) && $jogo['data_jogo'] === 'hoje') {
                        $jogos[] = $jogo;
                    }
                }
            }
        }
    }
    
    return $jogos;
}

/**
 * Gera um recurso de imagem GD para um banner de futebol
 * @param int $userId ID do usuário
 * @param int $bannerModel Modelo do banner (1, 2 ou 3)
 * @param int $grupoIndex Índice do grupo de jogos
 * @param array $allJogos Todos os jogos disponíveis
 * @return resource|false Recurso GD da imagem ou false em caso de erro
 */
function generateFootballBannerResource($userId, $bannerModel, $grupoIndex, $allJogos) {
    // Verificar parâmetros
    if (empty($allJogos)) {
        return false;
    }
    
    // Dividir jogos em grupos
    $jogosPorBanner = 5;
    $gruposDeJogos = array_chunk(array_keys($allJogos), $jogosPorBanner);
    
    // Verificar se o grupo solicitado existe
    if (!isset($gruposDeJogos[$grupoIndex])) {
        return false;
    }
    
    $grupoJogos = $gruposDeJogos[$grupoIndex];
    
    // Configurações comuns
    $padding = 15;
    $fontLiga = __DIR__ . '/../fonts/MANDATOR.ttf';
    
    // Configurações específicas por modelo
    switch ($bannerModel) {
        case 1:
            $width = 1440;
            $heightPorJogo = 240;
            $espacoExtra = 649;
            $height = max(count($grupoJogos) * $heightPorJogo + $padding * 2 + $espacoExtra, 2030);
            return _gerarBannerModel1($userId, $allJogos, $grupoJogos, $width, $height, $padding, $heightPorJogo, $fontLiga);
            
        case 2:
            $width = 720;
            $heightPorJogo = 140;
            $espacoExtra = 200;
            $height = max(count($grupoJogos) * $heightPorJogo + $padding * 2 + $espacoExtra, 1015);
            return _gerarBannerModel2($userId, $allJogos, $grupoJogos, $width, $height, $padding, $heightPorJogo, $fontLiga);
            
        case 3:
            $width = 1440;
            $heightPorJogo = 240;
            $espacoExtra = 649;
            $height = max(count($grupoJogos) * $heightPorJogo + $padding * 2 + $espacoExtra, 2030);
            return _gerarBannerModel3($userId, $allJogos, $grupoJogos, $width, $height, $padding, $heightPorJogo, $fontLiga);
            
        default:
            return false;
    }
}

/**
 * Gera banner modelo 1 (gerar_fut.php)
 */
function _gerarBannerModel1($userId, $jogos, $grupoJogos, $width, $height, $padding, $heightPorJogo, $fontLiga) {
    // Criar imagem
    $im = imagecreatetruecolor($width, $height);
    $preto = imagecolorallocate($im, 0, 0, 0);
    $branco = imagecolorallocate($im, 255, 255, 255);
    
    // Carregar fundo do usuário
    $fundo = loadUserImage($userId, 'background_banner_1');
    if ($fundo) {
        imagecopyresampled($im, $fundo, 0, 0, 0, 0, $width, $height, imagesx($fundo), imagesy($fundo));
        imagedestroy($fundo);
    } else {
        imagefill($im, 0, 0, $branco);
    }
    
    // Cache para imagens estáticas
    static $fundoJogo = null;
    static $logoLiga = null;
    
    if ($fundoJogo === null) {
        $fundoJogo = loadUserImage($userId, 'card_banner_1');
        if (!$fundoJogo) {
            // Fallback para imagem padrão
            $fundoJogoPath = __DIR__ . '/../fzstore/card/card_banner_1.png';
            $fundoJogo = file_exists($fundoJogoPath) ? imagecreatefrompng($fundoJogoPath) : false;
        }
    }
    
    $yAtual = $padding + 480;

    foreach ($grupoJogos as $idx) {
        if (!isset($jogos[$idx])) continue;

        $jogo = $jogos[$idx];
        if ($fundoJogo) {
            imagecopyresampled($im, $fundoJogo, 15, $yAtual, 0, 0, $width - ($padding * 2), $heightPorJogo - 8, imagesx($fundoJogo), imagesy($fundoJogo));
        }

        $time1 = $jogo['time1'] ?? 'Time 1';
        $time2 = $jogo['time2'] ?? 'Time 2';
        $liga = $jogo['competicao'] ?? 'Liga';
        $hora = $jogo['horario'] ?? '';
        
        $escudo1_url = LOGO_OVERRIDES[$time1] ?? ($jogo['img_time1_url'] ?? '');
        $escudo2_url = LOGO_OVERRIDES[$time2] ?? ($jogo['img_time2_url'] ?? '');
        $liga_img_url = LOGO_OVERRIDES[$liga] ?? ($jogo['img_competicao_url'] ?? '');
        
        $imgliga = carregarEscudo($liga, $liga_img_url, 140);
        $imgEscudo1 = carregarEscudo($time1, $escudo1_url, 140);
        $imgEscudo2 = carregarEscudo($time2, $escudo2_url, 140);
        
        $yTop = $yAtual + 20;
        if($imgliga) imagecopy($im, $imgliga, 165, $yTop + 22, 0, 0, imagesx($imgliga), imagesy($imgliga));
        if($imgEscudo1) imagecopy($im, $imgEscudo1, 365, $yTop + 35, 0, 0, imagesx($imgEscudo1), imagesy($imgEscudo1));
        if($imgEscudo2) imagecopy($im, $imgEscudo2, 650, $yTop + 35, 0, 0, imagesx($imgEscudo2), imagesy($imgEscudo2));
        
        // Limpar memória apenas se não estiver no cache
        if($imgliga && !isset($GLOBALS['imageCache'][md5($liga_img_url . 140)])) imagedestroy($imgliga);
        if($imgEscudo1 && !isset($GLOBALS['imageCache'][md5($escudo1_url . 140)])) imagedestroy($imgEscudo1);
        if($imgEscudo2 && !isset($GLOBALS['imageCache'][md5($escudo2_url . 140)])) imagedestroy($imgEscudo2);
        
        $fonteNomes = __DIR__ . '/../fonts/CalSans-Regular.ttf';
        $tamanhoNomes = 30; $corNomes = $preto;
        $textoLinha1 = "$time1 X"; $textoLinha2 = $time2;
        $eixoCentralColuna = 1040;
        
        $bbox1 = imagettfbbox($tamanhoNomes, 0, $fonteNomes, $textoLinha1);
        $xPos1 = $eixoCentralColuna - (($bbox1[2] - $bbox1[0]) / 2);
        $bbox2 = imagettfbbox($tamanhoNomes, 0, $fonteNomes, $textoLinha2);
        $xPos2 = $eixoCentralColuna - (($bbox2[2] - $bbox2[0]) / 2);
        
        desenharTexto($im, $textoLinha1, $xPos1, $yTop + 5, $corNomes, $tamanhoNomes);
        desenharTexto($im, $textoLinha2, $xPos2, $yTop + 45, $corNomes, $tamanhoNomes);
        desenharTexto($im, $hora, 850, $yTop + 115, $branco, 60);
        
        // Canais (limitado a 2 para performance)
        $canaisDoJogo = array_slice($jogo['canais'] ?? [], 0, 2);
        if (!empty($canaisDoJogo)) {
            $logosParaDesenhar = [];
            $larguraTotalBloco = 0; $espacoEntreLogos = 5;
            
            foreach ($canaisDoJogo as $canal) {
                if (!empty($canal['img_url'])) {
                    $logoCanal = carregarLogoCanalComAlturaFixa($canal['img_url'], 85);
                    if ($logoCanal) {
                        $logosParaDesenhar[] = $logoCanal;
                        $larguraTotalBloco += imagesx($logoCanal);
                    }
                }
            }
            
            if (!empty($logosParaDesenhar)) {
                $larguraTotalBloco += (count($logosParaDesenhar) - 1) * $espacoEntreLogos;
                $xAtual = (($width - $larguraTotalBloco) / 2) + 430;
                foreach ($logosParaDesenhar as $logo) {
                    imagecopy($im, $logo, (int)$xAtual, (int)($yTop + 105), 0, 0, imagesx($logo), imagesy($logo));
                    $xAtual += imagesx($logo) + $espacoEntreLogos;
                    imagedestroy($logo);
                }
            }
        }
        $yAtual += $heightPorJogo;
    }

    // Elementos estáticos (cache)
    $fonteTitulo = __DIR__ . '/../fonts/BebasNeue-Regular.ttf';
    $fonteData = __DIR__ . '/../fonts/RobotoCondensed-VariableFont_wght.ttf';
    $corBranco = imagecolorallocate($im, 255, 255, 255);
    $titulo1 = "DESTAQUES DE HOJE";
    
    setlocale(LC_TIME, 'pt_BR.utf8', 'pt_BR.UTF-8', 'pt_BR', 'portuguese');
    $dataTexto = mb_strtoupper(strftime('%A - %d de %B'));
    imagettftext($im, 82, 0, centralizarTextoX($width, 82, $fonteTitulo, $titulo1), 120, $corBranco, $fonteTitulo, $titulo1);
    
    $corBranco2 = imagecolorallocate($im, 236, 240, 243);
    $corTexto = imagecolorallocate($im, 0, 0, 0);
    $retanguloLargura = 1135;
    $retanguloAltura = 130;
    $cantoRaio = 15; 
    $retanguloX = ($width - $retanguloLargura) / 2;
    $retanguloY = 348; 
    
    desenhar_retangulo_arredondado($im, $retanguloX, $retanguloY, $retanguloLargura, $retanguloAltura, $cantoRaio, $corBranco2);
    
    $tamanhoFonte = 78;
    $textoX = centralizarTextoX($width, $tamanhoFonte, $fonteData, $dataTexto) - 33;
    $textoY_preciso = $retanguloY + 45;
    
    desenharTexto($im, $dataTexto, $textoX, $textoY_preciso, $corTexto, $tamanhoFonte);

    // Logo das ligas (cache)
    if ($logoLiga === null) {
        $ligas_url = 'https://i.ibb.co/Cp8ck2H3/Rodape-liga-1440.png';
        $logoLiga = @imagecreatefrompng($ligas_url);
    }
    
    if ($logoLiga) {
        imagecopy($im, $logoLiga, 0, 1740, 0, 0, imagesx($logoLiga), imagesy($logoLiga));
    }

    // Logo do usuário
    $logoUsuario = loadUserImage($userId, 'logo_banner_1');
    if ($logoUsuario) {
        $w = imagesx($logoUsuario); $h = imagesy($logoUsuario);
        if ($w > 0 && $h > 0) {
            $scale = min(350 / $w, 350 / $h, 1.0);
            $newW = (int)($w * $scale); $newH = (int)($h * $scale);
            $logoRedimensionada = imagecreatetruecolor($newW, $newH);
            imagealphablending($logoRedimensionada, false); 
            imagesavealpha($logoRedimensionada, true);
            imagecopyresampled($logoRedimensionada, $logoUsuario, 0, 0, 0, 0, $newW, $newH, $w, $h);
            imagecopy($im, $logoRedimensionada, 10, 5, 0, 0, $newW, $newH);
            imagedestroy($logoRedimensionada);
        }
        imagedestroy($logoUsuario);
    }
    
    return $im;
}

/**
 * Gera banner modelo 2 (gerar_fut_2.php)
 */
function _gerarBannerModel2($userId, $jogos, $grupoJogos, $width, $height, $padding, $heightPorJogo, $fontLiga) {
    // Criar imagem
    $im = imagecreatetruecolor($width, $height);
    $preto = imagecolorallocate($im, 0, 0, 0);
    $branco = imagecolorallocate($im, 255, 255, 255);
    
    // Carregar fundo do usuário
    $fundo = loadUserImage($userId, 'background_banner_2');
    if ($fundo) {
        imagecopyresampled($im, $fundo, 0, 0, 0, 0, $width, $height, imagesx($fundo), imagesy($fundo));
        imagedestroy($fundo);
    } else {
        imagefill($im, 0, 0, $branco);
    }
    
    // Cache para imagens estáticas
    static $fundoJogo = null;
    static $logoLiga = null;
    
    if ($fundoJogo === null) {
        $fundoJogo = loadUserImage($userId, 'card_banner_2');
        if (!$fundoJogo) {
            // Fallback para imagem padrão
            $fundoJogoPath = __DIR__ . '/../fzstore/card/card_banner_2.png';
            $fundoJogo = file_exists($fundoJogoPath) ? imagecreatefrompng($fundoJogoPath) : false;
        }
    }
    
    $yAtual = $padding + 150;
    $offsetEsquerda = 50;
    $posX = 15;
    
    foreach ($grupoJogos as $idx) {
        if (!isset($jogos[$idx])) continue;
        
        if ($fundoJogo) {
            $alturaCard = $heightPorJogo - 8;
            $larguraCard = $width - $padding * 2;
            $cardResized = imagecreatetruecolor($larguraCard, $alturaCard);
            imagealphablending($cardResized, false); imagesavealpha($cardResized, true);
            imagecopyresampled($cardResized, $fundoJogo, 0, 0, 0, 0, $larguraCard, $alturaCard, imagesx($fundoJogo), imagesy($fundoJogo));
            imagecopy($im, $cardResized, $posX, $yAtual, 0, 0, $larguraCard, $alturaCard);
            imagedestroy($cardResized);
        }
        
        $jogo = $jogos[$idx];
        $time1 = $jogo['time1'] ?? 'Time 1';
        $time2 = $jogo['time2'] ?? 'Time 2';
        $liga = $jogo['competicao'] ?? 'Liga';
        $hora = $jogo['horario'] ?? '';
        $canais = implode(', ', array_slice(array_column($jogo['canais'] ?? [], 'nome'), 0, 3));
        
        $escudo1_url = LOGO_OVERRIDES[$time1] ?? $jogo['img_time1_url'] ?? '';
        $escudo2_url = LOGO_OVERRIDES[$time2] ?? $jogo['img_time2_url'] ?? '';
        
        $tamEscudo = 78;
        $imgEscudo1 = carregarEscudo($time1, $escudo1_url, $tamEscudo);
        $imgEscudo2 = carregarEscudo($time2, $escudo2_url, $tamEscudo);
        
        $xBase = $offsetEsquerda;
        $yTop = $yAtual + 20;
        $fontSizeLiga = 12;
        
        $bboxLiga = imagettfbbox($fontSizeLiga, 0, $fontLiga, $liga);
        $textWidthLiga = $bboxLiga[2] - $bboxLiga[0];
        $centerX_Liga = ($width / 2) - ($textWidthLiga / 2);
        desenharTexto($im, $liga, $centerX_Liga, $yTop + 21, $branco, $fontSizeLiga, 0, $fontLiga);
        
        $yEscudos = $yTop - 35;
        imagecopy($im, $imgEscudo1, $xBase + 70, $yEscudos, 0, 0, imagesx($imgEscudo1), imagesy($imgEscudo1));
        imagecopy($im, $imgEscudo2, $xBase + 470, $yEscudos, 0, 0, imagesx($imgEscudo2), imagesy($imgEscudo2));
        
        desenharTexto($im, "$time1", $xBase + 70, $yTop + 50, $branco, 14);
        desenharTexto($im, "$time2", $xBase + 450, $yTop + 50, $branco, 14);
        desenharTexto($im, $hora, 345, $yTop + 0, $branco, 12);
        
        $fontSize = 12; $fontFile = $fontLiga;
        $bbox = imagettfbbox($fontSize, 0, $fontFile, $canais);
        $textWidth = $bbox[2] - $bbox[0];
        $centerX = ($width / 2) - ($textWidth / 2);
        desenharTexto($im, $canais, $centerX, $yTop + 90, $branco, $fontSize, 0);
        
        // Limpar memória apenas se não estiver no cache
        if (!isset($GLOBALS['imageCache'][md5($escudo1_url . $tamEscudo)])) imagedestroy($imgEscudo1);
        if (!isset($GLOBALS['imageCache'][md5($escudo2_url . $tamEscudo)])) imagedestroy($imgEscudo2);
        
        $yAtual += $heightPorJogo;
    }
    
    // Logo das ligas
    if ($logoLiga === null) {
        $ligas_url = 'https://i.ibb.co/ycxpN2rc/Rodape-liga-720.png';
        $logoLiga = @imagecreatefrompng($ligas_url);
    }
    
    if ($logoLiga) {
        imagecopy($im, $logoLiga, 40, 870, 0, 0, imagesx($logoLiga), imagesy($logoLiga));
    }
    
    $fonteTitulo = __DIR__ . '/../fonts/BebasNeue-Regular.ttf';
    $fonteData = __DIR__ . '/../fonts/RobotoCondensed-VariableFont_wght.ttf';
    $corBranco = imagecolorallocate($im, 255, 255, 255);
    $titulo1 = "DESTAQUES DE HOJE";
    
    setlocale(LC_TIME, 'pt_BR.utf8', 'pt_BR.UTF-8', 'pt_BR', 'portuguese');
    $dataTexto = mb_strtoupper(strftime('%A - %d de %B'), 'UTF-8');
    
    $xTitulo1 = centralizarTextoX($width, 36, $fonteTitulo, $titulo1);
    $xData = centralizarTextoX($width, 17, $fonteData, $dataTexto);
    
    imagettftext($im, 36, 0, $xTitulo1, 65, $corBranco, $fonteTitulo, $titulo1);
    imagettftext($im, 17, 0, $xData, 90, $corBranco, $fonteData, $dataTexto);
    
    // Logo do usuário
    $logoUsuario = loadUserImage($userId, 'logo_banner_2');
    if ($logoUsuario) {
        $logoLarguraDesejada = 150;
        $logoPosX = 6; $logoPosY = 10;
        $logoWidthOriginal = imagesx($logoUsuario);
        $logoHeightOriginal = imagesy($logoUsuario);
        $logoHeight = (int)($logoHeightOriginal * ($logoLarguraDesejada / $logoWidthOriginal));
        $logoRedimensionada = imagecreatetruecolor($logoLarguraDesejada, $logoHeight);
        imagealphablending($logoRedimensionada, false); imagesavealpha($logoRedimensionada, true);
        imagecopyresampled($logoRedimensionada, $logoUsuario, 0, 0, 0, 0, $logoLarguraDesejada, $logoHeight, $logoWidthOriginal, $logoHeightOriginal);
        imagecopy($im, $logoRedimensionada, $logoPosX, $logoPosY, 0, 0, $logoLarguraDesejada, $logoHeight);
        imagedestroy($logoUsuario); imagedestroy($logoRedimensionada);
    }
    
    return $im;
}

/**
 * Gera banner modelo 3 (gerar_fut_3.php)
 */
function _gerarBannerModel3($userId, $jogos, $grupoJogos, $width, $height, $padding, $heightPorJogo, $fontLiga) {
    // Criar imagem
    $im = imagecreatetruecolor($width, $height);
    $preto = imagecolorallocate($im, 0, 0, 0);
    $branco = imagecolorallocate($im, 255, 255, 255);
    
    // Carregar fundo do usuário
    $fundo = loadUserImage($userId, 'background_banner_3');
    if ($fundo) {
        imagecopyresampled($im, $fundo, 0, 0, 0, 0, $width, $height, imagesx($fundo), imagesy($fundo));
        imagedestroy($fundo);
    } else {
        imagefill($im, 0, 0, $branco);
    }
    
    // Cache para imagens estáticas
    static $fundoJogo = null;
    static $logoLiga = null;
    
    if ($fundoJogo === null) {
        $fundoJogo = loadUserImage($userId, 'card_banner_3');
        if (!$fundoJogo) {
            // Fallback para imagem padrão
            $fundoJogoPath = __DIR__ . '/../fzstore/card/card_banner_3.png';
            $fundoJogo = file_exists($fundoJogoPath) ? imagecreatefrompng($fundoJogoPath) : false;
        }
    }
    
    $yAtual = $padding + 480;

    foreach ($grupoJogos as $idx) {
        if (!isset($jogos[$idx])) continue;

        $jogo = $jogos[$idx];
        if ($fundoJogo) {
            imagecopyresampled($im, $fundoJogo, 15, $yAtual, 0, 0, $width - ($padding * 2), $heightPorJogo - 8, imagesx($fundoJogo), imagesy($fundoJogo));
        }

        $time1 = $jogo['time1'] ?? 'Time 1';
        $time2 = $jogo['time2'] ?? 'Time 2';
        $liga = $jogo['competicao'] ?? 'Liga';
        $hora = $jogo['horario'] ?? '';
        
        $escudo1_url = LOGO_OVERRIDES[$time1] ?? ($jogo['img_time1_url'] ?? '');
        $escudo2_url = LOGO_OVERRIDES[$time2] ?? ($jogo['img_time2_url'] ?? '');
        
        $imgEscudo1 = carregarEscudo($time1, $escudo1_url, 180);
        $imgEscudo2 = carregarEscudo($time2, $escudo2_url, 180);
        
        $yTop = $yAtual + 20;
        if($imgEscudo1) imagecopy($im, $imgEscudo1, 165, $yTop + 15, 0, 0, imagesx($imgEscudo1), imagesy($imgEscudo1));
        if($imgEscudo2) imagecopy($im, $imgEscudo2, 1130, $yTop + 15, 0, 0, imagesx($imgEscudo2), imagesy($imgEscudo2));
        
        // Limpar memória apenas se não estiver no cache
        if($imgEscudo1 && !isset($GLOBALS['imageCache'][md5($escudo1_url . 180)])) imagedestroy($imgEscudo1);
        if($imgEscudo2 && !isset($GLOBALS['imageCache'][md5($escudo2_url . 180)])) imagedestroy($imgEscudo2);
        
        $fonteNomes = __DIR__ . '/../fonts/CalSans-Regular.ttf';
        $tamanhoNomes = 25; $corNomes = $preto;
        $tamanhoHora = 50;
        $textoLinha1 = mb_strtoupper($time1);
        $textoLinha2 = mb_strtoupper($time2);

        $eixoCentralColuna = 500;
        $eixoCentralColuna2 = 940;
        $eixoCentralColuna3 = 720;
        
        $bbox1 = imagettfbbox($tamanhoNomes, 0, $fonteNomes, $textoLinha1);
        $xPos1 = $eixoCentralColuna - (($bbox1[2] - $bbox1[0]) / 2);
        $bbox2 = imagettfbbox($tamanhoNomes, 0, $fonteNomes, $textoLinha2);
        $xPos2 = $eixoCentralColuna2 - (($bbox2[2] - $bbox2[0]) / 2);
        $bbox3 = imagettfbbox($tamanhoHora, 0, $fonteNomes, $hora);
        $xPos3 = $eixoCentralColuna3 - (($bbox3[2] - $bbox3[0]) / 2);
        
        desenharTexto($im, $textoLinha1, $xPos1, $yTop + 85, $corNomes, $tamanhoNomes);
        desenharTexto($im, $textoLinha2, $xPos2, $yTop + 85, $corNomes, $tamanhoNomes);
        desenharTexto($im, $hora, $xPos3, $yTop - 10, $branco, $tamanhoHora);
        
        $canaisDoJogo = $jogo['canais'];
        if (!empty($canaisDoJogo)) {
            $logosParaDesenhar = [];
            $larguraTotalBloco = 0; $espacoEntreLogos = 5;
            
            foreach ($canaisDoJogo as $canal) {
                if (!empty($canal['img_url'])) {
                    $logoCanal = carregarLogoCanalComAlturaFixa($canal['img_url'], 55);
                    if ($logoCanal) {
                        $logosParaDesenhar[] = $logoCanal;
                        $larguraTotalBloco += imagesx($logoCanal);
                    }
                }
            }
            
            if (!empty($logosParaDesenhar)) {
                $larguraTotalBloco += (count($logosParaDesenhar) - 1) * $espacoEntreLogos;
                $xAtual = (($width - $larguraTotalBloco) / 2);
                foreach ($logosParaDesenhar as $logo) {
                    imagecopy($im, $logo, (int)$xAtual, (int)($yTop + 145), 0, 0, imagesx($logo), imagesy($logo));
                    $xAtual += imagesx($logo) + $espacoEntreLogos;
                    imagedestroy($logo);
                }
            }
        }
        $yAtual += $heightPorJogo;
    }

    $fonteTitulo = __DIR__ . '/../fonts/BebasNeue-Regular.ttf';
    $fonteData = __DIR__ . '/../fonts/RobotoCondensed-VariableFont_wght.ttf';
    $corBranco = imagecolorallocate($im, 255, 255, 255);
    $titulo1 = "DESTAQUES DE HOJE";
    
    setlocale(LC_TIME, 'pt_BR.utf8', 'pt_BR.UTF-8', 'pt_BR', 'portuguese');
    $dataTexto = mb_strtoupper(strftime('%A - %d de %B'));
    imagettftext($im, 82, 0, centralizarTextoX($width, 82, $fonteTitulo, $titulo1), 120, $corBranco, $fonteTitulo, $titulo1);
    
    $corBranco2 = imagecolorallocate($im, 255, 255, 255);
    $corTexto = imagecolorallocate($im, 0, 0, 0);
    $retanguloLargura = 1135;
    $retanguloAltura = 130;
    $cantoRaio = 15; 
    $retanguloX = ($width - $retanguloLargura) / 2;
    $retanguloY = 348; 
    
    desenhar_retangulo_arredondado($im, $retanguloX, $retanguloY, $retanguloLargura, $retanguloAltura, $cantoRaio, $corBranco2);
    
    $fontPath2 = __DIR__ . '/../fonts/CalSans-Regular.ttf';
    $tamanhoFonte = 78;
    $textoX = centralizarTextoX($width, $tamanhoFonte, $fontPath2, $dataTexto);
    $textoY_preciso = $retanguloY + 45;
    
    desenharTexto($im, $dataTexto, $textoX, $textoY_preciso, $corTexto, $tamanhoFonte);

    // Logo das ligas (cache)
    if ($logoLiga === null) {
        $ligas_url = 'https://i.ibb.co/W4nVKgd3/tlx.png';
        $logoLiga = @imagecreatefrompng($ligas_url);
    }
    
    if ($logoLiga) {
        imagecopy($im, $logoLiga, 0, 1740, 0, 0, imagesx($logoLiga), imagesy($logoLiga));
    }

    // Logo do usuário
    $logoUsuario = loadUserImage($userId, 'logo_banner_3');
    if ($logoUsuario) {
        $w = imagesx($logoUsuario); $h = imagesy($logoUsuario);
        if ($w > 0 && $h > 0) {
            $scale = min(350 / $w, 350 / $h, 1.0);
            $newW = (int)($w * $scale); $newH = (int)($h * $scale);
            $logoRedimensionada = imagecreatetruecolor($newW, $newH);
            imagealphablending($logoRedimensionada, false); imagesavealpha($logoRedimensionada, true);
            imagecopyresampled($logoRedimensionada, $logoUsuario, 0, 0, 0, 0, $newW, $newH, $w, $h);
            imagecopy($im, $logoRedimensionada, 10, 5, 0, 0, $newW, $newH);
            imagedestroy($logoRedimensionada);
        }
        imagedestroy($logoUsuario);
    }
    
    return $im;
}
?>