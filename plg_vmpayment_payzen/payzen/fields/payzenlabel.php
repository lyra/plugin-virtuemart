<?php
/**
 * PayZen V2-Payment Module version 2.0.3 for VirtueMart 3.x. Support contact : support@payzen.eu.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * @category  payment
 * @package   payzen
 * @author    Lyra Network (http://www.lyra-network.com/)
 * @copyright 2014-2016 Lyra Network and contributors
 * @license   http://www.gnu.org/licenses/old-licenses/gpl-2.0.html  GNU General Public License (GPL v2)
 */

/* check to ensure this file is within the rest of the framework */
defined('JPATH_BASE') or die();

jimport('joomla.form.formfield');

/**
 * Renders a label element.
 */
class JFormFieldPayzenLabel extends JFormField {

	var $type = 'PayzenLabel';

	function getInput() {
		if ($this->fieldname == 'documentation') {
			return '<a style="color: red;" target="_blank" href="'.JURI::root ().$this->value.'">'.JText::_ ('VMPAYMENT_PAYZEN_DOC_TEXT').'</a>';
		}

		return '<label>'.$this->value.'</label>';
	}
}
