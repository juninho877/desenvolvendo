<?php
session_start();
date_default_timezone_set('America/Sao_Paulo');

// Verificação de sessão primeiro
if (!isset($_SESSION["usuario"])) {
    http_response_code(403);
    header('Content-Type: image/png');
    $im = imagecreatetruecolor(600, 100);
    imagefill($im, 0, 0, imagecolorallocate($im, 255, 255, 255));
    imagestring($im, 5, 10, 40, "Erro: Acesso Negado.", imagecolorallocate($im, 0, 0, 0));
    imagepng($im);
    imagedestroy($im);
    exit();
}

require_once 'includes/banner_functions.php';
require_once 'classes/BannerStats.php';
require_once 'classes/BannerCache.php';

function gerarBanner($im, $jogos, $grupoJogos, $padding, $heightPorJogo, $width, $preto, $branco, $fontLiga) {
    static $fundoJogo = null;
    
    // Obter ID do usuário da sessão
    $userId = $_SESSION['user_id'];
    
    if ($fundoJogo === null) {
        $fundoJogo = loadUserImage($userId, 'card_banner_2');
        if (!$fundoJogo) {
            // Fallback para imagem padrão
            $fundoJogoPath = __DIR__ . '/fzstore/card/card_banner_2.png';
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
    static $logoLiga = null;
    if ($logoLiga === null) {
        $ligas_url = 'https://i.ibb.co/ycxpN2rc/Rodape-liga-720.png';
        $logoLiga = @imagecreatefrompng($ligas_url);
    }
    
    if ($logoLiga) {
        imagecopy($im, $logoLiga, 40, 870, 0, 0, imagesx($logoLiga), imagesy($logoLiga));
    }
    
    $fonteTitulo = __DIR__ . '/fonts/BebasNeue-Regular.ttf';
    $fonteData = __DIR__ . '/fonts/RobotoCondensed-VariableFont_wght.ttf';
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
}

$jogos = obterJogosDeHoje();

if (empty($jogos)) {
    header('Content-Type: image/png'); 
    $im = imagecreatetruecolor(600, 100);
    $bg = imagecolorallocate($im, 255, 255, 255); imagefill($im, 0, 0, $bg);
    $color = imagecolorallocate($im, 0, 0, 0);
    imagestring($im, 5, 10, 40, "Nenhum jogo disponível.", $color);
    imagepng($im); imagedestroy($im); exit;
}

$jogosPorBanner = 5;
$gruposDeJogos = array_chunk(array_keys($jogos), $jogosPorBanner);
$width = 720;
$heightPorJogo = 140;
$padding = 15;
$espacoExtra = 200;
$fontLiga = __DIR__ . '/fonts/MANDATOR.ttf';

// Inicializar cache
$bannerCache = new BannerCache();
$userId = $_SESSION['user_id'];

if (isset($_GET['download_all']) && $_GET['download_all'] == 1) {
    // Registrar estatística para download de todos os banners
    $bannerStats = new BannerStats();
    $bannerStats->recordBannerGeneration($userId, 'football', 'tema2_all', 'Banners Futebol V2 - Todos');
    
    // Gerar chave de cache para todos os banners
    $allBannersCacheKey = $bannerCache->generateCacheKey($userId, 'football_2_all', 'all', $jogos);
    
    // Verificar se existe cache válido para o ZIP
    $cachedZip = $bannerCache->getCachedBanner($userId, $allBannersCacheKey);
    
    if ($cachedZip && file_exists($cachedZip['file_path'])) {
        // Servir ZIP do cache
        if (ob_get_level()) ob_end_clean();
        
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $cachedZip['original_name'] . '"');
        header('Content-Length: ' . filesize($cachedZip['file_path']));
        header('Pragma: no-cache');
        header('Expires: 0');
        
        readfile($cachedZip['file_path']);
        exit;
    }
    
    $zip = new ZipArchive();
    $zipNome = "banners_Top_V3_" . date('Y-m-d') . ".zip";
    $caminhoTempZip = sys_get_temp_dir() . '/' . uniqid('banners_V3_') . '.zip';
    $tempFiles = [];

    if ($zip->open($caminhoTempZip, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
        foreach ($gruposDeJogos as $index => $grupoJogos) {
            $numJogosNesteBanner = count($grupoJogos);
            $height = max($numJogosNesteBanner * $heightPorJogo + $padding * 2 + $espacoExtra, 1015);

            // Verificar cache individual primeiro
            $cacheKey = $bannerCache->generateCacheKey($userId, 'football_2', $index, array_intersect_key($jogos, array_flip($grupoJogos)));
            $cachedBanner = $bannerCache->getCachedBanner($userId, $cacheKey);
            
            if ($cachedBanner && file_exists($cachedBanner['file_path'])) {
                // Usar banner do cache
                $zip->addFile($cachedBanner['file_path'], 'banner_V3_parte_' . ($index + 1) . '.png');
            } else {
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

                gerarBanner($im, $jogos, $grupoJogos, $padding, $heightPorJogo, $width, $preto, $branco, $fontLiga);
                
                // Salvar no cache
                $bannerCache->saveBannerToCache($userId, $cacheKey, $im, 'football_2', $index);
                
                $nomeArquivoTemp = sys_get_temp_dir() . '/banner_V3_parte_' . uniqid() . '.png';
                imagepng($im, $nomeArquivoTemp);
                
                $zip->addFile($nomeArquivoTemp, 'banner_V3_parte_' . ($index + 1) . '.png');
                $tempFiles[] = $nomeArquivoTemp;
                imagedestroy($im);
            }
        }
        $zip->close();

        if (ob_get_level()) ob_end_clean();
        
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $zipNome . '"');
        header('Content-Length: ' . filesize($caminhoTempZip));
        header('Pragma: no-cache');
        header('Expires: 0');

        if(readfile($caminhoTempZip)) {
            foreach ($tempFiles as $file) {
                if (file_exists($file)) unlink($file);
            }
            unlink($caminhoTempZip);
        }
        exit;
    } else {
        die("Erro: Não foi possível criar o arquivo ZIP.");
    }
}

$grupoIndex = isset($_GET['grupo']) ? (int)$_GET['grupo'] : 0;
if (!isset($gruposDeJogos[$grupoIndex])) {
    header('Content-Type: image/png'); 
    $im = imagecreatetruecolor(600, 100);
    $bg = imagecolorallocate($im, 255, 255, 255); imagefill($im, 0, 0, $bg);
    $color = imagecolorallocate($im, 0, 0, 0);
    imagestring($im, 5, 10, 40, "Banner inválido.", $color);
    imagepng($im); imagedestroy($im); exit;
}

$grupoJogos = $gruposDeJogos[$grupoIndex];

// Gerar chave de cache
$jogosDoGrupo = array_intersect_key($jogos, array_flip($grupoJogos));
$cacheKey = $bannerCache->generateCacheKey($userId, 'football_2', $grupoIndex, $jogosDoGrupo);

// Verificar cache
$cachedBanner = $bannerCache->getCachedBanner($userId, $cacheKey);

if ($cachedBanner && file_exists($cachedBanner['file_path'])) {
    // Servir do cache
    $download = isset($_GET['download']) && $_GET['download'] == 1;
    $bannerCache->serveCachedFile($cachedBanner['file_path'], $cachedBanner['original_name'], $download);
    exit;
}

// Gerar banner se não estiver em cache
$numJogosNesteBanner = count($grupoJogos);
$height = max($numJogosNesteBanner * $heightPorJogo + $padding * 2 + $espacoExtra, 1015);

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

gerarBanner($im, $jogos, $grupoJogos, $padding, $heightPorJogo, $width, $preto, $branco, $fontLiga);

// Registrar estatística para banner individual
$bannerStats = new BannerStats();
$bannerStats->recordBannerGeneration($userId, 'football', 'tema2', 'Banner Futebol V2 - Parte ' . ($grupoIndex + 1));

// Salvar no cache
$originalName = "banner_V3_" . date('Y-m-d') . "_parte" . ($grupoIndex + 1) . ".png";
$bannerCache->saveBannerToCache($userId, $cacheKey, $im, 'football_2', $grupoIndex, $originalName);

if (isset($_GET['download']) && $_GET['download'] == 1) {
    header('Content-Disposition: attachment; filename="' . $originalName . '"');
}

header('Content-Type: image/png');
header('Cache-Control: public, max-age=300');
imagepng($im);
imagedestroy($im);
exit;
?>