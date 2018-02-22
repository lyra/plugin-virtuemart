<?php
/**
 * PayZen V2-Payment Module version 2.1.0 for VirtueMart 3.x. Support contact : support@payzen.eu.
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
 * @author    Lyra Network (http://www.lyra-network.com/)
 * @copyright 2014-2017 Lyra Network and contributors
 * @license   http://www.gnu.org/licenses/old-licenses/gpl-2.0.html  GNU General Public License (GPL v2)
 * @category  payment
 * @package   payzen
 */
defined('JPATH_BASE') or die();

JFormHelper::loadFieldClass('filelist');

/**
 * Renders an item select element (with multiple choice possibility).
 */
class JFormFieldPayzenMultiList extends JFormFieldList
{

    var $type = 'PayzenmultiList';

    function getOptions()
    {
        if (! class_exists('PayzenApi')) {
            $plugin_path = JPATH_ROOT . DS . 'plugins' . DS . 'vmpayment';
            if (version_compare(JVERSION, '1.6.0', 'ge')) {
                $plugin_path .= DS . 'payzenmulti';
            }

            require_once(rtrim(JPATH_ADMINISTRATOR, DS) . DS . 'components' . DS . 'com_payzen' . DS . 'classes' . DS .
                    'PayzenApi.php');
        }

        if ($this->fieldname == 'payment_cards') {
            $payzenmultiOptions = $this->_getAvailableMultiCards();
        } else {
            foreach (PayzenApi::getSupportedLanguages() as $code => $lang) {
                $payzenmultiOptions[$code] = 'VMPAYMENT_PAYZENMULTI_' . strtoupper($lang);
            }
        }

        // construct an array of HTML OPTION statements.
        $options = array();
        foreach ($payzenmultiOptions as $key => $value) {
            $options[] = JHTML::_('select.option', $key, JText::_($value));
        }

        $options = array_merge(parent::getOptions(), $options);
        return $options;
    }

    function _getAvailableMultiCards()
    {
        $multi_cards = array(
                'AMEX',
                'CB',
                'DINERS',
                'DISCOVER',
                'E-CARTEBLEUE',
                'JCB',
                'MASTERCARD',
                'PRV_BDP',
                'PRV_BDT',
                'PRV_OPT',
                'PRV_SOC',
                'VISA',
                'VISA_ELECTRON'
        );

        $all_cards = PayzenApi::getSupportedCardTypes();
        $avail_cards = array();

        foreach ($all_cards as $key => $value) {
            if (in_array($key, $multi_cards)) {
                $avail_cards[$key] = $value;
            }
        }

        return $avail_cards;
    }
}
