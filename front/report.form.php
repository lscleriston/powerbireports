<?php
// Arquivo: front/report.form.php
// Edição de relatório Power BI

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
Session::checkRight("powerbireports", UPDATE);

use GlpiPlugin\Powerbireports\ReportItem;

$plugin = new Plugin();
if (!$plugin->isActivated('powerbireports')) {
    Html::displayNotFoundError();
}

// Verificar se o ID do relatório foi passado
$report_id = $_GET['id'] ?? $_POST['id'] ?? null;
if (!$report_id) {
    Html::displayNotFoundError();
}

// Desativar verificação CSRF temporariamente
$_SESSION['glpi_use_csrf_check'] = 0;

// URL correta para o formulário
$form_url = Plugin::getWebDir('powerbireports') . '/front/report.form.php';

// Processar atualização
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_report'])) {
    $reportData = [
        'name' => $_POST['name'] ?? '',
        'group_id' => $_POST['group_id'] ?? '',
        'report_id' => $_POST['report_id'] ?? '',
        'description' => $_POST['description'] ?? '',
        'update_mode' => $_POST['update_mode'] ?? 'api',
        'update_table' => $_POST['update_table'] ?? null,
        'update_column' => $_POST['update_column'] ?? null
    ];
    
    // Processar upload de ícone
    if (isset($_FILES['report_icon']) && $_FILES['report_icon']['error'] === UPLOAD_ERR_OK) {
        $icon_path = ReportItem::handleIconUpload($_FILES['report_icon']);
        if ($icon_path) {
            // Remover ícone antigo se existir
            $currentReport = new ReportItem();
            if ($currentReport->getFromDB($report_id)) {
                if (!empty($currentReport->fields['icon_path'])) {
                    $old_path = GLPI_ROOT . '/' . $currentReport->fields['icon_path'];
                    if (file_exists($old_path)) {
                        @unlink($old_path);
                    }
                }
            }
            $reportData['icon_path'] = $icon_path;
        }
    }
    
    // Verificar se deve remover o ícone
    if (isset($_POST['remove_icon']) && $_POST['remove_icon'] == '1') {
        // Buscar relatório atual para remover arquivo
        $currentReport = new ReportItem();
        if ($currentReport->getFromDB($report_id)) {
            if (!empty($currentReport->fields['icon_path'])) {
                $old_path = GLPI_ROOT . '/' . $currentReport->fields['icon_path'];
                if (file_exists($old_path)) {
                    @unlink($old_path);
                }
            }
        }
        $reportData['icon_path'] = null;
    }
    
    try {
        if (ReportItem::updateReport($report_id, $reportData)) {
            // Atualizar permissões de usuários
            $selected_users = $_POST['_users_id'] ?? [];
            if (!is_array($selected_users)) {
                $selected_users = [];
            }
            ReportItem::syncUsers($report_id, $selected_users);
            
            // Atualizar permissões de grupos
            $selected_groups = $_POST['_groups_id'] ?? [];
            if (!is_array($selected_groups)) {
                $selected_groups = [];
            }
            ReportItem::syncGroups($report_id, $selected_groups);
            
            // Atualizar permissões de perfis
            $selected_profiles = $_POST['_profiles_id'] ?? [];
            if (!is_array($selected_profiles)) {
                $selected_profiles = [];
            }
            ReportItem::syncProfiles($report_id, $selected_profiles);
            
            Session::addMessageAfterRedirect(__('Report updated successfully', 'powerbireports'), true, INFO);
        } else {
            Session::addMessageAfterRedirect(__('Error updating report', 'powerbireports'), true, ERROR);
        }
    } catch (\Throwable $e) {
        Session::addMessageAfterRedirect(__('Error: ', 'powerbireports') . $e->getMessage(), true, ERROR);
    }
    Html::redirect(Plugin::getWebDir('powerbireports') . '/front/config.form.php');
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

// Garantir defaults para novos campos
$report['update_mode'] = $report['update_mode'] ?? 'api';
$report['update_table'] = $report['update_table'] ?? '';
$report['update_column'] = $report['update_column'] ?? '';

Html::header(__('Edit Report', 'powerbireports'), $_SERVER['PHP_SELF'], "config", "plugins");

$csrf_token = Session::getNewCSRFToken();

// Exibir mensagens
Html::displayMessageAfterRedirect();

echo "<div class='center'>";

// Link para voltar
echo "<div style='margin-bottom: 20px;'>";
echo "<a href='" . Plugin::getWebDir('powerbireports') . "/front/config.form.php' class='btn btn-secondary'>";
echo "<i class='fas fa-arrow-left'></i> " . __('Back to Reports List', 'powerbireports');
echo "</a>";
echo "</div>";

// Formulário de edição
echo "<form method='post' action='" . $form_url . "' enctype='multipart/form-data'>";
echo "<table class='tab_cadre_fixe'>";
echo "<tr><th colspan='2'>" . __('Edit Report', 'powerbireports') . " - " . htmlspecialchars($report['name']) . "</th></tr>";

echo "<tr class='tab_bg_1'>";
echo "<td>" . __('Report Name', 'powerbireports') . " <span style='color:red'>*</span></td>";
echo "<td><input type='text' name='name' value='" . htmlspecialchars($report['name']) . "' size='50' required></td>";
echo "</tr>";

echo "<tr class='tab_bg_1'>";
echo "<td>" . __('Group ID (Workspace ID)', 'powerbireports') . " <span style='color:red'>*</span></td>";
echo "<td><input type='text' name='group_id' value='" . htmlspecialchars($report['group_id']) . "' size='50' required></td>";
echo "</tr>";

echo "<tr class='tab_bg_1'>";
echo "<td>" . __('Report ID', 'powerbireports') . " <span style='color:red'>*</span></td>";
echo "<td><input type='text' name='report_id' value='" . htmlspecialchars($report['report_id']) . "' size='50' required></td>";
echo "</tr>";

echo "<tr class='tab_bg_1'>";
echo "<td>" . __('Description', 'powerbireports') . "</td>";
echo "<td><textarea name='description' rows='3' cols='48'>" . htmlspecialchars($report['description'] ?? '') . "</textarea></td>";
echo "</tr>";

// --- PERMISSÕES: Usuários e Grupos ---
echo "<tr class='tab_bg_2'>";
echo "<td colspan='2' style='text-align:left; font-weight:bold; background:#e8e8e8; padding: 8px;'>";
echo "<i class='fas fa-lock'></i> " . __('Permissões de Visualização', 'powerbireports');
echo "<br><small style='font-weight:normal; color:#666;'>" . __('Se nenhum usuário ou grupo for selecionado, todos poderão visualizar o relatório', 'powerbireports') . "</small>";
echo "</td>";
echo "</tr>";

// Buscar usuários e grupos já associados
try {
    $current_users = ReportItem::getReportUsers($report_id);
    if (!is_array($current_users)) {
        $current_users = [];
    }
} catch (\Throwable $e) {
    error_log('Erro ao obter usuários do relatório: ' . $e->getMessage());
    $current_users = [];
}

try {
    $current_groups = ReportItem::getReportGroups($report_id);
    if (!is_array($current_groups)) {
        $current_groups = [];
    }
} catch (\Throwable $e) {
    error_log('Erro ao obter grupos do relatório: ' . $e->getMessage());
    $current_groups = [];
}

// Campo de seleção de usuários
echo "<tr class='tab_bg_1'>";
echo "<td>" . __('Usuários Autorizados', 'powerbireports') . "</td>";
echo "<td>";

try {
    $users = ReportItem::getAllGlpiUsers();
    
    echo "<select name='_users_id[]' multiple style='width:80%; height:150px;'>";
    if (empty($users)) {
        echo "<option value=''>-- Nenhum usuário cadastrado --</option>";
    } else {
        foreach ($users as $user_id => $user_name) {
            $selected = in_array($user_id, $current_users) ? ' selected' : '';
            echo "<option value='" . htmlspecialchars($user_id) . "'" . $selected . ">" . htmlspecialchars($user_name) . "</option>";
        }
    }
    echo "</select>";
} catch (\Throwable $e) {
    error_log('Erro ao carregar usuários: ' . $e->getMessage());
    echo "<select name='_users_id[]' multiple style='width:80%; height:150px;'>";
    echo "<option value=''>-- Erro ao carregar usuários --</option>";
    echo "</select>";
}

echo "<br><small style='color:#666;'>" . __('Usuários que podem visualizar este relatório', 'powerbireports') . "</small>";
echo "</td>";
echo "</tr>";

// Campo de seleção de grupos
echo "<tr class='tab_bg_1'>";
echo "<td>" . __('Grupos Autorizados', 'powerbireports') . "</td>";
echo "<td>";

try {
    $groups = ReportItem::getAllGlpiGroups();
    
    echo "<select name='_groups_id[]' multiple style='width:80%; height:150px;'>";
    if (empty($groups)) {
        echo "<option value=''>-- Nenhum grupo cadastrado --</option>";
    } else {
        foreach ($groups as $group_id => $group_name) {
            $selected = in_array($group_id, $current_groups) ? ' selected' : '';
            echo "<option value='" . htmlspecialchars($group_id) . "'" . $selected . ">" . htmlspecialchars($group_name) . "</option>";
        }
    }
    echo "</select>";
} catch (\Throwable $e) {
    error_log('Erro ao carregar grupos: ' . $e->getMessage());
    echo "<select name='_groups_id[]' multiple style='width:80%; height:150px;'>";
    echo "<option value=''>-- Erro ao carregar grupos --</option>";
    echo "</select>";
}

echo "<br><small style='color:#666;'>" . __('Grupos que podem visualizar este relatório', 'powerbireports') . "</small>";
echo "</td>";
echo "</tr>";

// Campo de seleção de perfis
echo "<tr class='tab_bg_1'>";
echo "<td>" . __('Perfis Autorizados', 'powerbireports') . "</td>";
echo "<td>";

try {
    $current_profiles = ReportItem::getReportProfiles($report_id);
    $profiles = ReportItem::getAllGlpiProfiles();
    
    echo "<select name='_profiles_id[]' multiple style='width:80%; height:150px;'>";
    if (empty($profiles)) {
        echo "<option value=''>-- Nenhum perfil cadastrado --</option>";
    } else {
        foreach ($profiles as $profile_id => $profile_name) {
            $selected = in_array($profile_id, $current_profiles) ? ' selected' : '';
            echo "<option value='" . htmlspecialchars($profile_id) . "'" . $selected . ">" . htmlspecialchars($profile_name) . "</option>";
        }
    }
    echo "</select>";
} catch (\Throwable $e) {
    error_log('Erro ao carregar perfis: ' . $e->getMessage());
    echo "<select name='_profiles_id[]' multiple style='width:80%; height:150px;'>";
    echo "<option value=''>-- Erro ao carregar perfis --</option>";
    echo "</select>";
}

echo "<br><small style='color:#666;'>" . __('Perfis que podem visualizar este relatório', 'powerbireports') . "</small>";
echo "</td>";
echo "</tr>";

// --- CAMPO: Modo de atualização ---
echo "<tr class='tab_bg_1'>";
echo "<td>" . __('Modo de atualização', 'powerbireports') . " <span style='color:red'>*</span></td>";
echo "<td>";
echo "<select name='update_mode' id='update_mode' onchange='toggleTableColumnFields();loadTablesColumns();'>";
echo "<option value='api'" . ($report['update_mode'] == 'api' ? ' selected' : '') . ">API Power BI</option>";
echo "<option value='table_column'" . ($report['update_mode'] == 'table_column' ? ' selected' : '') . ">Tabela/Coluna do Relatório</option>";
echo "</select>";
echo "</td>";
echo "</tr>";

// --- CAMPOS: Tabela e Coluna (aparecem só se modo for table_column) ---
echo "<tr class='tab_bg_1' id='tr_update_table' style='display:none;'>";
echo "<td>" . __('Tabela do Relatório', 'powerbireports') . "</td>";
echo "<td><input type='text' name='update_table' id='update_table' list='update_table_options' value='" . htmlspecialchars($report['update_table'] ?? '') . "' size='40'><datalist id='update_table_options'></datalist><div id='schema_status' style='margin-top:5px;color:#666;font-size:12px;'></div></td>";
echo "</tr>";

echo "<tr class='tab_bg_1' id='tr_update_column' style='display:none;'>";
echo "<td>" . __('Coluna de Data', 'powerbireports') . "</td>";
echo "<td><input type='text' name='update_column' id='update_column' list='update_column_options' value='" . htmlspecialchars($report['update_column'] ?? '') . "' size='40'><datalist id='update_column_options'></datalist></td>";
echo "</tr>";

// Campo de ícone
echo "<tr class='tab_bg_1'>";
echo "<td>" . __('Icon', 'powerbireports') . "</td>";
echo "<td>";

// Mostrar ícone atual se existir
if (!empty($report['icon_path']) && file_exists(GLPI_ROOT . '/' . $report['icon_path'])) {
    $icon_url = $CFG_GLPI['root_doc'] . '/' . $report['icon_path'];
    echo "<div style='margin-bottom: 10px;'>";
    echo "<img src='" . htmlspecialchars($icon_url) . "' alt='" . __('Current icon', 'powerbireports') . "' style='width: 64px; height: 64px; object-fit: contain; border: 1px solid #ddd; padding: 5px; border-radius: 4px;'>";
    echo "<br><label style='margin-top: 5px; display: inline-block;'><input type='checkbox' name='remove_icon' value='1'> " . __('Remove current icon', 'powerbireports') . "</label>";
    echo "</div>";
}

echo "<input type='file' name='report_icon' accept='image/*'>";
echo "<br><small style='color:#666;'>" . __('Optional. Recommended size: 64x64 pixels. Formats: PNG, JPG, GIF, SVG, WebP', 'powerbireports') . "</small>";
echo "</td>";
echo "</tr>";

echo "<tr class='tab_bg_1'>";
echo "<td colspan='2' class='center'>";
echo Html::hidden('id', ['value' => $report_id]);
echo Html::hidden('_glpi_csrf_token', ['value' => $csrf_token]);
echo "<input type='submit' name='update_report' value='" . _sx('button', 'Save') . "' class='btn btn-primary'>";
echo " ";
echo "<a href='" . Plugin::getWebDir('powerbireports') . "/front/config.form.php' class='btn btn-secondary'>" . _sx('button', 'Cancel') . "</a>";
echo "</td>";
echo "</tr>";

// Script para exibir/esconder campos e carregar tabelas/colunas via API
$schemaEndpoint = Plugin::getWebDir('powerbireports') . '/ajax/get_report_schema.php';
$preselectTable = json_encode($report['update_table']);
$preselectColumn = json_encode($report['update_column']);
$script = <<<JS
<script>
var tableColumnsCache = {};
function toggleTableColumnFields() {
    var mode = document.getElementById('update_mode').value;
    document.getElementById('tr_update_table').style.display = (mode === 'table_column') ? '' : 'none';
    document.getElementById('tr_update_column').style.display = (mode === 'table_column') ? '' : 'none';
}
function setSchemaStatus(msg, isError) {
    var el = document.getElementById('schema_status');
    if (!el) return;
    el.style.color = isError ? '#b94a48' : '#666';
    el.textContent = msg || '';
}
function clearDatalistOptions(listEl) {
    while (listEl.firstChild) { listEl.removeChild(listEl.firstChild); }
}
function loadTablesColumns() {
    var mode = document.getElementById('update_mode').value;
    if (mode !== 'table_column') { setSchemaStatus('', false); return; }
    var groupId = document.querySelector("input[name='group_id']");
    var reportId = document.querySelector("input[name='report_id']");
    if (!groupId || !reportId || !groupId.value || !reportId.value) {
        setSchemaStatus('Informe Group ID e Report ID para listar tabelas.', true);
        return;
    }
    setSchemaStatus('Carregando tabelas...', false);
    fetch('{$schemaEndpoint}?group_id=' + encodeURIComponent(groupId.value) + '&report_id=' + encodeURIComponent(reportId.value), { credentials: 'same-origin' })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            var tableList = document.getElementById('update_table_options');
            var columnList = document.getElementById('update_column_options');
            clearDatalistOptions(tableList);
            clearDatalistOptions(columnList);
            tableColumnsCache = {};
            if (!data.success) {
                setSchemaStatus((data.error || 'Erro ao carregar tabelas.') + ' Você pode digitar a tabela/coluna manualmente.', true);
                return;
            }
            data.tables.forEach(function(t) {
                var opt = document.createElement('option');
                opt.value = t.name;
                tableList.appendChild(opt);
                tableColumnsCache[t.name] = (t.columns || []).map(function(c) { return c.name; });
            });
            setSchemaStatus('Tabelas carregadas. Você pode selecionar ou digitar.', false);
            var tableInput = document.getElementById('update_table');
            if ($preselectTable) { tableInput.value = $preselectTable; }
            loadColumnsForSelectedTable();
        })
        .catch(function(err) {
            setSchemaStatus('Erro ao carregar tabelas: ' + (err && err.message ? err.message : err) + '. Você pode digitar manualmente.', true);
        });
}
function loadColumnsForSelectedTable() {
    var mode = document.getElementById('update_mode').value;
    if (mode !== 'table_column') return;
    var tableInput = document.getElementById('update_table');
    var columnList = document.getElementById('update_column_options');
    clearDatalistOptions(columnList);
    var tableName = tableInput.value;
    if (!tableName || !tableColumnsCache[tableName]) { return; }
    tableColumnsCache[tableName].forEach(function(col) {
        var opt = document.createElement('option');
        opt.value = col;
        columnList.appendChild(opt);
    });
    var columnInput = document.getElementById('update_column');
    if ($preselectColumn) { columnInput.value = $preselectColumn; }
}
document.addEventListener('DOMContentLoaded', function() {
    toggleTableColumnFields();
    loadTablesColumns();
    var groupEl = document.querySelector("input[name='group_id']");
    var reportEl = document.querySelector("input[name='report_id']");
    if (groupEl) groupEl.addEventListener('change', loadTablesColumns);
    if (reportEl) reportEl.addEventListener('change', loadTablesColumns);
    var tableInput = document.getElementById('update_table');
    if (tableInput) tableInput.addEventListener('change', loadColumnsForSelectedTable);
});
</script>
JS;
echo $script;

echo "</table>";
echo "</form>";

// Informações adicionais
echo "<br>";
echo "<table class='tab_cadre_fixe'>";
echo "<tr><th colspan='2'>" . __('Report Information', 'powerbireports') . "</th></tr>";
echo "<tr class='tab_bg_1'><td>" . __('Created at', 'powerbireports') . "</td><td>" . ($report['date_creation'] ?? '-') . "</td></tr>";
echo "<tr class='tab_bg_1'><td>" . __('Last modification', 'powerbireports') . "</td><td>" . ($report['date_mod'] ?? '-') . "</td></tr>";
echo "</table>";

// Botão para testar o embed token deste relatório
echo "<br>";
echo "<form method='post' action='" . Plugin::getWebDir('powerbireports') . "/front/test_embed_token.php'>";
echo Html::hidden('group_id', ['value' => $report['group_id']]);
echo Html::hidden('report_id', ['value' => $report['report_id']]);
echo Html::hidden('_glpi_csrf_token', ['value' => $csrf_token]);
echo "<input type='submit' name='test_embed' value='" . __('Test Embed Token for this Report', 'powerbireports') . "' class='btn btn-info'>";
echo "</form>";

echo "</div>";

Html::footer();
