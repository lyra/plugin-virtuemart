<?php
/**
 * PayZen V2-Payment Module version 1.3.4 (revision 65175) for VirtueMart 2.x.
 *
 * Copyright (C) 2014 Lyra Network and contributors
 * Support contact : support@payzen.eu
 * Author link : http://www.lyra-network.com/
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
 * @author    Lyra Network <supportvad@lyra-network.com>
 * @copyright 2014 Lyra Network and contributors
 * @license   http://www.gnu.org/licenses/old-licenses/gpl-2.0.html  GNU General Public License (GPL v2)
 * @version   1.3.4 (revision 65175)
*/

// check to ensure this file is within the rest of the framework
defined('JPATH_BASE') or die();

/**
 * Renders an item select element (with multiple choice possibility)
 */
class JElementPayzenList extends JElement {
	/**
	 * Element name
	 *
	 * @access protected
	 * @var string
	 */
	var $_name = 'PayzenList';

	function fetchElement($name, $value, &$node, $control_name) {
		// base name of the HTML control.
		$ctrl = $control_name .'['. $name .']';

		// construct an array of the HTML OPTION statements.
		$options = array ();

		$data = $node->data();
		if(!empty($data)) {
			foreach (eval($data) as $code => $text) { // evaluate element text to construct options
				$options[] = JHTML::_('select.option', $code, JText::_($text));
			}
		} else {
			foreach ($node->children() as $option) {
				$val = $option->attributes('value');
				$text  = $option->data();
				$options[] = JHTML::_('select.option', $val, JText::_($text));
			}
		}

		// construct the various argument calls that are supported.
		$attribs = ' ';
		if ($v = $node->attributes('size')) {
			$attribs .= 'size="'.$v.'"';
		}
		if ($v = $node->attributes('style')) {
			$attribs .= 'style="'.$v.'"';
		}
		if ($v = $node->attributes('class')) {
			$attribs .= 'class="'.$v.'"';
		} else {
			$attribs .= 'class="inputbox"';
		}
		if ($m = $node->attributes('multiple')) {
			$attribs .= ' multiple="multiple"';
			$ctrl .= '[]';
		}

		// render the HTML select list.
		return JHTML::_('select.genericlist', $options, $ctrl, $attribs, 'value', 'text', $value, $control_name.$name);
	}
}