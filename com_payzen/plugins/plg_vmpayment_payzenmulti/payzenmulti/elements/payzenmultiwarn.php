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

/**
 * Renders a warning element.
 */
class JElementPayzenMultiWarn extends JElement
{
    /**
     * Element name.
     *
     * @access protected
     * @var string
     */
    var $_name = 'PayzenmultiWarn';

    function fetchElement($name, $value, &$node, $control_name)
    {
        if (! class_exists('com_payzenInstallerScript')) {
            require_once(rtrim(JPATH_ADMINISTRATOR, DS) . DS . 'components' . DS . 'com_payzen' . DS . 'script.install.php');
        }

        $plugin_features = com_payzenInstallerScript::$plugin_features;

        $html = '';
        if ($plugin_features['restrictmulti']) {
            $style = 'style="background: none repeat scroll 0 0 #FFFFE0; border: 1px solid #E6DB55; margin: 0 0 20px -67%; padding: 10px;"';
            $html .= '<div class="level1" ' . $style . '>';
            $html .= JText::_($value);
            $html .= '</div>';
        }

        return $html;
    }
}
