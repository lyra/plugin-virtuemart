<?php
/**
 * Copyright © Lyra Network.
 * This file is part of PayZen plugin for VirtueMart. See COPYING.md for license details.
 *
 * @author    Lyra Network (https://www.lyra.com/)
 * @copyright Lyra Network
 * @license   http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL v2)
*/

defined('_JEXEC') or die('Restricted access');

if (! class_exists('plgVMPaymentPayzen')) {
    require_once(rtrim(JPATH_PLUGINS, DS) . DS . 'vmpayment' . DS . 'payzen' . DS . 'payzen.php');
}

class plgVMPaymentPayzenMulti extends plgVMPaymentPayzen
{
    protected $method_identifier = "payzenmulti";
    protected $order_total = '';
    protected $logo = 'payzenmulti.png';

    /**
     * Reimplementation of vmPaymentPlugin::checkPaymentConditions()
     *
     * @param array  $cart_prices all cart prices
     * @param object $payment payment parameters object
     * @return bool true if conditions verified
     */
    function checkConditions($cart, $method, $cart_prices)
    {
        $this->order_total = $cart_prices['salesPrice'];

        return parent::checkConditions($cart, $method, $cart_prices);
    }
}
