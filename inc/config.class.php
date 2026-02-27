<?php
namespace GlpiPlugin\Powerbireports;

class Config
{
    const CONFIG_KEY = 'plugin_powerbireports';

    public static function getConfig() {
        $item = new ConfigItem();
        $iterator = $item->find([], 'id DESC');
        foreach ($iterator as $row) {
            return $row;
        }
        return [];
    }

    public static function saveConfig($input) {
        $config = self::getConfig();
        $fields = ['tenant_id', 'client_id', 'client_secret', 'group_id', 'report_id', 'last_token', 'token_expiry', 'last_embed_token', 'embed_token_expiry'];
        $data = [];
        foreach ($fields as $field) {
            $data[$field] = isset($input[$field]) ? $input[$field] : (isset($config[$field]) ? $config[$field] : null);
        }
        $item = new ConfigItem();
        if (isset($config['id'])) {
            return $item->update(['id' => $config['id']] + $data);
        } else {
            return $item->add($data);
        }
    }

    public static function generateAccessToken() {
        $config = self::getConfig();
        if (empty($config['tenant_id']) || empty($config['client_id']) || empty($config['client_secret'])) {
            return false;
        }
        if (!empty($config['token_expiry']) && !empty($config['last_token'])) {
            $expiryDate = new \DateTime($config['token_expiry']);
            $now = new \DateTime();
            if ($expiryDate > $now) {
                return $config['last_token'];
            }
        }

        $tenantId = $config['tenant_id'];
        $clientId = $config['client_id'];
        $clientSecret = $config['client_secret'];

        $tokenUrl = "https://login.microsoftonline.com/{$tenantId}/oauth2/v2.0/token";

        $tokenParams = [
            'grant_type' => 'client_credentials',
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'scope' => 'https://analysis.windows.net/powerbi/api/.default'
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $tokenUrl);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($tokenParams));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if (!empty($error)) {
            return false;
        }

        $tokenData = json_decode($response, true);

        if (isset($tokenData['access_token'])) {
            $expiresIn = $tokenData['expires_in'];
            $expiryDate = new \DateTime();
            $expiryDate->add(new \DateInterval("PT{$expiresIn}S"));

            self::saveConfig([
                'last_token' => $tokenData['access_token'],
                'token_expiry' => $expiryDate->format('Y-m-d H:i:s')
            ]);

            return $tokenData['access_token'];
        }

        return false;
    }

    public static function generateEmbedToken() {
        $config = self::getConfig();
        if (empty($config['group_id']) || empty($config['report_id'])) {
            return false;
        }
        if (!empty($config['embed_token_expiry']) && !empty($config['last_embed_token'])) {
            $expiryDate = new \DateTime($config['embed_token_expiry']);
            $now = new \DateTime();
            if ($expiryDate > $now) {
                return $config['last_embed_token'];
            }
        }

        $access_token = self::generateAccessToken();
        if (!$access_token) {
            return false;
        }

        $groupId = $config['group_id'];
        $reportId = $config['report_id'];

        $embedUrl = "https://api.powerbi.com/v1.0/myorg/groups/{$groupId}/reports/{$reportId}/GenerateToken";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $embedUrl);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['accessLevel' => 'view']));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $access_token
        ]);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if (!empty($error)) {
            return false;
        }

        $tokenData = json_decode($response, true);

        if (isset($tokenData['token'])) {
            $expiryDate = new \DateTime();
            $expiryDate->add(new \DateInterval('PT1H'));

            if (isset($tokenData['expiration'])) {
                $expiryDate = new \DateTime($tokenData['expiration']);
            }

            self::saveConfig([
                'last_embed_token' => $tokenData['token'],
                'embed_token_expiry' => $expiryDate->format('Y-m-d H:i:s')
            ]);

            return $tokenData['token'];
        }

        return false;
    }
}
