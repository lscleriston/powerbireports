<?php

// Inclui o arquivo de instalação
require_once __DIR__ . '/install/install.php';

/**
 * Função de instalação do plugin.
 */
function plugin_powerbireports_install() {
    return plugin_powerbireports_runInstall();
}

/**
 * Função de desinstalação do plugin.
 */
function plugin_powerbireports_uninstall() {
    return plugin_powerbireports_runUninstall();
}