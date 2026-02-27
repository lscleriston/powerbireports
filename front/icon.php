<?php
// Arquivo: front/icon.php
// Serve os ícones dos relatórios

if (!defined('GLPI_ROOT')) {
    define('GLPI_ROOT', realpath(__DIR__ . '/../../..'));
}
include(GLPI_ROOT . '/inc/includes.php');

// Verificar se o usuário está logado
Session::checkLoginUser();

$filename = $_GET['file'] ?? '';

// Validar nome do arquivo (apenas alfanuméricos, underline e ponto)
if (!preg_match('/^[a-zA-Z0-9_]+\.(png|jpg|jpeg|gif|svg|webp)$/i', $filename)) {
    http_response_code(400);
    exit('Invalid filename');
}

$filepath = GLPI_ROOT . '/plugins/powerbireports/pics/icons/' . $filename;

if (!file_exists($filepath)) {
    http_response_code(404);
    exit('File not found');
}

// Determinar o tipo MIME
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $filepath);
finfo_close($finfo);

// Tipos permitidos
$allowed_mimes = [
    'image/png',
    'image/jpeg', 
    'image/gif',
    'image/svg+xml',
    'image/webp'
];

if (!in_array($mime, $allowed_mimes)) {
    http_response_code(403);
    exit('File type not allowed');
}

// Enviar headers
header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($filepath));
header('Cache-Control: public, max-age=86400'); // Cache de 1 dia

// Enviar arquivo
readfile($filepath);
exit;
