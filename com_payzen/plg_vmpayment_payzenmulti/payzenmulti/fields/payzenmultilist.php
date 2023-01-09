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

if (! class_exists('JFormFieldPayzenList')) {
    require_once(rtrim(JPATH_PLUGINS, DS) . DS . 'vmpayment' . DS . 'payzen' . DS . 'payzen'. DS . 'fields' . DS . 'payzenlist.php');
}

use Lyranetwork\Payzen\Sdk\Form\Api as PayzenApi;

/**
 * Renders an item select element (with multiple choice possibility).
 */
class JFormFieldPayzenMultiList extends JFormFieldPayzenList
{
    var $type = 'PayzenmultiList';
    var $identifier = 'payzenmulti';

    function _getAvailableCards()
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
