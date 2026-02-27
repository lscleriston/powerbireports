<?php
// Arquivo: ajax/get_embed_token.php
// Gera o embed token para um relatório específico

include('../../../inc/includes.php');

// Verificar se o usuário está logado
Session::checkLoginUser();

// Verificar permissões
Session::checkRight("powerbireports", READ);

use GlpiPlugin\Powerbireports\Config;

// Define o header para indicar que a resposta é JSON
header('Content-Type: application/json');

// Obter parâmetros do relatório
$group_id = $_GET['group_id'] ?? null;
$report_id = $_GET['report_id'] ?? null;

if (empty($group_id) || empty($report_id)) {
    echo json_encode([
        'success' => false,
        'error' => 'Missing required parameters: group_id and report_id'
    ]);
    exit;
}

// Obter configuração
$config = Config::getConfig();

if (empty($config['tenant_id']) || empty($config['client_id']) || empty($config['client_secret'])) {
    echo json_encode([
        'success' => false,
        'error' => 'Power BI API credentials are not configured'
    ]);
    exit;
}

// Gerar Access Token (autenticação com Azure AD)
$access_token = Config::generateAccessToken();

if (!$access_token) {
    echo json_encode([
        'success' => false,
        'error' => 'Failed to generate access token. Check your API credentials.'
    ]);
    exit;
}

// Gerar Embed Token para o relatório específico
$embed_url = "https://api.powerbi.com/v1.0/myorg/groups/{$group_id}/reports/{$report_id}/GenerateToken";

$ch = curl_init($embed_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $access_token,
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'accessLevel' => 'View',
    'allowSaveAs' => false
]));

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code !== 200) {
    $error_data = json_decode($response, true);
    $error_message = $error_data['error']['message'] ?? 'Unknown error generating embed token';
    echo json_encode([
        'success' => false,
        'error' => $error_message
    ]);
    exit;
}

$token_data = json_decode($response, true);

if (!isset($token_data['token'])) {
    echo json_encode([
        'success' => false,
        'error' => 'Invalid response from Power BI API'
    ]);
    exit;
}

// Retornar os dados necessários para embed
echo json_encode([
    'success' => true,
    'token' => $token_data['token'],
    'embedUrl' => "https://app.powerbi.com/reportEmbed?reportId={$report_id}&groupId={$group_id}",
    'reportId' => $report_id,
    'expiration' => $token_data['expiration'] ?? null
]);