<?php
// Arquivo: ajax/get_token.php

include('../../../inc/includes.php');

// Verificar acesso
Session::checkRight("powerbireports", READ);

use GlpiPlugin\Powerbireports\Config;

header('Content-Type: application/json');

// Gerar ou obter o token
$token = Config::generateAccessToken();

if ($token) {
    echo json_encode([
        'success' => true,
        'token' => $token
    ]);
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Failed to generate or retrieve access token'
    ]);
}