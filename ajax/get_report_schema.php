<?php
// Lista tabelas e colunas de um relatório Power BI

include('../../../inc/includes.php');

Session::checkLoginUser();
Session::checkRight("powerbireports", READ);

use GlpiPlugin\Powerbireports\Config;

header('Content-Type: application/json');

$group_id = $_GET['group_id'] ?? null;
$report_id = $_GET['report_id'] ?? null;

if (empty($group_id) || empty($report_id)) {
    echo json_encode(['success' => false, 'error' => 'Missing required parameters: group_id and report_id']);
    exit;
}

$config = Config::getConfig();
if (empty($config['tenant_id']) || empty($config['client_id']) || empty($config['client_secret'])) {
    echo json_encode(['success' => false, 'error' => 'Power BI API credentials are not configured']);
    exit;
}

$access_token = Config::generateAccessToken();
if (!$access_token) {
    echo json_encode(['success' => false, 'error' => 'Failed to generate access token. Check your API credentials.']);
    exit;
}

// 1) Obter datasetId do relatório
$reportUrl = "https://api.powerbi.com/v1.0/myorg/groups/{$group_id}/reports/{$report_id}";
$ch = curl_init($reportUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $access_token,
    'Content-Type: application/json'
]);
$reportResponse = curl_exec($ch);
$reportHttp = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($reportHttp !== 200) {
    $error_data = json_decode($reportResponse, true);
    $msg = $error_data['error']['message'] ?? 'Unable to fetch report metadata';
    echo json_encode(['success' => false, 'error' => $msg]);
    exit;
}

$reportData = json_decode($reportResponse, true);
$datasetId = $reportData['datasetId'] ?? null;
if (empty($datasetId)) {
    echo json_encode(['success' => false, 'error' => 'datasetId not found for this report']);
    exit;
}

// 2) Listar tabelas e colunas do dataset
$tablesUrl = "https://api.powerbi.com/v1.0/myorg/groups/{$group_id}/datasets/{$datasetId}/tables";
$ch = curl_init($tablesUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $access_token,
    'Content-Type: application/json'
]);
$tablesResponse = curl_exec($ch);
$tablesHttp = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($tablesHttp !== 200) {
    $error_data = json_decode($tablesResponse, true);
    $msg = $error_data['error']['message'] ?? 'Unable to fetch dataset tables';
    echo json_encode(['success' => false, 'error' => $msg]);
    exit;
}

$tablesData = json_decode($tablesResponse, true);
$tables = $tablesData['value'] ?? [];

$normalized = [];
foreach ($tables as $table) {
    $normalized[] = [
        'name' => $table['name'] ?? '',
        'columns' => $table['columns'] ?? []
    ];
}

echo json_encode([
    'success' => true,
    'tables' => $normalized
]);
