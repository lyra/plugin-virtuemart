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

if (! class_exists('JFormFieldPayzenText')) {
    require_once(rtrim(JPATH_PLUGINS, DS) . DS . 'vmpayment' . DS . 'payzen' . DS . 'payzen'. DS . 'fields' . DS . 'payzentext.php');
}

/**
 * Renders a Text element.
 */
class JFormFieldPayzenMultiText extends JFormFieldPayzenText
{
    var $type = 'PayzenMultiText';
}
