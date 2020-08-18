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

if (! class_exists('JFormFieldPayzenFiles')) {
    require_once(rtrim(JPATH_ADMINISTRATOR, DS) . DS . 'components' . DS . 'com_payzen' . DS . 'plg_vmpaymentpayzen' . DS . 'fields' . DS . 'payzenfiles.php');
}

/**
 * Renders a label element.
 */
class JFormFieldPayzenMultiFiles extends JFormFieldPayzenFiles
{
    var $type = 'PayzenmultiFiles';
}
