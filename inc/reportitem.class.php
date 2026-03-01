<?php
namespace GlpiPlugin\Powerbireports;

class ReportItem extends \CommonDBTM {
    public static $rightname = 'powerbireports';
    public $dohistory = false;

    public static function getTable($classname = null) {
        return 'glpi_plugin_powerbireports_reports';
    }

    public static function getAllReports() {
        $item = new self();
        return $item->find([], 'name ASC');
    }

    public static function getReportById($id) {
        $item = new self();
        if ($item->getFromDB($id)) {
            return $item->fields;
        }
        return false;
    }

    public static function addReport($data) {
        $item = new self();
        $input = [
            'name' => $data['name'] ?? '',
            'group_id' => $data['group_id'] ?? '',
            'report_id' => $data['report_id'] ?? '',
            'description' => $data['description'] ?? '',
            'icon_path' => $data['icon_path'] ?? null,
            'update_mode' => $data['update_mode'] ?? 'api',
            'update_table' => $data['update_table'] ?? null,
            'update_column' => $data['update_column'] ?? null
        ];
        return $item->add($input);
    }

    public static function updateReport($id, $data) {
        $item = new self();
        $input = [
            'id' => $id,
            'name' => $data['name'] ?? '',
            'group_id' => $data['group_id'] ?? '',
            'report_id' => $data['report_id'] ?? '',
            'description' => $data['description'] ?? '',
            'update_mode' => $data['update_mode'] ?? 'api',
            'update_table' => $data['update_table'] ?? null,
            'update_column' => $data['update_column'] ?? null
        ];
        // Só atualiza icon_path se foi enviado
        if (isset($data['icon_path'])) {
            $input['icon_path'] = $data['icon_path'];
        }
        return $item->update($input);
    }

    public static function deleteReport($id) {
        // Remover ícone se existir
        $item = new self();
        if ($item->getFromDB($id)) {
            $icon_path = $item->fields['icon_path'] ?? null;
            if ($icon_path && file_exists(GLPI_ROOT . '/' . $icon_path)) {
                @unlink(GLPI_ROOT . '/' . $icon_path);
            }
        }
        return $item->delete(['id' => $id]);
    }

    public static function handleIconUpload($file) {
        if (empty($file['name']) || $file['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        $allowed_types = ['image/png', 'image/jpeg', 'image/gif', 'image/svg+xml', 'image/webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime, $allowed_types)) {
            return null;
        }

        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid('icon_') . '.' . $ext;
        $upload_dir = 'plugins/powerbireports/pics/icons/';
        $full_path = GLPI_ROOT . '/' . $upload_dir;

        if (!is_dir($full_path)) {
            mkdir($full_path, 0755, true);
        }

        $destination = $full_path . $filename;
        if (move_uploaded_file($file['tmp_name'], $destination)) {
            return $upload_dir . $filename;
        }

        return null;
    }

    /**
     * Adiciona usuários a um relatório
     */
    public static function addUsers($report_id, $users_ids) {
        global $DB;
        
        if (empty($users_ids) || !is_array($users_ids)) {
            return false;
        }

        $success = true;
        foreach ($users_ids as $user_id) {
            try {
                $DB->insert('glpi_plugin_powerbireports_reports_users', [
                    'plugin_powerbireports_reports_id' => $report_id,
                    'users_id' => $user_id
                ]);
            } catch (\Exception $e) {
                // Ignora se já existe (unique constraint)
                if (strpos($e->getMessage(), 'Duplicate entry') === false) {
                    $success = false;
                }
            }
        }
        
        return $success;
    }

    /**
     * Remove usuários de um relatório
     */
    public static function removeUsers($report_id, $users_ids) {
        global $DB;
        
        if (empty($users_ids) || !is_array($users_ids)) {
            return false;
        }

        return $DB->delete('glpi_plugin_powerbireports_reports_users', [
            'plugin_powerbireports_reports_id' => $report_id,
            'users_id' => $users_ids
        ]);
    }

    /**
     * Obtém todos os usuários de um relatório
     */
    public static function getReportUsers($report_id) {
        global $DB;
        
        $iterator = $DB->request([
            'SELECT' => ['users_id'],
            'FROM' => 'glpi_plugin_powerbireports_reports_users',
            'WHERE' => ['plugin_powerbireports_reports_id' => $report_id]
        ]);
        
        $users = [];
        foreach ($iterator as $row) {
            $users[] = $row['users_id'];
        }
        
        return $users;
    }

    /**
     * Sincroniza usuários de um relatório (remove os antigos e adiciona os novos)
     */
    public static function syncUsers($report_id, $users_ids) {
        global $DB;
        
        // Remove todos os usuários atuais
        $DB->delete('glpi_plugin_powerbireports_reports_users', [
            'plugin_powerbireports_reports_id' => $report_id
        ]);
        
        // Adiciona os novos usuários
        if (!empty($users_ids) && is_array($users_ids)) {
            return self::addUsers($report_id, $users_ids);
        }
        
        return true;
    }

    /**
     * Adiciona grupos a um relatório
     */
    public static function addGroups($report_id, $groups_ids) {
        global $DB;
        
        if (empty($groups_ids) || !is_array($groups_ids)) {
            return false;
        }

        $success = true;
        foreach ($groups_ids as $group_id) {
            try {
                $DB->insert('glpi_plugin_powerbireports_reports_groups', [
                    'plugin_powerbireports_reports_id' => $report_id,
                    'groups_id' => $group_id
                ]);
            } catch (\Exception $e) {
                // Ignora se já existe (unique constraint)
                if (strpos($e->getMessage(), 'Duplicate entry') === false) {
                    $success = false;
                }
            }
        }
        
        return $success;
    }

    /**
     * Remove grupos de um relatório
     */
    public static function removeGroups($report_id, $groups_ids) {
        global $DB;
        
        if (empty($groups_ids) || !is_array($groups_ids)) {
            return false;
        }

        return $DB->delete('glpi_plugin_powerbireports_reports_groups', [
            'plugin_powerbireports_reports_id' => $report_id,
            'groups_id' => $groups_ids
        ]);
    }

    /**
     * Obtém todos os grupos de um relatório
     */
    public static function getReportGroups($report_id) {
        global $DB;
        
        $iterator = $DB->request([
            'SELECT' => ['groups_id'],
            'FROM' => 'glpi_plugin_powerbireports_reports_groups',
            'WHERE' => ['plugin_powerbireports_reports_id' => $report_id]
        ]);
        
        $groups = [];
        foreach ($iterator as $row) {
            $groups[] = $row['groups_id'];
        }
        
        return $groups;
    }

    /**
     * Sincroniza grupos de um relatório (remove os antigos e adiciona os novos)
     */
    public static function syncGroups($report_id, $groups_ids) {
        global $DB;
        
        // Remove todos os grupos atuais
        $DB->delete('glpi_plugin_powerbireports_reports_groups', [
            'plugin_powerbireports_reports_id' => $report_id
        ]);
        
        // Adiciona os novos grupos
        if (!empty($groups_ids) && is_array($groups_ids)) {
            return self::addGroups($report_id, $groups_ids);
        }
        
        return true;
    }

    /**
     * Verifica se um usuário tem permissão para ver um relatório
     */
    public static function canUserViewReport($user_id, $report_id) {
        global $DB;
        
        // Se não há restrições (nenhum usuário, grupo ou perfil definido), todos podem ver
        $has_users = $DB->request([
            'COUNT' => 'cpt',
            'FROM' => 'glpi_plugin_powerbireports_reports_users',
            'WHERE' => ['plugin_powerbireports_reports_id' => $report_id]
        ])->current()['cpt'] > 0;
        
        $has_groups = $DB->request([
            'COUNT' => 'cpt',
            'FROM' => 'glpi_plugin_powerbireports_reports_groups',
            'WHERE' => ['plugin_powerbireports_reports_id' => $report_id]
        ])->current()['cpt'] > 0;
        
        $has_profiles = $DB->request([
            'COUNT' => 'cpt',
            'FROM' => 'glpi_plugin_powerbireports_reports_profiles',
            'WHERE' => ['plugin_powerbireports_reports_id' => $report_id]
        ])->current()['cpt'] > 0;
        
        if (!$has_users && !$has_groups && !$has_profiles) {
            return true; // Sem restrições, todos podem ver
        }
        
        // Verifica se o usuário está diretamente autorizado
        $user_allowed = $DB->request([
            'COUNT' => 'cpt',
            'FROM' => 'glpi_plugin_powerbireports_reports_users',
            'WHERE' => [
                'plugin_powerbireports_reports_id' => $report_id,
                'users_id' => $user_id
            ]
        ])->current()['cpt'] > 0;
        
        if ($user_allowed) {
            return true;
        }
        
        // Verifica se o usuário pertence a algum grupo autorizado
        $user_groups = $DB->request([
            'SELECT' => ['groups_id'],
            'FROM' => 'glpi_groups_users',
            'WHERE' => ['users_id' => $user_id]
        ]);
        
        $groups_ids = [];
        foreach ($user_groups as $group) {
            $groups_ids[] = $group['groups_id'];
        }
        
        if (!empty($groups_ids)) {
            $group_allowed = $DB->request([
                'COUNT' => 'cpt',
                'FROM' => 'glpi_plugin_powerbireports_reports_groups',
                'WHERE' => [
                    'plugin_powerbireports_reports_id' => $report_id,
                    'groups_id' => $groups_ids
                ]
            ])->current()['cpt'] > 0;
            
            if ($group_allowed) {
                return true;
            }
        }
        
        // Verifica se o usuário possui algum perfil autorizado
        $user_profiles = $DB->request([
            'SELECT' => ['profiles_id'],
            'FROM' => 'glpi_profiles_users',
            'WHERE' => ['users_id' => $user_id]
        ]);
        
        $profiles_ids = [];
        foreach ($user_profiles as $profile) {
            $profiles_ids[] = $profile['profiles_id'];
        }
        
        if (!empty($profiles_ids)) {
            $profile_allowed = $DB->request([
                'COUNT' => 'cpt',
                'FROM' => 'glpi_plugin_powerbireports_reports_profiles',
                'WHERE' => [
                    'plugin_powerbireports_reports_id' => $report_id,
                    'profiles_id' => $profiles_ids
                ]
            ])->current()['cpt'] > 0;
            
            if ($profile_allowed) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Obtém todos os relatórios que um usuário pode visualizar
     */
    public static function getReportsForUser($user_id) {
        global $DB;
        
        // Primeiro, pega todos os relatórios
        $all_reports = self::getAllReports();
        
        // Filtra apenas os que o usuário pode ver
        $visible_reports = [];
        foreach ($all_reports as $report) {
            if (self::canUserViewReport($user_id, $report['id'])) {
                $visible_reports[] = $report;
            }
        }
        
        return $visible_reports;
    }

    /**
     * Obtém lista de todos os usuários ativos do GLPI
     */
    public static function getAllGlpiUsers() {
        global $DB;
        
        $users = [];
        
        try {
            $iterator = $DB->request([
                'SELECT' => ['id', 'name'],
                'FROM' => 'glpi_users',
                'WHERE' => ['is_active' => 1],
                'ORDER' => ['name']
            ]);
            
            foreach ($iterator as $row) {
                $users[$row['id']] = $row['name'];
            }
        } catch (\Throwable $e) {
            error_log('Erro ao buscar usuários GLPI: ' . $e->getMessage());
        }
        
        return $users;
    }

    /**
     * Obtém lista de todos os grupos do GLPI
     */
    public static function getAllGlpiGroups() {
        global $DB;
        
        $groups = [];
        
        try {
            $iterator = $DB->request([
                'SELECT' => ['id', 'name'],
                'FROM' => 'glpi_groups',
                'ORDER' => ['name']
            ]);
            
            foreach ($iterator as $row) {
                $groups[$row['id']] = $row['name'];
            }
        } catch (\Throwable $e) {
            error_log('Erro ao buscar grupos GLPI: ' . $e->getMessage());
        }
        
        return $groups;
    }

    /**
     * Obtém lista de todos os perfis do GLPI
     */
    public static function getAllGlpiProfiles() {
        global $DB;
        
        $profiles = [];
        
        try {
            $iterator = $DB->request([
                'SELECT' => ['id', 'name'],
                'FROM' => 'glpi_profiles',
                'ORDER' => ['name']
            ]);
            
            foreach ($iterator as $row) {
                $profiles[$row['id']] = $row['name'];
            }
        } catch (\Throwable $e) {
            error_log('Erro ao buscar perfis GLPI: ' . $e->getMessage());
        }
        
        return $profiles;
    }

    /**
     * Obtém os perfis autorizados para um relatório
     */
    public static function getReportProfiles($report_id) {
        global $DB;
        
        $profiles = [];
        
        try {
            $iterator = $DB->request([
                'SELECT' => ['profiles_id'],
                'FROM' => 'glpi_plugin_powerbireports_reports_profiles',
                'WHERE' => ['plugin_powerbireports_reports_id' => $report_id]
            ]);
            
            foreach ($iterator as $row) {
                $profiles[] = $row['profiles_id'];
            }
        } catch (\Throwable $e) {
            error_log('Erro ao buscar perfis do relatório: ' . $e->getMessage());
        }
        
        return $profiles;
    }

    /**
     * Sincroniza os perfis autorizados de um relatório
     */
    public static function syncProfiles($report_id, $profiles_ids) {
        global $DB;
        
        try {
            // Remove todos os perfis atuais
            $DB->delete('glpi_plugin_powerbireports_reports_profiles', [
                'plugin_powerbireports_reports_id' => $report_id
            ]);
            
            // Adiciona os novos perfis
            if (!empty($profiles_ids) && is_array($profiles_ids)) {
                foreach ($profiles_ids as $profile_id) {
                    $DB->insert('glpi_plugin_powerbireports_reports_profiles', [
                        'plugin_powerbireports_reports_id' => $report_id,
                        'profiles_id' => $profile_id
                    ]);
                }
            }
            
            return true;
        } catch (\Throwable $e) {
            error_log('Erro ao sincronizar perfis: ' . $e->getMessage());
            return false;
        }
    }
}
