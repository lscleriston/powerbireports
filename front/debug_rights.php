<?php
// Arquivo de debug para verificar permissões
include('../../../inc/includes.php');

Session::checkLoginUser();

echo "<h2>Debug de Permissões - Power BI Reports</h2>";
echo "<pre>";

echo "Usuário logado: " . ($_SESSION['glpiname'] ?? 'N/A') . "\n";
echo "ID do usuário: " . ($_SESSION['glpiID'] ?? 'N/A') . "\n";
echo "Perfil ativo: " . ($_SESSION['glpiactiveprofile']['name'] ?? 'N/A') . "\n";
echo "ID do perfil: " . ($_SESSION['glpiactiveprofile']['id'] ?? 'N/A') . "\n";

echo "\n--- Constantes GLPI ---\n";
echo "READ = " . (defined('READ') ? READ : 'não definido') . "\n";
echo "UPDATE = " . (defined('UPDATE') ? UPDATE : 'não definido') . "\n";
echo "CREATE = " . (defined('CREATE') ? CREATE : 'não definido') . "\n";
echo "DELETE = " . (defined('DELETE') ? DELETE : 'não definido') . "\n";
echo "PURGE = " . (defined('PURGE') ? PURGE : 'não definido') . "\n";

echo "\n--- Direitos do perfil ativo ---\n";
if (isset($_SESSION['glpiactiveprofile'])) {
    $profile = $_SESSION['glpiactiveprofile'];
    
    // Verificar se existe o direito powerbireports
    if (isset($profile['powerbireports'])) {
        echo "powerbireports = " . $profile['powerbireports'] . "\n";
        echo "  - Tem READ? " . (($profile['powerbireports'] & READ) ? 'SIM' : 'NÃO') . "\n";
        echo "  - Tem UPDATE? " . (($profile['powerbireports'] & UPDATE) ? 'SIM' : 'NÃO') . "\n";
    } else {
        echo "powerbireports = NÃO DEFINIDO no perfil ativo!\n";
    }
}

echo "\n--- Verificação com Session::haveRight ---\n";
echo "haveRight('powerbireports', READ): " . (Session::haveRight('powerbireports', READ) ? 'SIM' : 'NÃO') . "\n";
echo "haveRight('powerbireports', UPDATE): " . (Session::haveRight('powerbireports', UPDATE) ? 'SIM' : 'NÃO') . "\n";

echo "\n--- Todos os direitos do perfil ativo ---\n";
if (isset($_SESSION['glpiactiveprofile'])) {
    foreach ($_SESSION['glpiactiveprofile'] as $key => $value) {
        if (is_numeric($value)) {
            echo "$key = $value\n";
        }
    }
}

echo "</pre>";
