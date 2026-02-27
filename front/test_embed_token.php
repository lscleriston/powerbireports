<?php
// Arquivo: front/test_embed_token.php

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

Html::header(__('Test Embed Token', 'powerbireports'), $_SERVER['PHP_SELF'], "config", "plugins");

echo "<div class='center'>";
echo "<h1>" . __('Testing Embed Token Generation', 'powerbireports') . "</h1>";

// Tentar gerar um token de incorporação
$embed_token = Config::generateEmbedToken();

if ($embed_token) {
    echo "<div class='alert alert-success'>";
    echo "<p>" . __('Successfully generated an embed token!', 'powerbireports') . "</p>";
    echo "<p><strong>" . __('Embed Token', 'powerbireports') . ":</strong> <code>" . substr($embed_token, 0, 50) . "...</code></p>";
    
    // Obter a configuração para mostrar a data de expiração
    $config = Config::getConfig();
    if (!empty($config['embed_token_expiry'])) {
        echo "<p><strong>" . __('Expiry', 'powerbireports') . ":</strong> " . $config['embed_token_expiry'] . "</p>";
    }
    
    echo "</div>";
} else {
    echo "<div class='alert alert-danger'>";
    echo "<p>" . __('Failed to generate embed token.', 'powerbireports') . "</p>";
    echo "<p>" . __('Please make sure your Power BI configuration (Group ID and Report ID) is correct.', 'powerbireports') . "</p>";
    echo "</div>";
}

echo "<div class='spaced'>";
echo "<a href='" . Plugin::getWebDir('powerbireports') . "/front/config.form.php' class='submit'>" . __('Back to Configuration', 'powerbireports') . "</a>";
echo "</div>";

echo "</div>";

Html::footer();