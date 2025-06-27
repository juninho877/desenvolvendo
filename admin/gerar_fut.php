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
    // Cache para imagens estáticas
    static $fundoJogo = null;
    static $logoLiga = null;
    
    // Obter ID do usuário da sessão
    $userId = $_SESSION['user_id'];
    
    if ($fundoJogo === null) {
        $fundoJogo = loadUserImage($userId, 'card_banner_1');
        if (!$fundoJogo) {
            // Fallback para imagem padrão
            $fundoJogoPath = __DIR__ . '/fzstore/card/card_banner_1.png';
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
        
        $fonteNomes = __DIR__ . '/fonts/CalSans-Regular.ttf';
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
    $fonteTitulo = __DIR__ . '/fonts/BebasNeue-Regular.ttf';
    $fonteData = __DIR__ . '/fonts/RobotoCondensed-VariableFont_wght.ttf';
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
}

// Buscar dados dos jogos
$jogos = obterJogosDeHoje();

if (empty($jogos)) {
    header('Content-Type: image/png');
    $im = imagecreatetruecolor(600, 100);
    imagefill($im, 0, 0, imagecolorallocate($im, 255, 255, 255));
    imagestring($im, 5, 10, 40, "Nenhum jogo disponivel.", imagecolorallocate($im, 0, 0, 0));
    imagepng($im);
    imagedestroy($im);
    exit;
}

$width = 1440;
$heightPorJogo = 240;
$padding = 15;
$espacoExtra = 649;
$fontLiga = __DIR__ . '/fonts/MANDATOR.ttf';
$jogosPorBanner = 5;
$gruposDeJogos = array_chunk(array_keys($jogos), $jogosPorBanner);

// Inicializar cache
$bannerCache = new BannerCache();
$userId = $_SESSION['user_id'];

// Download de todos os banners
if (isset($_GET['download_all']) && $_GET['download_all'] == 1) {
    // Registrar estatística para download de todos os banners
    $bannerStats = new BannerStats();
    $bannerStats->recordBannerGeneration($userId, 'football', 'tema1_all', 'Banners Futebol - Todos');
    
    // Gerar chave de cache para todos os banners
    $allBannersCacheKey = $bannerCache->generateCacheKey($userId, 'football_1_all', 'all', $jogos);
    
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
    
    // Gerar ZIP se não estiver em cache
    $zip = new ZipArchive();
    $zipNome = "banners_topplay_" . date('Y-m-d') . ".zip";
    $caminhoTempZip = sys_get_temp_dir() . '/' . uniqid('banners_') . '.zip';
    $tempFiles = [];

    if ($zip->open($caminhoTempZip, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
        foreach ($gruposDeJogos as $index => $grupoJogos) {
            $numJogosNesteBanner = count($grupoJogos);
            $height = max($numJogosNesteBanner * $heightPorJogo + $padding * 2 + $espacoExtra, 2030);

            // Verificar cache individual primeiro
            $cacheKey = $bannerCache->generateCacheKey($userId, 'football_1', $index, array_intersect_key($jogos, array_flip($grupoJogos)));
            $cachedBanner = $bannerCache->getCachedBanner($userId, $cacheKey);
            
            if ($cachedBanner && file_exists($cachedBanner['file_path'])) {
                // Usar banner do cache
                $zip->addFile($cachedBanner['file_path'], 'banner_topplay_' . ($index + 1) . '.png');
            } else {
                // Gerar banner
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

                gerarBanner($im, $jogos, $grupoJogos, $padding, $heightPorJogo, $width, $preto, $branco, $fontLiga);
                
                // Salvar no cache
                $bannerCache->saveBannerToCache($userId, $cacheKey, $im, 'football_1', $index);
                
                $nomeArquivoTemp = sys_get_temp_dir() . '/banner_topplay_' . uniqid() . '.png';
                imagepng($im, $nomeArquivoTemp);
                
                $zip->addFile($nomeArquivoTemp, 'banner_topplay_' . ($index + 1) . '.png');
                $tempFiles[] = $nomeArquivoTemp;
                imagedestroy($im);
            }
        }
        $zip->close();

        // Salvar ZIP no cache
        $zipResource = imagecreatetruecolor(1, 1); // Dummy resource para compatibilidade
        $bannerCache->saveBannerToCache($userId, $allBannersCacheKey, $zipResource, 'football_1', null, $zipNome);
        imagedestroy($zipResource);
        
        // Copiar ZIP para cache
        $cacheZipPath = sys_get_temp_dir() . '/futbanner_cache/zip_' . $userId . '_' . $allBannersCacheKey . '.zip';
        copy($caminhoTempZip, $cacheZipPath);

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
} else {
    // Gerar banner individual
    $grupoIndex = isset($_GET['grupo']) ? (int)$_GET['grupo'] : 0;
    if (!isset($gruposDeJogos[$grupoIndex])) {
        header('Content-Type: image/png');
        $im = imagecreatetruecolor(600, 100);
        imagefill($im, 0, 0, imagecolorallocate($im, 255, 255, 255));
        imagestring($im, 5, 10, 40, "Banner invalido.", imagecolorallocate($im, 0, 0, 0));
        imagepng($im);
        imagedestroy($im);
        exit;
    }

    $grupoJogos = $gruposDeJogos[$grupoIndex];
    
    // Gerar chave de cache
    $jogosDoGrupo = array_intersect_key($jogos, array_flip($grupoJogos));
    $cacheKey = $bannerCache->generateCacheKey($userId, 'football_1', $grupoIndex, $jogosDoGrupo);
    
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
    $height = max($numJogosNesteBanner * $heightPorJogo + $padding * 2 + $espacoExtra, 2030);

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

    gerarBanner($im, $jogos, $grupoJogos, $padding, $heightPorJogo, $width, $preto, $branco, $fontLiga);

    // Registrar estatística para banner individual
    $bannerStats = new BannerStats();
    $bannerStats->recordBannerGeneration($userId, 'football', 'tema1', 'Banner Futebol - Parte ' . ($grupoIndex + 1));

    // Salvar no cache
    $originalName = "banner_topplay_" . date('Y-m-d') . "_parte" . ($grupoIndex + 1) . ".png";
    $cachedResult = $bannerCache->saveBannerToCache($userId, $cacheKey, $im, 'football_1', $grupoIndex, $originalName);

    if (isset($_GET['download']) && $_GET['download'] == 1) {
        header('Content-Disposition: attachment; filename="' . $originalName . '"');
    }

    header('Content-Type: image/png');
    header('Cache-Control: public, max-age=3600'); // Cache por 1 hora
    imagepng($im);
    imagedestroy($im);
    exit;
}
?>