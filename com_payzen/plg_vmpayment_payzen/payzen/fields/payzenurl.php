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
 * Renders a URL element.
 */
class JFormFieldPayzenUrl extends JFormField
{
    var $type = 'PayzenUrl';

    function getInput()
    {
        return '<label for="' . $this->name . '"><b>' . JURI::root() . $this->value . '</b></label>';
    }
}
