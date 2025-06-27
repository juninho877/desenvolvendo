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
?>