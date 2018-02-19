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

JFormHelper::loadFieldClass('filelist');

/**
 * Renders an item select element (with multiple choice possibility).
 */
class JFormFieldPayzenList extends JFormFieldList {

	var $type = 'PayzenList';

	function getOptions() {
		if (!class_exists ('PayzenApi')) {
			$plugin_path = JPATH_ROOT . DS . 'plugins' . DS . 'vmpayment';
			if (version_compare (JVERSION, '1.6.0', 'ge')) {
				$plugin_path .= DS . 'payzen';
			}

			require_once($plugin_path .DS . 'payzen' . DS . 'helpers' . DS . 'PayzenApi.php');
		}

		if ($this->fieldname == 'payment_cards') {
			$payzenOptions = PayzenApi::getSupportedCardTypes();
		} else {
			foreach(PayzenApi::getSupportedLanguages() as $code => $lang) {
				$payzenOptions[$code] = 'VMPAYMENT_PAYZEN_' . strtoupper($lang);
			}
		}

		// construct an array of HTML OPTION statements.
		$options = array();
		foreach ($payzenOptions as $key => $value) {
			$options[] = JHTML::_('select.option', $key, JText::_ ($value));
		}

		$options = array_merge(parent::getOptions(), $options);
		return $options;
	}
}
