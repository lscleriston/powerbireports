<?php


// Define plugin rights constants if not already defined
if (!defined('POWERBIREPORTS_READ')) {
    define('POWERBIREPORTS_READ', 1);
}


/**
 * Executa a instalação do plugin.
 * NOTA: As tabelas devem ser criadas manualmente via MySQL antes de instalar o plugin.
 * O GLPI 11 não permite queries diretas em plugins.
 */
function plugin_powerbireports_runInstall() {
    global $DB;
    echo "[PowerBIReports] Iniciando instalação do plugin...<br>"; flush();
    
    $tables = [
        'glpi_plugin_powerbireports_configs',
        'glpi_plugin_powerbireports_reports',
        'glpi_plugin_powerbireports_reports_users',
        'glpi_plugin_powerbireports_reports_groups',
        'glpi_plugin_powerbireports_reports_profiles'
    ];
    
    $all_exist = true;
    foreach ($tables as $table) {
        if ($DB->tableExists($table)) {
            echo "[PowerBIReports] Tabela $table já existe.<br>"; flush();
        } else {
            echo "[PowerBIReports][AVISO] Tabela $table não existe. Crie manualmente executando os arquivos SQL em install/mysql/.<br>"; flush();
            $all_exist = false;
        }
    }
    
    if (!$all_exist) {
        echo "[PowerBIReports][INFO] Execute os comandos SQL manualmente para criar as tabelas necessárias.<br>"; flush();
    }

    echo "[PowerBIReports] Direitos de perfil serão gerenciados pela classe Profile.<br>"; flush();
    echo "[PowerBIReports] Instalação concluída.<br>"; flush();
    return true;
}

/**
 * Executa a desinstalação do plugin.
 */
function plugin_powerbireports_runUninstall() {
    global $DB;
    echo "[PowerBIReports] Executando desinstalação...<br>"; flush();
    $tables = [
        'glpi_plugin_powerbireports_configs',
    ];
    foreach ($tables as $table) {
        if ($DB->tableExists($table)) {
            echo "[PowerBIReports][INFO] A estrutura e os dados da tabela $table permanecem no banco de dados. Remova manualmente se necessário."; flush();
        } else {
            echo "[PowerBIReports] Tabela $table não existe.<br>"; flush();
        }
    }
    echo "[PowerBIReports] Desinstalação concluída.<br>"; flush();
    return true;
}