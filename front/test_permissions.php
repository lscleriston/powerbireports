<?php
if (!defined('GLPI_ROOT')) {
    $candidate3 = realpath(__DIR__ . '/../../..');
    if ($candidate3 && file_exists($candidate3 . '/inc/includes.php')) {
        define('GLPI_ROOT', $candidate3);
    } else {
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

use GlpiPlugin\Powerbireports\ReportItem;

$report_id = $_GET['id'] ?? 1;

echo "Testando report_id: " . $report_id . "<br>";

try {
    echo "1. Tentando chamar getReportUsers...<br>";
    $users = ReportItem::getReportUsers($report_id);
    echo "Resultado: " . print_r($users, true) . "<br>";
} catch (\Throwable $e) {
    echo "ERRO em getReportUsers: " . $e->getMessage() . " (File: " . $e->getFile() . ", Line: " . $e->getLine() . ")<br>";
}

try {
    echo "2. Tentando chamar getReportGroups...<br>";
    $groups = ReportItem::getReportGroups($report_id);
    echo "Resultado: " . print_r($groups, true) . "<br>";
} catch (\Throwable $e) {
    echo "ERRO em getReportGroups: " . $e->getMessage() . " (File: " . $e->getFile() . ", Line: " . $e->getLine() . ")<br>";
}
