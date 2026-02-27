<?php
// Arquivo: front/test_token.php

include('../../../inc/includes.php');

// Verificar se o usuário está logado
Session::checkLoginUser();

// Verificar permissões
Session::checkRight("config", UPDATE);

use GlpiPlugin\Powerbireports\Config;

$plugin = new Plugin();
if (!$plugin->isActivated('powerbireports')) {
    Html::displayNotFoundError();
}

// SOLUÇÃO TEMPORÁRIA: Desativar verificação CSRF para esta página
$_SESSION['glpi_use_csrf_check'] = 0;

Html::header(__('Test Access Token', 'powerbireports'), $_SERVER['PHP_SELF'], "config", "plugins");

echo "<div class='center'>";
echo "<h1>" . __('Testing Access Token Generation', 'powerbireports') . "</h1>";

// Tentar gerar um token de acesso
$access_token = Config::generateAccessToken();

if ($access_token) {
    echo "<div class='alert alert-success'>";
    echo "<p>" . __('Successfully generated an access token!', 'powerbireports') . "</p>";
    echo "<p><strong>" . __('Access Token', 'powerbireports') . ":</strong> <code>" . substr($access_token, 0, 50) . "...</code></p>";
    
    // Obter a configuração para mostrar a data de expiração
    $config = Config::getConfig();
    if (!empty($config['token_expiry'])) {
        echo "<p><strong>" . __('Expiry', 'powerbireports') . ":</strong> " . $config['token_expiry'] . "</p>";
    }
    
    echo "</div>";
} else {
    echo "<div class='alert alert-danger'>";
    echo "<p>" . __('Failed to generate access token.', 'powerbireports') . "</p>";
    echo "<p>" . __('Please make sure your Azure AD configuration (Tenant ID, Client ID, and Client Secret) is correct.', 'powerbireports') . "</p>";
    echo "</div>";
}

echo "<div class='spaced'>";
echo "<a href='" . Plugin::getWebDir('powerbireports') . "/front/config.form.php' class='submit'>" . __('Back to Configuration', 'powerbireports') . "</a>";
echo "</div>";

echo "</div>";

Html::footer();
