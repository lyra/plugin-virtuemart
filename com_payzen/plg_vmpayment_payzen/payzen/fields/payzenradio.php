<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for VirtueMart. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL v2)
*/

defined('JPATH_BASE') or die();

jimport('joomla.form.formfield');

/**
 * Renders a Radio element.
 */
class JFormFieldPayzenRadio extends JFormFieldRadio
{
    var $type = 'PayzenRadio';

    function getLayoutData()
    {
        $data = parent::getLayoutData();
        if (! class_exists('com_payzenInstallerScript')) {
            require_once(rtrim(JPATH_ADMINISTRATOR, DS) . DS . 'components' . DS . 'com_payzen' . DS . 'script.install.php');
        }

        $plugin_features = com_payzenInstallerScript::$plugin_features;
        if ($plugin_features['shatwo'] && $this->fieldname == 'sign_algo') {
            $data['description'] = preg_replace('#<br /><b>[^<>]+</b>#', '', $data['description']);
        }

        $data['disabled'] = ($plugin_features['qualif'] && $this->fieldname == 'ctx_mode') ? 'disabled="disabled"' : '';

        return $data;
    }
}
