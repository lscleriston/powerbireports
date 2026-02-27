<?php
namespace GlpiPlugin\Powerbireports;

use CommonDBTM;
use CommonGLPI;
use Html;
use Profile as GlpiProfile;
use Session;
use ProfileRight;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

class Profile extends CommonDBTM
{
    static $rightname = 'profile';

    function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if ($item->getType() == 'Profile') {
            if ($item->getField('interface') != 'helpdesk') {
                return __('Power BI Reports', 'powerbireports');
            }
        }
        return '';
    }

    static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if ($item->getType() == 'Profile') {
            $ID = $item->getID();
            $profile = new self();
            $profile->showForm($ID);
        }
        
        return true;
    }

    function showForm($ID, $options = [])
    {
        global $CFG_GLPI;
        
        $profile = new GlpiProfile();
        $profile->getFromDB($ID);
        
        if (!$profile->canView()) {
            return false;
        }
        
        echo "<div class='spaced'>";
        
        $canedit = Session::haveRight('profile', UPDATE);
        
        if ($canedit) {
            // Abre um formulário para permitir salvar as permissões
            echo "<form method='post' action='" . $profile->getFormURL() . "'>";
        }
        
        $rights = self::getAllRights();
        $profile->displayRightsChoiceMatrix($rights, [
            'canedit' => $canedit,
            'default_class' => 'tab_bg_2',
            'title' => __('Power BI Reports', 'powerbireports')
        ]);
        
        if ($canedit) {
            echo "<div class='center'>";
            echo Html::hidden('id', ['value' => $ID]);
            echo Html::submit(_sx('button', 'Save'), ['name' => 'update', 'class' => 'btn btn-primary']);
            echo "</div>";
            Html::closeForm();
        }
        
        echo "</div>";
    }

    static function getAllRights($all = false)
    {
        $rights = [
            [
                'itemtype' => self::class,
                'label' => __('Power BI Reports', 'powerbireports'),
                'field' => 'powerbireports',
                'rights' => [
                    READ => __('Read', 'powerbireports'),
                    UPDATE => __('Write', 'powerbireports') // Adicionando o direito de gravação
                ]
            ]
        ];
        
        return $rights;
    }

    static function initProfile()
    {
        global $DB;
        
        $profile = new GlpiProfile();
        
        // Add new rights in glpi_profilerights table
        foreach ($profile->getAllRights() as $data) {
            if (countElementsInTable('glpi_profilerights', 
                                    ['name' => $data['field']]) == 0) {
                ProfileRight::addProfileRights([$data['field']]);
            }
        }
    }
}