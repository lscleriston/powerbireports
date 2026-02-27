<?php

class PluginPowerbireportsMenu extends CommonGLPI {
    static $rightname = 'powerbireports';

    static function getMenuName() {
        return __('Power BI Reports', 'powerbireports');
    }

    static function getMenuContent() {
        global $CFG_GLPI;
        
        if (!Session::haveRight(static::$rightname, READ)) {
            return false;
        }
        
        $menu = [
            'title' => self::getMenuName(),
            'page'  => Plugin::getWebDir('powerbireports') . '/front/index.php',
            'icon'  => 'fas fa-chart-line',
        ];

        // Submenu para Central de Relatórios
        $menu['options']['reports'] = [
            'title' => __('Reports', 'powerbireports'),
            'page'  => Plugin::getWebDir('powerbireports') . '/front/index.php',
            'icon'  => 'fas fa-chart-bar'
        ];

        // Submenu para Configuração (apenas para quem tem permissão de UPDATE)
        if (Session::haveRight(static::$rightname, UPDATE)) {
            $menu['options']['config'] = [
                'title' => __('Configuration', 'powerbireports'),
                'page'  => Plugin::getWebDir('powerbireports') . '/front/config.form.php',
                'icon'  => 'fas fa-cog'
            ];
        }

        return $menu;
    }
}