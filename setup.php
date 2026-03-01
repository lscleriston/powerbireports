<?php

use GlpiPlugin\Powerbireports\Profile;

function plugin_init_powerbireports() {
    global $PLUGIN_HOOKS;

    // Plugin compatível com CSRF
    $PLUGIN_HOOKS['csrf_compliant']['powerbireports'] = true;

    // Only load if the user has the right to use the plugin
    if (Session::haveRight('powerbireports', READ)) {
        // Adiciona o item de menu
        $PLUGIN_HOOKS["menu_toadd"]['powerbireports'] = [
            'tools' => 'PluginPowerbireportsMenu',
        ];
    }

    // Define a página de configuração do plugin
    $PLUGIN_HOOKS['config_page']['powerbireports'] = 'front/config.form.php';

    // Registra a classe Profile para gerenciar direitos
    Plugin::registerClass(Profile::class, [
        'addtabon' => ['Profile']
    ]);
}

function plugin_version_powerbireports() {
    return [
        'name'           => __('Power BI Reports', 'powerbireports'),
        'version'        => '2.2.0',
        'author'         => 'Cleriston Lopes',
        'license'        => 'GPLv3+',
        'homepage'       => 'https://exemplo.com',
        'minGlpiVersion' => '10.0',
        'requirements'   => [
            'glpi' => [
                'min' => '10.0',
            ]
        ]
    ];
}

function plugin_powerbireports_check_prerequisites() {
    if (version_compare(GLPI_VERSION, '10.0', '>=')) {
        return true;
    } else {
        echo "GLPI version NOT compatible. Requires GLPI >= 10.0";
        return false;
    }
}

function plugin_powerbireports_check_config($verbose = false) {
    if ($verbose) {
        echo 'Installed / not configured';
    }
    return true;
}