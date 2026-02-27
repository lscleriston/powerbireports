<?php
// Arquivo: front/report.view.php
// Visualização de relatório Power BI

include('../../../inc/includes.php');

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

// Verificar se o ID do relatório foi passado
$report_id = $_GET['id'] ?? null;
if (!$report_id) {
    Html::displayNotFoundError();
}

// Buscar o relatório
$report = null;
try {
    $reportItem = new ReportItem();
    if ($reportItem->getFromDB($report_id)) {
        $report = $reportItem->fields;
    }
} catch (\Throwable $e) {
    Html::displayErrorAndDie(__('Error loading report', 'powerbireports'));
}

if (!$report) {
    Html::displayNotFoundError();
}

// Obter configuração (credenciais)
$config = [];
try {
    $config = Config::getConfig();
} catch (\Throwable $e) {
    Html::displayErrorAndDie(__('Error loading configuration', 'powerbireports'));
}

$page_title = $report['name'] . ' - Power BI Report';
Html::header($page_title, Plugin::getWebDir('powerbireports') . '/front/index.php', "plugins", "pluginpowerbireportsmenu");

echo "<div class='center'>";

// Verificar se as credenciais estão configuradas
if (empty($config['tenant_id']) || empty($config['client_id']) || empty($config['client_secret'])) {
    echo "<div class='alert alert-danger'>";
    echo __('Power BI API credentials are not configured. Please configure them first.', 'powerbireports');
    echo "</div>";
    echo "<a href='" . Plugin::getWebDir('powerbireports') . "/front/config.form.php' class='btn btn-primary'>";
    echo __('Go to Configuration', 'powerbireports');
    echo "</a>";
} else {
    // Container para o relatório com estilo ajustado
    echo "<div id='reportContainer' style='width:100%; height:800px; position:relative; top:0px; margin-top:0px; overflow:hidden;'></div>";
    echo "<div id='loading-message' style='text-align:center; padding:20px;'>";
    echo "<p><i class='fa fa-spinner fa-pulse'></i> " . __('Loading Power BI report...', 'powerbireports') . "</p>";
    echo "</div>";
    
    // Scripts do Power BI
    echo "<script src='https://cdn.jsdelivr.net/npm/powerbi-client@2.22.4/dist/powerbi.min.js'></script>";
    
    // Script para carregar o relatório
    echo "<script type='text/javascript'>
    (function() {
        // Obter o token de embed
        fetch('" . Plugin::getWebDir('powerbireports') . "/ajax/get_embed_token.php?group_id=" . urlencode($report['group_id']) . "&report_id=" . urlencode($report['report_id']) . "')
        .then(response => {
            if (!response.ok) {
                throw new Error('Failed to get embed token');
            }
            return response.json();
        })
        .then(data => {
            // Esconder mensagem de loading
            document.getElementById('loading-message').style.display = 'none';
            
            if (data.error) {
                document.getElementById('reportContainer').innerHTML = '<div class=\"alert alert-danger\">' + data.error + '</div>';
                return;
            }
            
            // Configurar o relatório
            var models = window['powerbi-client'].models;
            var embedConfiguration = {
                type: 'report',
                tokenType: models.TokenType.Embed,
                accessToken: data.token,
                embedUrl: data.embedUrl,
                id: data.reportId,
                permissions: models.Permissions.All,
                settings: {
                    panes: {
                        filters: {
                            expanded: false,
                            visible: true
                        },
                        pageNavigation: {
                            visible: true
                        }
                    },
                    background: models.BackgroundType.Default
                }
            };
            
            // Obter o container e embedar o relatório
            var reportContainer = document.getElementById('reportContainer');
            var report = powerbi.embed(reportContainer, embedConfiguration);
            
            // Eventos
            report.on('loaded', function() {
                console.log('Report loaded successfully');
            });
            
            report.on('error', function(event) {
                console.error('Error loading report:', event.detail);
                reportContainer.innerHTML = '<div class=\"alert alert-danger\">Error loading report: ' + event.detail.message + '</div>';
            });
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('loading-message').style.display = 'none';
            document.getElementById('reportContainer').innerHTML = '<div class=\"alert alert-danger\">Error loading report: ' + error.message + '</div>';
        });
    })();
    </script>";
}

echo "</div>";

Html::footer();
