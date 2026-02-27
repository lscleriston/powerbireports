<?php
include('../../../inc/includes.php');

Session::checkLoginUser();

if (!Session::haveRight('powerbireports', UPDATE)) {
    Html::displayRightError();
    exit;
}

use GlpiPlugin\Powerbireports\Config;
use GlpiPlugin\Powerbireports\ReportItem;

$plugin = new Plugin();
if (!$plugin->isActivated('powerbireports')) {
    Html::displayNotFoundError();
}

// URL correta para o formulário (no GLPI 11, PHP_SELF retorna /index.php)
$form_url = Plugin::getWebDir('powerbireports') . '/front/config.form.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_config'])) {
        $configData = [
            'tenant_id' => $_POST['tenant_id'] ?? '',
            'client_id' => $_POST['client_id'] ?? '',
            'client_secret' => $_POST['client_secret'] ?? ''
        ];
        try {
            if (Config::saveConfig($configData)) {
                Session::addMessageAfterRedirect(__('Configuration saved successfully', 'powerbireports'), true, INFO);
            } else {
                Session::addMessageAfterRedirect(__('Error saving configuration', 'powerbireports'), true, ERROR);
            }
        } catch (\Throwable $e) {
            Session::addMessageAfterRedirect(__('Error: ', 'powerbireports') . $e->getMessage(), true, ERROR);
        }
        Html::redirect($form_url);
    }

    if (isset($_POST['add_report'])) {
        // Processar upload de ícone
        $icon_path = null;
        if (isset($_FILES['report_icon']) && $_FILES['report_icon']['error'] === UPLOAD_ERR_OK) {
            $icon_path = ReportItem::handleIconUpload($_FILES['report_icon']);
        }

        $reportData = [
            'name' => $_POST['report_name'] ?? '',
            'group_id' => $_POST['report_group_id'] ?? '',
            'report_id' => $_POST['report_report_id'] ?? '',
            'description' => $_POST['report_description'] ?? '',
            'icon_path' => $icon_path,
            'update_mode' => $_POST['report_update_mode'] ?? 'api',
            'update_table' => $_POST['report_update_table'] ?? null,
            'update_column' => $_POST['report_update_column'] ?? null
        ];
        try {
            if (ReportItem::addReport($reportData)) {
                Session::addMessageAfterRedirect(__('Report added successfully', 'powerbireports'), true, INFO);
            } else {
                Session::addMessageAfterRedirect(__('Error adding report', 'powerbireports'), true, ERROR);
            }
        } catch (\Throwable $e) {
            Session::addMessageAfterRedirect(__('Error: ', 'powerbireports') . $e->getMessage(), true, ERROR);
        }
        Html::redirect($form_url);
    }

    if (isset($_POST['delete_report']) && isset($_POST['report_id'])) {
        try {
            if (ReportItem::deleteReport($_POST['report_id'])) {
                Session::addMessageAfterRedirect(__('Report deleted successfully', 'powerbireports'), true, INFO);
            } else {
                Session::addMessageAfterRedirect(__('Error deleting report', 'powerbireports'), true, ERROR);
            }
        } catch (\Throwable $e) {
            Session::addMessageAfterRedirect(__('Error: ', 'powerbireports') . $e->getMessage(), true, ERROR);
        }
        Html::redirect($form_url);
    }
}

Html::header(__('Power BI Reports Configuration', 'powerbireports'), $form_url, "config", "plugins");

$config = [];
try {
    $config = Config::getConfig();
} catch (\Throwable $e) {
    echo '<div class="alert alert-danger">Error loading configuration: ' . htmlspecialchars($e->getMessage()) . '</div>';
}

$reports = [];
try {
    $reports = ReportItem::getAllReports();
} catch (\Throwable $e) {
    echo '<div class="alert alert-danger">Error loading reports: ' . htmlspecialchars($e->getMessage()) . '</div>';
}

Html::displayMessageAfterRedirect();

echo "<div class='center'>";

echo "<h2>" . __('Authorization Settings', 'powerbireports') . "</h2>";

echo "<form method='post' action='" . $form_url . "'>";
echo Html::hidden('_glpi_csrf_token', ['value' => Session::getNewCSRFToken()]);
echo "<table class='tab_cadre_fixe'>";
echo "<tr><th colspan='2'>" . __('Power BI API Credentials', 'powerbireports') . "</th></tr>";

echo "<tr class='tab_bg_1'>";
echo "<td>" . __('Tenant ID', 'powerbireports') . "</td>";
echo "<td><input type='text' name='tenant_id' value='" . htmlspecialchars($config['tenant_id'] ?? '') . "' size='50'></td>";
echo "</tr>";

echo "<tr class='tab_bg_1'>";
echo "<td>" . __('Client ID', 'powerbireports') . "</td>";
echo "<td><input type='text' name='client_id' value='" . htmlspecialchars($config['client_id'] ?? '') . "' size='50'></td>";
echo "</tr>";

echo "<tr class='tab_bg_1'>";
echo "<td>" . __('Client Secret', 'powerbireports') . "</td>";
echo "<td><input type='password' name='client_secret' value='" . htmlspecialchars($config['client_secret'] ?? '') . "' size='50'></td>";
echo "</tr>";

echo "<tr class='tab_bg_1'>";
echo "<td colspan='2' class='center'>";
echo "<input type='submit' name='update_config' value='" . _sx('button', 'Save') . "' class='btn btn-primary'>";
echo "</td>";
echo "</tr>";

echo "</table>";
echo "</form>";

if (!empty($config['tenant_id']) && !empty($config['client_id']) && !empty($config['client_secret'])) {
    echo "<div class='spaced'>";
    echo "<form method='post' action='" . Plugin::getWebDir('powerbireports') . "/front/test_token.php'>";
    echo Html::hidden('_glpi_csrf_token', ['value' => Session::getNewCSRFToken()]);
    echo "<input type='submit' name='test_access' value='" . __('Test Access Token Generation', 'powerbireports') . "' class='btn btn-secondary'>";
    echo "</form>";
    echo "</div>";
}

echo "<hr style='margin: 30px 0;'>";

echo "<h2>" . __('Power BI Reports', 'powerbireports') . "</h2>";

echo "<form method='post' action='" . $form_url . "' enctype='multipart/form-data'>";
echo Html::hidden('_glpi_csrf_token', ['value' => Session::getNewCSRFToken()]);
echo "<table class='tab_cadre_fixe'>";
echo "<tr><th colspan='2'>" . __('Add New Report', 'powerbireports') . "</th></tr>";

echo "<tr class='tab_bg_1'>";
echo "<td>" . __('Report Name', 'powerbireports') . " <span style='color:red'>*</span></td>";
echo "<td><input type='text' name='report_name' value='' size='50' required></td>";
echo "</tr>";

echo "<tr class='tab_bg_1'>";
echo "<td>" . __('Group ID (Workspace ID)', 'powerbireports') . " <span style='color:red'>*</span></td>";
echo "<td><input type='text' name='report_group_id' value='' size='50' required></td>";
echo "</tr>";

echo "<tr class='tab_bg_1'>";
echo "<td>" . __('Report ID', 'powerbireports') . " <span style='color:red'>*</span></td>";
echo "<td><input type='text' name='report_report_id' value='' size='50' required></td>";
echo "</tr>";

echo "<tr class='tab_bg_1'>";
echo "<td>" . __('Description', 'powerbireports') . "</td>";
echo "<td><textarea name='report_description' rows='3' cols='48'></textarea></td>";
echo "</tr>";

// Modo de atualização (API ou Tabela/Coluna)
echo "<tr class='tab_bg_1'>";
echo "<td>" . __('Modo de atualização', 'powerbireports') . " <span style='color:red'>*</span></td>";
echo "<td>";
echo "<select name='report_update_mode' id='report_update_mode' onchange='toggleAddTableColumnFields();loadAddTablesColumns();'>";
echo "<option value='api'>API Power BI</option>";
echo "<option value='table_column'>Tabela/Coluna do Relatório</option>";
echo "</select>";
echo "</td>";
echo "</tr>";

// Tabela (apenas quando modo = table_column)
echo "<tr class='tab_bg_1' id='tr_add_update_table' style='display:none;'>";
echo "<td>" . __('Tabela do Relatório', 'powerbireports') . "</td>";
echo "<td><input type='text' name='report_update_table' id='report_update_table' list='report_update_table_options' value='' size='40'><datalist id='report_update_table_options'></datalist><div id='add_schema_status' style='margin-top:5px;color:#666;font-size:12px;'></div></td>";
echo "</tr>";

// Coluna (apenas quando modo = table_column)
echo "<tr class='tab_bg_1' id='tr_add_update_column' style='display:none;'>";
echo "<td>" . __('Coluna de Data', 'powerbireports') . "</td>";
echo "<td><input type='text' name='report_update_column' id='report_update_column' list='report_update_column_options' value='' size='40'><datalist id='report_update_column_options'></datalist></td>";
echo "</tr>";

echo "<tr class='tab_bg_1'>";
echo "<td>" . __('Icon', 'powerbireports') . "</td>";
echo "<td>";
echo "<input type='file' name='report_icon' accept='image/*'>";
echo "<br><small style='color:#666;'>" . __('Optional. Recommended size: 64x64 pixels. Formats: PNG, JPG, GIF, SVG, WebP', 'powerbireports') . "</small>";
echo "</td>";
echo "</tr>";

echo "<tr class='tab_bg_1'>";
echo "<td colspan='2' class='center'>";
echo "<input type='submit' name='add_report' value='" . __('Add Report', 'powerbireports') . "' class='btn btn-success'>";
echo "</td>";
echo "</tr>";

echo "</table>";
echo "</form>";

// Script para exibir campos de tabela/coluna e carregar schema no formulário de inclusão
$schemaEndpoint = Plugin::getWebDir('powerbireports') . '/ajax/get_report_schema.php';
$addFormScript = <<<JS
<script>
var addTableColumnsCache = {};
function toggleAddTableColumnFields() {
    var mode = document.getElementById('report_update_mode').value;
    document.getElementById('tr_add_update_table').style.display = (mode === 'table_column') ? '' : 'none';
    document.getElementById('tr_add_update_column').style.display = (mode === 'table_column') ? '' : 'none';
}
function setAddSchemaStatus(msg, isError) {
    var el = document.getElementById('add_schema_status');
    if (!el) return;
    el.style.color = isError ? '#b94a48' : '#666';
    el.textContent = msg || '';
}
function clearAddListOptions(listEl) {
    while (listEl.firstChild) { listEl.removeChild(listEl.firstChild); }
}
function loadAddTablesColumns() {
    var mode = document.getElementById('report_update_mode').value;
    if (mode !== 'table_column') { setAddSchemaStatus('', false); return; }
    var groupId = document.querySelector("input[name='report_group_id']");
    var reportId = document.querySelector("input[name='report_report_id']");
    if (!groupId || !reportId || !groupId.value || !reportId.value) {
        setAddSchemaStatus('Informe Group ID e Report ID para listar tabelas.', true);
        return;
    }
    setAddSchemaStatus('Carregando tabelas...', false);
    fetch('{$schemaEndpoint}?group_id=' + encodeURIComponent(groupId.value) + '&report_id=' + encodeURIComponent(reportId.value), { credentials: 'same-origin' })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            var tableList = document.getElementById('report_update_table_options');
            var columnList = document.getElementById('report_update_column_options');
            clearAddListOptions(tableList);
            clearAddListOptions(columnList);
            addTableColumnsCache = {};
            if (!data.success) {
                setAddSchemaStatus((data.error || 'Erro ao carregar tabelas.') + ' Você pode digitar a tabela/coluna manualmente.', true);
                return;
            }
            data.tables.forEach(function(t) {
                var opt = document.createElement('option');
                opt.value = t.name;
                tableList.appendChild(opt);
                addTableColumnsCache[t.name] = (t.columns || []).map(function(c) { return c.name; });
            });
            setAddSchemaStatus('Tabelas carregadas. Você pode selecionar ou digitar.', false);
        })
        .catch(function(err) {
            setAddSchemaStatus('Erro ao carregar tabelas: ' + (err && err.message ? err.message : err) + '. Você pode digitar manualmente.', true);
        });
}
function loadAddColumnsForSelectedTable() {
    var mode = document.getElementById('report_update_mode').value;
    if (mode !== 'table_column') return;
    var tableInput = document.getElementById('report_update_table');
    var columnList = document.getElementById('report_update_column_options');
    clearAddListOptions(columnList);
    var tableName = tableInput.value;
    if (!tableName || !addTableColumnsCache[tableName]) { return; }
    addTableColumnsCache[tableName].forEach(function(col) {
        var opt = document.createElement('option');
        opt.value = col;
        columnList.appendChild(opt);
    });
}
document.addEventListener('DOMContentLoaded', function() {
    toggleAddTableColumnFields();
    var groupEl = document.querySelector("input[name='report_group_id']");
    var reportEl = document.querySelector("input[name='report_report_id']");
    if (groupEl) groupEl.addEventListener('change', loadAddTablesColumns);
    if (reportEl) reportEl.addEventListener('change', loadAddTablesColumns);
    var tableInput = document.getElementById('report_update_table');
    if (tableInput) tableInput.addEventListener('change', loadAddColumnsForSelectedTable);
});
</script>
JS;
echo $addFormScript;

echo "<br>";
echo "<table class='tab_cadre_fixe'>";
echo "<tr><th colspan='5'>" . __('Registered Reports', 'powerbireports') . "</th></tr>";
echo "<tr>";
echo "<th>" . __('Name', 'powerbireports') . "</th>";
echo "<th>" . __('Group ID', 'powerbireports') . "</th>";
echo "<th>" . __('Report ID', 'powerbireports') . "</th>";
echo "<th>" . __('Description', 'powerbireports') . "</th>";
echo "<th>" . __('Actions', 'powerbireports') . "</th>";
echo "</tr>";

if (empty($reports)) {
    echo "<tr class='tab_bg_1'><td colspan='5' class='center'>" . __('No reports registered', 'powerbireports') . "</td></tr>";
} else {
    foreach ($reports as $report) {
        echo "<tr class='tab_bg_1'>";
        echo "<td>" . htmlspecialchars($report['name']) . "</td>";
        echo "<td>" . htmlspecialchars($report['group_id']) . "</td>";
        echo "<td>" . htmlspecialchars($report['report_id']) . "</td>";
        echo "<td>" . htmlspecialchars($report['description'] ?? '') . "</td>";
        echo "<td class='center'>";

        echo "<a href='" . Plugin::getWebDir('powerbireports') . "/front/report.view.php?id=" . $report['id'] . "' class='btn btn-sm btn-primary' title='" . __('View', 'powerbireports') . "'>";
        echo "<i class='fas fa-eye'></i> " . __('View', 'powerbireports');
        echo "</a> ";

        echo "<a href='" . Plugin::getWebDir('powerbireports') . "/front/report.form.php?id=" . $report['id'] . "' class='btn btn-sm btn-warning' title='" . __('Edit', 'powerbireports') . "'>";
        echo "<i class='fas fa-edit'></i> " . __('Edit', 'powerbireports');
        echo "</a> ";

        echo "<form method='post' action='" . $form_url . "' style='display:inline;' onsubmit='return confirm(\"" . __('Are you sure you want to delete this report?', 'powerbireports') . "\");'>";
        echo Html::hidden('report_id', ['value' => $report['id']]);
        echo Html::hidden('_glpi_csrf_token', ['value' => Session::getNewCSRFToken()]);
        echo "<button type='submit' name='delete_report' class='btn btn-sm btn-danger' title='" . __('Delete', 'powerbireports') . "'>";
        echo "<i class='fas fa-trash'></i> " . __('Delete', 'powerbireports');
        echo "</button>";
        echo "</form>";

        echo "</td>";
        echo "</tr>";
    }
}

echo "</table>";
echo "</div>";

Html::footer();
