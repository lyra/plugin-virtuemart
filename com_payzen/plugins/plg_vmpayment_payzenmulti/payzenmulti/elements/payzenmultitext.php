<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for VirtueMart. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL v2)
*/

// Check to ensure this file is within the rest of the framework.
defined('JPATH_BASE') or die();

class JElementPayzenMultiText extends JElement
{
    /**
     * Element name.
     *
     * @access protected
     * @var string
     */
    var $_name = 'PayzenmultiText';

    function fetchElement($name, $value, &$node, $control_name)
    {
        $ctrl = $control_name . '[' . $name . ']';
        $size = ($node->attributes('size') ? 'size="' . $node->attributes('size') . '"' : '');
        $class = ($node->attributes('class') ? $node->attributes('class') : 'text_area');
        $auto = ((string) $node->attributes('autocomplete') == 'off') ? ' autocomplete="off"' : '';

        if ($class == 'payzenmultimessage') {
            $value = JText::_($value);
        }

        if (! class_exists('com_payzenInstallerScript')) {
            require_once(rtrim(JPATH_ADMINISTRATOR, DS) . DS . 'components' . DS . 'com_payzen' . DS . 'script.install.php');
        }

        $plugin_features = com_payzenInstallerScript::$plugin_features;

        $disabled = ($plugin_features['qualif'] && $name == 'key_test') ? ' disabled="disabled"' : '';

        return '<input type="text" name="' . $ctrl . '" id="' . $control_name . $name . '" value="' . $value
            . '" class="' . $class . '" ' . $size . $disabled . $auto . ' />';
    }
}
