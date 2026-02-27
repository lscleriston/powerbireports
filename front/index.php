<?php
// Arquivo: front/index.php
// Central de Relatórios Power BI

if (!defined('GLPI_ROOT')) {
    // Try GLPI root 3 levels up (common when GLPI is at /var/www/html/glpi)
    $candidate3 = realpath(__DIR__ . '/../../..');
    if ($candidate3 && file_exists($candidate3 . '/inc/includes.php')) {
        define('GLPI_ROOT', $candidate3);
    } else {
        // Fallback: 4 levels up (older layouts)
        $candidate4 = realpath(__DIR__ . '/../../../..');
        if ($candidate4 && file_exists($candidate4 . '/inc/includes.php')) {
            define('GLPI_ROOT', $candidate4);
        }
    }
}
if (!defined('GLPI_ROOT')) {
    die('GLPI_ROOT not found');
}
include(GLPI_ROOT . '/inc/includes.php');

// Verificar se o usuário está logado
Session::checkLoginUser();

// Verificar permissões
Session::checkRight("powerbireports", READ);

use GlpiPlugin\Powerbireports\Config;
use GlpiPlugin\Powerbireports\ReportItem;

$plugin = new Plugin();
if (!$plugin->isActivated('powerbireports')) {
    Html::displayNotFoundError();
}

Html::header(__('Power BI Reports', 'powerbireports'), $_SERVER['PHP_SELF'], "plugins", "pluginpowerbireportsmenu");

// Obter configuração
$config = [];
try {
    $config = Config::getConfig();
} catch (\Throwable $e) {
    // Ignora erros
}

// Obter relatórios cadastrados (apenas os que o usuário pode visualizar)
$reports = [];
try {
    $current_user_id = Session::getLoginUserID();
    $reports = ReportItem::getReportsForUser($current_user_id);
} catch (\Throwable $e) {
    // Ignora erros
}

echo "<div class='center'>";

// Cabeçalho
echo "<div style='margin-bottom: 20px;'>";
echo "<h1><i class='fas fa-chart-line'></i> " . __('Power BI Reports Center', 'powerbireports') . "</h1>";
echo "<p style='color: #666;'>" . __('Select a report to view', 'powerbireports') . "</p>";
echo "</div>";

// Verificar se as credenciais estão configuradas
if (empty($config['tenant_id']) || empty($config['client_id']) || empty($config['client_secret'])) {
    echo "<div class='alert alert-warning'>";
    echo "<p><i class='fas fa-exclamation-triangle'></i> " . __('Power BI API credentials are not configured.', 'powerbireports') . "</p>";
    
    if (Session::haveRight("powerbireports", UPDATE)) {
        echo "<p><a href='" . Plugin::getWebDir('powerbireports') . "/front/config.form.php' class='btn btn-primary'>";
        echo __('Go to Configuration', 'powerbireports');
        echo "</a></p>";
    } else {
        echo "<p>" . __('Please contact an administrator to configure the plugin.', 'powerbireports') . "</p>";
    }
    echo "</div>";
} elseif (empty($reports)) {
    // Sem relatórios cadastrados
    echo "<div class='alert alert-info'>";
    echo "<p><i class='fas fa-info-circle'></i> " . __('No reports registered yet.', 'powerbireports') . "</p>";
    
    if (Session::haveRight("powerbireports", UPDATE)) {
        echo "<p><a href='" . Plugin::getWebDir('powerbireports') . "/front/config.form.php' class='btn btn-primary'>";
        echo __('Add Reports', 'powerbireports');
        echo "</a></p>";
    } else {
        echo "<p>" . __('Please contact an administrator to add reports.', 'powerbireports') . "</p>";
    }
    echo "</div>";
} else {
    // Mostrar cards dos relatórios
    echo "<div class='d-flex flex-wrap justify-content-center' style='gap: 20px;'>";
    
    global $CFG_GLPI;
    
    foreach ($reports as $report) {
        echo "<div class='card' style='width: 300px; margin: 10px; border: 1px solid #ddd; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);'>";
        echo "<div class='card-body' style='padding: 20px;'>";
        
        // Ícone e título
        echo "<div style='text-align: center; margin-bottom: 15px;'>";
        
        // Verificar se tem ícone personalizado
        if (!empty($report['icon_path']) && file_exists(GLPI_ROOT . '/' . $report['icon_path'])) {
            $icon_filename = basename($report['icon_path']);
            $icon_url = Plugin::getWebDir('powerbireports') . '/front/icon.php?file=' . urlencode($icon_filename);
            echo "<img src='" . htmlspecialchars($icon_url) . "' alt='" . htmlspecialchars($report['name']) . "' style='width: 64px; height: 64px; object-fit: contain;'>";
        } else {
            // Ícone padrão
            echo "<i class='fas fa-chart-bar' style='font-size: 48px; color: #f2c811;'></i>";
        }
        echo "</div>";
        
        echo "<h4 style='text-align: center; margin-bottom: 10px;'>" . htmlspecialchars($report['name']) . "</h4>";
        
        if (!empty($report['description'])) {
            echo "<p style='color: #666; font-size: 14px; text-align: center; margin-bottom: 15px;'>" . htmlspecialchars($report['description']) . "</p>";
        }
        
        // Exibir data da última atualização do relatório via API do Power BI
        echo "<div style='margin-bottom: 15px;'>";
        echo "<div style='text-align: center; font-size: 12px; color: #666; margin-bottom: 5px;'>Última atualização</div>";
        
        $last_update_info = null;
        try {
            $last_update_info = getPowerBIReportLastUpdate($config, $report);
        } catch (Throwable $e) {
            error_log('PowerBI Reports: Exception getting last update: ' . $e->getMessage());
        }
        
        if (empty($last_update_info) || empty($last_update_info['formatted'])) {
            echo "<div style='background-color: #dc3545; color: white; padding: 4px 8px; border-radius: 4px; text-align: center; font-weight: bold; font-size: 12px;'>";
            echo "Indisponível";
            echo "</div>";
        } else {
            $bg_color = $last_update_info['is_today'] ? '#28a745' : '#dc3545';
            echo "<div style='background-color: $bg_color; color: white; padding: 4px 8px; border-radius: 4px; text-align: center; font-weight: bold; font-size: 12px;'>";
            echo htmlspecialchars($last_update_info['formatted']);
            echo "</div>";
        }
        echo "</div>";
        
        // Botão para visualizar
        echo "<div style='text-align: center;'>";
        echo "<a href='" . Plugin::getWebDir('powerbireports') . "/front/report.view.php?id=" . $report['id'] . "' class='btn btn-primary' style='width: 100%;'>";
        echo "<i class='fas fa-eye'></i> " . __('View Report', 'powerbireports');
        echo "</a>";
        echo "</div>";
        
        echo "</div>";
        echo "</div>";
    }
    
    echo "</div>";
    
    // Link para configuração (se tiver permissão)
    if (Session::haveRight("powerbireports", UPDATE)) {
        echo "<div style='margin-top: 30px;'>";
        echo "<a href='" . Plugin::getWebDir('powerbireports') . "/front/config.form.php' class='btn btn-secondary'>";
        echo "<i class='fas fa-cog'></i> " . __('Manage Reports', 'powerbireports');
        echo "</a>";
        echo "</div>";
    }
}

echo "</div>";

function getPowerBIReportLastUpdate($config, $report) {
    $group_id = $report['group_id'] ?? null;
    $report_id = $report['report_id'] ?? null;
    $dataset_id = $report['dataset_id'] ?? null;
    $update_mode = $report['update_mode'] ?? 'api';
    $update_table = $report['update_table'] ?? '';
    $update_column = $report['update_column'] ?? '';

    if (empty($group_id) || empty($report_id)) {
        return null;
    }

    try {
        $access_token = Config::generateAccessToken();
    } catch (Exception $e) {
        error_log('PowerBI Reports: Error generating access token: ' . $e->getMessage());
        return null;
    }

    if (!$access_token) {
        error_log('PowerBI Reports: No access token available');
        return null;
    }

    // 1) Garantir dataset_id
    if (empty($dataset_id)) {
        $report_url = "https://api.powerbi.com/v1.0/myorg/groups/$group_id/reports/$report_id";
        $ch = curl_init($report_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer $access_token",
            "Content-Type: application/json"
        ]);
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);
        if ($curl_error) {
            error_log('PowerBI Reports: CURL error fetching report metadata: ' . $curl_error);
        }
        if ($http_code !== 200 || !$response) {
            error_log('PowerBI Reports: Failed to get report metadata - HTTP ' . $http_code);
            return null;
        }
        $meta = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('PowerBI Reports: JSON decode error for report metadata: ' . json_last_error_msg());
            return null;
        }
        if (!empty($meta['datasetId'])) {
            $dataset_id = $meta['datasetId'];
        } else {
            error_log('PowerBI Reports: datasetId not present in report metadata');
            return null;
        }
    }

    // 2) Se modo tabela/coluna, consultar MAX na coluna indicada
    if ($update_mode === 'table_column' && $update_table && $update_column) {
        $safeTable = str_replace("'", "''", $update_table);
        $safeColumn = str_replace("'", "''", $update_column);
        $dax = "EVALUATE ROW(\"last_update\", MAX('{$safeTable}'[{$safeColumn}]))";
        $queryUrl = "https://api.powerbi.com/v1.0/myorg/groups/$group_id/datasets/$dataset_id/executeQueries";
        $payload = json_encode([
            'queries' => [ ['query' => $dax] ],
            'serializerSettings' => ['includeNulls' => true]
        ]);

        $ch = curl_init($queryUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer $access_token",
            "Content-Type: application/json"
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        if ($curl_error) {
            error_log('PowerBI Reports: CURL error on executeQueries: ' . $curl_error);
        }

        if ($http_code === 200 && $response) {
            $data = json_decode($response, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $rows = $data['results'][0]['tables'][0]['rows'] ?? [];
                if (!empty($rows[0]['last_update'])) {
                    $value = $rows[0]['last_update'];
                    try {
                        $dt = new DateTime($value);
                        $formatted = $dt->format('d/m/Y H:i:s');
                        $today = new DateTime();
                        $is_today = ($dt->format('Y-m-d') === $today->format('Y-m-d'));
                        return [
                            'formatted' => $formatted,
                            'is_today' => $is_today
                        ];
                    } catch (Exception $e) {
                        error_log('PowerBI Reports: Error formatting date from table/column: ' . $e->getMessage());
                    }
                }
            } else {
                error_log('PowerBI Reports: JSON decode error (executeQueries): ' . json_last_error_msg());
            }
        } else {
            error_log('PowerBI Reports: executeQueries failed - HTTP ' . $http_code . ' - Response: ' . substr($response, 0, 200));
        }
        // se falhar, continua para modo API padrão como fallback
    }

    // 3) Fallback: última execução de refresh do dataset (modo API)
    $refresh_url = "https://api.powerbi.com/v1.0/myorg/groups/$group_id/datasets/$dataset_id/refreshes?$top=1";
    $ch = curl_init($refresh_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $access_token",
        "Content-Type: application/json"
    ]);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    if ($curl_error) {
        error_log('PowerBI Reports: CURL error on refreshes: ' . $curl_error);
    }
    
    if ($http_code !== 200 || !$response) {
        error_log('PowerBI Reports: refreshes failed - HTTP ' . $http_code . ' - Response: ' . substr($response, 0, 200));
        return null;
    }
    $data = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log('PowerBI Reports: JSON decode error (refreshes): ' . json_last_error_msg());
        return null;
    }
    if (!empty($data['value'][0]['endTime'])) {
        $endTime = $data['value'][0]['endTime'];
        try {
            $dt = new DateTime($endTime);
            $formatted = $dt->format('d/m/Y H:i:s');
            $today = new DateTime();
            $is_today = ($dt->format('Y-m-d') === $today->format('Y-m-d'));
            return [
                'formatted' => $formatted,
                'is_today' => $is_today
            ];
        } catch (Exception $e) {
            error_log('PowerBI Reports: Error formatting date: ' . $e->getMessage());
            return null;
        }
    }
    return null;
}

Html::footer();
