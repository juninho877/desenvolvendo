<?php

session_start();
if (!isset($_SESSION["usuario"])) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['image'])) {
    echo "Imagem nПлкo especificada.";
    exit;
}

$token = '7114525729:AAG4gmFWxifEztK5v0e8MQOSWpm4rIFkNu4'; // Substitua pelo token do seu bot
$chat_id = '-1001889169661'; // Substitua pelo ID do chat ou grupo
$image = $_GET['image'];

$fullImagePath = __DIR__ . '/' . $image;

if (!file_exists($fullImagePath)) {
    echo "Arquivo nПлкo encontrado.";
    exit;
}

$url = "https://api.telegram.org/bot$token/sendPhoto";
$postFields = [
    'chat_id' => $chat_id,
    'photo'   => new CURLFile(realpath($fullImagePath)),
    'caption' => 'Banner gerado por ?'
];

$ch = curl_init(); 
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type:multipart/form-data"]);
curl_setopt($ch, CURLOPT_URL, $url); 
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields); 
$result = curl_exec($ch);
curl_close($ch);

echo "Imagem enviada com sucesso!";
?>