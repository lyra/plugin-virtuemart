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
 * Renders a URL element.
 */
class JElementPayzenUrl extends JElement
{
    /**
    * Element name.
    *
    * @access protected
    * @var string
    */
    var $_name = 'PayzenUrl';

    function fetchElement($name, $value, &$node, $control_name)
    {
        return '<label for="' . $control_name . '"><strong>' . JURI::root() . $value . '</strong></label>';
    }
}
