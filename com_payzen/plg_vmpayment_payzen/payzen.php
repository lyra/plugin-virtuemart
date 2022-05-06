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

if (! class_exists('vmPSPlugin')) {
    require(JPATH_VM_PLUGINS . DS . 'vmpsplugin.php');
}

class plgVMPaymentPayzen extends vmPSPlugin
{
    // Instance of class.
    public static $_this = false;

    protected $method_identifier = 'payzen';
    protected $plugin_features;
    protected $logo = 'payzen.png';

    function __construct(& $subject, $config)
    {
        parent::__construct($subject, $config);

        $this->_loggable = true;
        $this->tableFields = array_keys($this->getTableSQLFields());
        $this->_tablepkey = 'id';
        $this->_tableId = 'id';

        $vars_to_push = $this->getVarsToPush();

        if (! class_exists('com_payzenInstallerScript')) {
            require_once(rtrim(JPATH_ADMINISTRATOR, DS) . DS . 'components' . DS . 'com_payzen' . DS . 'script.install.php');
        }

        $this->plugin_features = com_payzenInstallerScript::$plugin_features;

        if ($this->plugin_features['qualif']) {
            $vars_to_push['ctx_mode']['0'] = 'PRODUCTION';
        }

        $this->setConfigParameterable($this->_configTableFieldName, $vars_to_push);
    }

    protected function getVmPluginCreateTableSQL()
    {
        return $this->createTableSQL('Payment ' . $this->_name . ' Table');
    }

    function getTableSQLFields()
    {
        return array(
            'id'                                                               => 'int(11) UNSIGNED NOT NULL AUTO_INCREMENT',
            'virtuemart_order_id'                                              => 'int(1) UNSIGNED',
            'order_number'                                                     => 'char(64)',
            'virtuemart_paymentmethod_id'                                      => 'mediumint(1) UNSIGNED',
            'payment_name'                                                     => 'varchar(5000)',
            'payment_order_total'                                              => 'decimal(15,5) NOT NULL DEFAULT \'0.00000\'',
            'payment_currency'                                                 => 'char(3)',
            'cost_per_transaction'                                             => 'decimal(10,2)',
            'cost_percent_total'                                               => 'decimal(10,2)',
            'tax_id'                                                           => 'smallint(1)',
            $this->method_identifier . '_custom'                               => 'varchar(255)',
            $this->method_identifier . '_response_payment_amount'              => 'char(15)',
            $this->method_identifier . '_response_auth_number'                 => 'char(10)',
            $this->method_identifier . '_response_payment_currency'            => 'char(3)',
            $this->method_identifier . '_response_payment_mean'                => 'char(255)',
            $this->method_identifier . '_response_payment_date'                => 'char(20)',
            $this->method_identifier . '_response_payment_status'              => 'char(3)',
            $this->method_identifier . '_response_payment_message'             => 'char(255)',
            $this->method_identifier . '_response_payment_card_brand_choice'   => 'char(255)',
            $this->method_identifier . '_response_card_number'                 => 'char(50)',
            $this->method_identifier . '_response_trans_id'                    => 'char(6)',
            $this->method_identifier . '_response_expiry_month'                => 'char(2)',
            $this->method_identifier . '_response_expiry_year'                 => 'char(4)'
        );
    }

    function getCosts(VirtueMartCart $cart, $method, $cart_prices)
    {
        if (preg_match('/%$/', $method->cost_percent_total)) {
            $cost_percent_total = substr($method->cost_percent_total, 0, -1);
        } else {
            $cost_percent_total = $method->cost_percent_total;
        }

        return (float) $method->cost_per_transaction + ($cart_prices['salesPrice'] * (float) $cost_percent_total * 0.01);
    }

    /**
     * Reimplementation of vmPaymentPlugin::checkPaymentConditions()
     *
     * @param array  $cart_prices all cart prices
     * @param object $payment payment parameters object
     * @return bool true if conditions verified
     */
    function checkConditions($cart, $method, $cart_prices)
    {
        $this->convert($method);
        $amount = $cart_prices['salesPrice'];
        return (($amount >= $method->min_amount && $amount <= $method->max_amount)
            || ($amount >= $method->min_amount && empty($method->max_amount)));
    }

    function convert($method)
    {
        $method->min_amount = (float) $method->min_amount;
        $method->max_amount = (float) $method->max_amount;
    }

    /**
     * Prepare data and redirect to gateway platform.
     *
     * @param object $cart
     * @param object $order
     * @return bool|null|void
     */
    function plgVmConfirmedOrder($cart, $order)
    {
        if (! ($method = $this->getVmPluginMethod($order['details']['BT']->virtuemart_paymentmethod_id))) {
            return null; // Another method was selected, do nothing.
        }

        if (! $this->selectedThisElement($method->payment_element)) {
            return false;
        }

        $this->_debug = $method->debug; // Enable debug.
        $session = JFactory::getSession();
        $session_id = $session->getId();

        $this->logInfo('plgVmConfirmedOrder -- order number: ' . $order['details']['BT']->order_number, 'message');

        if (! class_exists('PayzenRequest')) {
            require_once(rtrim(JPATH_ADMINISTRATOR, DS) . DS . 'components' . DS . 'com_payzen' . DS . 'classes' . DS . 'PayzenRequest.php');
        }

        $request = new PayzenRequest();

        // Set configuration parameters.
        $paramNames = array(
            'platform_url', 'key_test', 'key_prod', 'capture_delay', 'ctx_mode', 'site_id',
            'validation_mode', 'redirect_enabled', 'redirect_success_timeout', 'redirect_success_message',
            'redirect_error_timeout', 'redirect_error_message', 'return_mode', 'sign_algo'
        );
        foreach ($paramNames as $name) {
            $request->set($name, $method->$name);
        }

        // Set urls.
        $url_return = JROUTE::_(JURI::root() . 'index.php?option=com_virtuemart&view=pluginresponse&task=pluginresponsereceived');
        $uri = JURI::getInstance($url_return);
        $uri->setVar('pm', $order['details']['BT']->virtuemart_paymentmethod_id);
        $uri->setVar('Itemid', JRequest::getInt('Itemid'));
        $request->set('url_return', $uri->toString());

        $url_cancel = JROUTE::_(JURI::root() . 'index.php?option=com_virtuemart&view=pluginresponse&task=pluginUserPaymentCancel');
        $uri = JURI::getInstance($url_cancel);
        $uri->setVar('on', $order['details']['BT']->order_number);
        $uri->setVar('pm', $order['details']['BT']->virtuemart_paymentmethod_id);
        $uri->setVar('Itemid', JRequest::getInt('Itemid'));
        $request->set('url_cancel', $uri->toString());

        // Set the language code.
        $lang = JFactory::getLanguage();
        $lang->load('plg_vmpayment_' . $this->_name, JPATH_ADMINISTRATOR);

        $tag = substr($lang->get('tag'), 0, 2);
        $language = PayzenApi::isSupportedLanguage($tag) ? $tag : $method->language;
        $request->set('language', $language);

        // Set currency.
        if (! class_exists('VirtueMartModelCurrency')) {
            require(VMPATH_ADMIN . DS . 'models' . DS . 'currency.php');
        }

        $currency_model = new VirtueMartModelCurrency();
        $currency_obj = $currency_model->getCurrency($cart->pricesCurrency);

        $currency = PayzenApi::findCurrencyByNumCode($currency_obj->currency_numeric_code);
        if ($currency == null) {
            $this->logInfo('plgVmConfirmedOrder -- could not find currency numeric code for currency : ' . $currency_obj->currency_numeric_code, 'error');
            vmInfo(JText::_('VMPAYMENT_' . $this->_name . '_CURRENCY_NOT_SUPPORTED'));
            return null;
        }

        $request->set('currency', $currency->getNum());

        // payment_cards may be one value or array.
        $cards = $method->payment_cards;
        $cards = ! is_array($cards) ? $cards : (in_array('', $cards) ? '' : implode(';', $cards));
        $request->set('payment_cards', $cards);

        // available_languages may be one value or array.
        $available_languages = $method->available_languages;
        $available_languages = ! is_array($available_languages) ? $available_languages : (in_array('', $available_languages) ? '' : implode(';', $available_languages));
        $request->set('available_languages', $available_languages);

        $request->set('contrib', 'VirtueMart_3.x_2.2.2/' . JVERSION . '_' . vmVersion::$RELEASE . '/' . PayzenApi::shortPhpVersion());

        // Set customer info.
        $usrBT = $order['details']['BT'];
        $usrST = isset($order['details']['ST']) ? $order['details']['ST'] : $order['details']['BT'];

        $request->set('cust_email', $usrBT->email);
        $request->set('cust_id', @$usrBT->virtuemart_user_id);
        $request->set('cust_title', @$usrBT->title);
        $request->set('cust_first_name', $usrBT->first_name);
        $request->set('cust_last_name', $usrBT->last_name);
        $request->set('cust_address', $usrBT->address_1 . ($usrBT->address_2 ? ' ' . $usrBT->address_2 : ''));
        $request->set('cust_zip', $usrBT->zip);
        $request->set('cust_city', $usrBT->city);
        $request->set('cust_state', @ShopFunctions::getStateByID($usrBT->virtuemart_state_id));
        $request->set('cust_country', @ShopFunctions::getCountryByID($usrBT->virtuemart_country_id, 'country_2_code'));
        $request->set('cust_phone', $usrBT->phone_1);
        $request->set('cust_cell_phone', $usrBT->phone_2);

        $request->set('ship_to_first_name', $usrST->first_name);
        $request->set('ship_to_last_name', $usrST->last_name);
        $request->set('ship_to_city', $usrST->city);
        $request->set('ship_to_street', $usrST->address_1);
        $request->set('ship_to_street2', $usrST->address_2);
        $request->set('ship_to_state', @ShopFunctions::getStateByID($usrST->virtuemart_state_id));
        $request->set('ship_to_country', @ShopFunctions::getCountryByID($usrST->virtuemart_country_id, 'country_2_code'));
        $request->set('ship_to_phone_num', $usrST->phone_1);
        $request->set('ship_to_zip', $usrST->zip);

        // Set order_id.
        $request->set('order_id', $order['details']['BT']->order_number);

        // Set the amount to pay.
        $exchangeRate = $currency_obj->currency_exchange_rate;
        if ($exchangeRate == 0) {
            // Not consider exchange rate.
            $exchangeRate = 1;
        }

        $amount = $order['details']['BT']->order_total * $exchangeRate;
        $request->set('amount', $currency->convertAmountToInteger(round($amount, $currency_obj->currency_decimal_place)));

        // 3DS activation according to amount.
        $threeds_mpi = null;
        if (($method->threeds_min_amount != '') && ($amount < $method->threeds_min_amount)) {
            $threeds_mpi = '2';
        }

        $request->set('threeds_mpi', $threeds_mpi);

        // Prepare data that should be stored in the database.
        $dbValues['order_number'] = $order['details']['BT']->order_number;
        $dbValues['virtuemart_paymentmethod_id'] = $cart->virtuemart_paymentmethod_id;
        $dbValues['payment_name'] = $this->renderPluginName($method, $order);
        $dbValues['payment_order_total'] = $order['details']['BT']->order_total;
        $dbValues['payment_currency'] = $currency_obj->currency_numeric_code;
        $dbValues['cost_per_transaction'] = $method->cost_per_transaction;
        $dbValues['cost_percent_total'] = $method->cost_percent_total;
        $dbValues['tax_id'] = $method->tax_id;
        $dbValues[$this->_name . '_custom'] = $session_id;
        $this->storePSPluginInternalData($dbValues);

        $this->logInfo('plgVmConfirmedOrder -- payment data saved to table ' . $this->_tablename, 'message');

        $logo_path = '/images/virtuemart/payment/';
        if (version_compare(vmVersion::$RELEASE, '3.2.1', '<')) {
            $logo_path = '/images/stories/virtuemart/payment/';
        }

        // Echo the redirect form.
        $form = '<p>' . JText::_('VMPAYMENT_' . $this->_name . '_PLEASE_WAIT') . '</p>';
        $form .= '<p>' . JText::_('VMPAYMENT_' . $this->_name . '_CLICK_BUTTON_IF_NOT_REDIRECTED') . '</p>';
        $form .= '<form action="' . $request->get('platform_url') . '" method="POST" name="vm_' . $this->_name . '_form" >';
        $form .= '<br />';
        $form .= '<input type="image" name="submit" src="' . JURI::base(true) . $logo_path . $this->logo . '" alt="' . JText::_('VMPAYMENT_' . $this->_name . '_BTN_ALT') . '" title="' . JText::_('VMPAYMENT_' . $this->_name . '_BTN_TITLE') . '"/>';
        $form .= $request->getRequestHtmlFields();
        $form .= '</form></div>';
        $form .= '<script type="text/javascript">document.forms["vm_' . $this->_name . '_form"].submit();</script>';

        $this->logInfo('plgVmConfirmedOrder -- user redirected to ' . $this->_name, 'message');

        $cart->_confirmDone = false;
        $cart->_dataValidated = false;
        $cart->setCartIntoSession();

        vRequest::setVar('html', $form);
    }

    /**
     * Check gateway response, save order if not done by server call and redirect to response page
     * when client comes back from payment platform.
     *
     * @param $html
     * @return bool|null|string
     */
    function plgVmOnPaymentResponseReceived(&$html)
    {
        if (! class_exists('VirtueMartCart')) {
            require(JPATH_VM_SITE . DS . 'helpers' . DS . 'cart.php');
        }

        // The payment itself should send the parameter needed.
        $virtuemart_paymentmethod_id = JRequest::getInt('pm', 0);
        if (! ($method = $this->getVmPluginMethod($virtuemart_paymentmethod_id))) {
            return null; // Another method was selected, do nothing.
        }

        if (! $this->selectedThisElement($method->payment_element)) {
            return false;
        }

        $this->_debug = $method->debug; // Enable debug.
        $this->logInfo('plgVmOnPaymentResponseReceived -- user returned back from ' . $this->_name, 'message');

        $data = JRequest::get('request', 2);

        // Load API.
        if (! class_exists('PayzenResponse')) {
            require_once(rtrim(JPATH_ADMINISTRATOR, DS) . DS . 'components' . DS . 'com_payzen' . DS . 'classes' . DS . 'PayzenResponse.php');
        }

        $payzen_response = new PayzenResponse(
            $data,
            $method->ctx_mode,
            $method->key_test,
            $method->key_prod,
            $method->sign_algo
        );

        if (! $payzen_response->isAuthentified()) {
            $this->logInfo('plgVmOnPaymentResponseReceived -- suspect request sent to plgVmOnPaymentResponseReceived, IP : ' . $_SERVER['REMOTE_ADDR'], 'error');
            $this->logInfo('Signature algorithm selected in module settings must be the same as one selected in PayZen Back Office.', 'error');
            $html = $this->_getHtmlPaymentResponse('VMPAYMENT_' . $this->_name . '_ERROR_MSG', false);
            return null;
        }

        // Retrieve order info from database.
        if (! class_exists('VirtueMartModelOrders')) {
            require(VMPATH_ADMIN . DS . 'models' . DS . 'orders.php');
        }

        $virtuemart_order_id = VirtueMartModelOrders::getOrderIdByOrderNumber($payzen_response->get('order_id'));

        // Order not found.
        if (! $virtuemart_order_id) {
            $this->logInfo('plgVmOnPaymentResponseReceived -- payment check attempted on non existing order : ' . $payzen_response->get('order_id'), 'error');
            $html = $this->_getHtmlPaymentResponse('VMPAYMENT_' . $this->_name . '_ERROR_MSG', false);
            return null;
        }

        $order = VirtueMartModelOrders::getOrder($virtuemart_order_id);
        $order_status_code = $order['items'][0]->order_status;

        $result = false;

        if ($payzen_response->isAcceptedPayment()) {
            $currency = PayzenApi::findCurrencyByNumCode($payzen_response->get('currency'))->getAlpha3();
            $amount = $payzen_response->getFloatAmount() . ' ' . $currency;
            $html = $this->_getHtmlPaymentResponse('VMPAYMENT_' . $this->_name . '_SUCCESS_MSG', true, $payzen_response->get('order_id'), $amount);

            $new_status = $method->order_success_status;
            $result = true;
        } else {
            $html = $this->_getHtmlPaymentResponse('VMPAYMENT_' . $this->_name . '_FAILURE_MSG', false);

            $new_status = $method->order_failure_status;
        }

        // Order not processed yet.
        if ($order_status_code == 'P') {
            $this->logInfo('plgVmOnPaymentResponseReceived -- IPN URL does not work.', 'warning');

            if ($method->ctx_mode === 'TEST') {
                // TEST mode warning : check URL not correctly called.
                $msg  = '<div style="margin-bottom: 15px;">' . JText::_('VMPAYMENT_' . $this->_name . '_CHECK_URL_WARN');
                $msg .= '<br />' . JText::_('VMPAYMENT_' . $this->_name . '_CHECK_URL_WARN_DETAILS') . '</div>';

                if ($this->plugin_features['prodfaq']) {
                    $msg .= '<div style="margin-bottom: 15px;">' . JText::_('VMPAYMENT_' . $this->_name . '_SHOP_TO_PROD_INFO') . '</div>';
                }
                vmWarn($msg, '');
            }

            $this->managePaymentResponse($virtuemart_order_id, $payzen_response, $new_status);
        } elseif (($method->ctx_mode === 'TEST') && $this->plugin_features['prodfaq']) {
            // TEST mode and other site than ALATAK: server URL is correctly configured, just show going to prod info.
            vmWarn('<div style="margin-bottom: 15px;">' . JText::_('VMPAYMENT_' . $this->_name . '_SHOP_TO_PROD_INFO') . '</div>', '');
        }

        return $result;
    }

    /**
     * Process a gateway payment cancellation.
     *
     * @return bool|null
     */
    function plgVmOnUserPaymentCancel()
    {
        // The payment itself should send the parameter needed.
        $virtuemart_paymentmethod_id = JRequest::getInt('pm', 0);
        if (! ($method = $this->getVmPluginMethod($virtuemart_paymentmethod_id))) {
            return null; // Another method was selected, do nothing.
        }

        if (! $this->selectedThisElement($method->payment_element)) {
            return false;
        }

        $order_number = JRequest::getString('on');
        if (! $order_number) {
            return false;
        }

        if (! class_exists('VirtueMartModelOrders')) {
            require(VMPATH_ADMIN . DS . 'models' . DS . 'orders.php');
        }

        if (! ($virtuemart_order_id = VirtueMartModelOrders::getOrderIdByOrderNumber($order_number))) {
            return null;
        }

        if (! ($payment_data = $this->getDataByOrderId($virtuemart_order_id))) {
            return null;
        }

        $this->_debug = $method->debug; // Enable debug.
        $this->logInfo('plgVmOnUserPaymentCancel -- user cancelled payment from ' . $this->_name, 'message');

        $session = JFactory::getSession();
        $session_id = $session->getId();
        $field = $this->_name . '_custom';
        if (strcmp($payment_data->$field, $session_id) === 0) {
            $this->handlePaymentUserCancel($virtuemart_order_id);
        }

        return true;
    }

    /**
     * Check gateway response, save order and empty cart if payment success when server notification
     * is received from payment platform.
     *
     * @return bool|null
     */
    function plgVmOnPaymentNotification()
    {
        // Platform params and payment data.
        $data = JRequest::get('post', 2);
        if (! key_exists('vads_order_id', $data) || ! $data['vads_order_id']) {
            $this->logInfo('plgVmOnPaymentNotification -- another method was selected, do nothing.', 'message');
            return null; // Another method was selected, do nothing.
        }

        $this->logInfo('plgVmOnPaymentNotification -- start processing.', 'message');

        // Retrieve order info from database.
        if (! class_exists('VirtueMartModelOrders')) {
            require(VMPATH_ADMIN . DS . 'models' . DS . 'orders.php');
        }

        // Payment params.
        $virtuemart_order_id = VirtueMartModelOrders::getOrderIdByOrderNumber($data['vads_order_id']);
        if (! ($payment_data = $this->getDataByOrderId($virtuemart_order_id))) {
            return false;
        }

        $method = $this->getVmPluginMethod($payment_data->virtuemart_paymentmethod_id);
        if (! $this->selectedThisElement($method->payment_element)) {
            return false;
        }

        $this->_debug = $method->debug;
        $custom = $this->_name . '_custom';
        $session_id = $payment_data->$custom;

        // Load API.
        if (! class_exists('PayzenResponse')) {
            require(rtrim(JPATH_ADMINISTRATOR, DS) . DS . 'components' . DS . 'com_payzen' . DS . 'classes' . DS . 'PayzenResponse.php');
        }

        $payzen_response = new PayzenResponse(
            $data,
            $method->ctx_mode,
            $method->key_test,
            $method->key_prod,
            $method->sign_algo
        );

        if (! $payzen_response->isAuthentified()) {
            $this->logInfo('plgVmOnPaymentNotification -- suspect request sent to plgVmOnPaymentNotification, IP : ' . $_SERVER['REMOTE_ADDR'], 'error');
            $this->logInfo('Signature algorithm selected in module settings must be the same as one selected in PayZen Back Office.', 'error');

            die($payzen_response->getOutputForGateway('auth_fail'));
        }

        $order = VirtueMartModelOrders::getOrder($virtuemart_order_id);
        $order_status_code = $order['items'][0]->order_status;

        // Order not processed yet.
        if ($order_status_code == 'P') {
            if ($payzen_response->isAcceptedPayment()) {
                $currency = PayzenApi::findCurrencyByNumCode($payzen_response->get('currency'))->getAlpha3();
                $amount = $payzen_response->getFloatAmount() . ' ' . $currency;

                $new_status = $method->order_success_status;

                $this->logInfo('plgVmOnPaymentNotification -- payment process OK, ' . $amount . ' paid for order ' . $payzen_response->get('order_id') . ', new status ' . $new_status, 'message');
                echo $payzen_response->getOutputForGateway('payment_ok');
            } else {
                $new_status = $method->order_failure_status;

                $this->logInfo('plgVmOnPaymentNotification -- payment process error ' . $payzen_response->getLogMessage() . ', new status ' . $new_status, 'error');
                echo $payzen_response->getOutputForGateway('payment_ko');
            }

            // Save platform response.
            $this->managePaymentResponse($virtuemart_order_id, $payzen_response, $new_status, $session_id);
        } else {
            // Order already processed.
            if ($payzen_response->isAcceptedPayment()) {
                echo $payzen_response->getOutputForGateway('payment_ok_already_done');
            } else {
                echo $payzen_response->getOutputForGateway('payment_ko_on_order_ok');
            }
        }

        die();
    }

    /**
     * Display stored payment data for an order.
     *
     * @see components/com_virtuemart/helpers/vmPaymentPlugin::plgVmOnShowOrderPaymentBE()
     */
    function plgVmOnShowOrderBEPayment($virtuemart_order_id, $payment_method_id)
    {
        if (! $this->selectedThisByMethodId($payment_method_id)) {
            return null; // Another method was selected, do nothing.
        }

        if (! ($payment_data = $this->getDataByOrderId($virtuemart_order_id))) {
            return null;
        }

        $html = '<table class="adminlist">' . "\n";
        $html .= $this->getHtmlHeaderBE();
        $html .= $this->getHtmlRowBE(strtoupper($this->_name) . '_PAYMENT_NAME', $payment_data->payment_name);

        $response_trans_id = $this->_name . '_response_trans_id';
        $response_card_number = $this->_name . '_response_card_number';
        $response_payment_mean = $this->_name . '_response_payment_mean';
        $response_payment_message = $this->_name . '_response_payment_message';
        $response_payment_card_brand_choice = $this->_name . '_response_payment_card_brand_choice';
        $response_expiry_month = $this->_name . '_response_expiry_month';
        $response_expiry_year = $this->_name . '_response_expiry_year';

        $expiry = $payment_data->$response_expiry_year ?
            str_pad($payment_data->$response_expiry_month, 2, '0', STR_PAD_LEFT) . ' / ' . $payment_data->$response_expiry_year :
            '-';

        $html .= $this->getHtmlRowBE($this->_name . '_RESULT', $payment_data->$response_payment_message ? $payment_data->$response_payment_message : '-');
        $html .= $this->getHtmlRowBE($this->_name . '_TRANS_ID', $payment_data->$response_trans_id ? $payment_data->$response_trans_id : '-');
        $html .= $this->getHtmlRowBE($this->_name . '_CC_NUMBER', $payment_data->$response_card_number ? $payment_data->$response_card_number : '-');
        $html .= $this->getHtmlRowBE($this->_name . '_CC_EXPIRY', $expiry);
        $response_payment_mean_field = $payment_data->$response_payment_mean ? $payment_data->$response_payment_mean : '-';
        if ($payment_data->$response_payment_card_brand_choice && ($response_payment_mean_field != '-')) {
            $response_payment_mean_field .= ' <b>' . JText::_($payment_data->$response_payment_card_brand_choice) . '<b>';
        }

        $html .= $this->getHtmlRowBE($this->_name . '_CC_TYPE', $response_payment_mean_field);
        $html .= '</table>' . "\n";

        return $html;
    }

    function _getHtmlPaymentResponse($msg, $is_success = true, $order_id = null, $amount = null)
    {
        if (! $is_success) {
            return '<p>' . JText::_($msg) . '</p>';
        } else {
            $html = '<table>' . "\n";
            $html .= '<thead><tr><td colspan="2" style="text-align: center;">' . JText::_($msg) . '</td></tr></thead>';
            $html .= $this->getHtmlRow($this->_name . '_ORDER_NUMBER', $order_id, 'style="width: 90px;" class="key"');
            $html .= $this->getHtmlRow($this->_name . '_AMOUNT', $amount, 'style="width: 90px;" class="key"');
            $html .= '</table>' . "\n";

            return $html;
        }
    }

    function savePaymentData($virtuemart_order_id, $payzen_response)
    {
        $response[$this->_tablepkey] = $this->_getTablepkeyValue($virtuemart_order_id);
        $response['virtuemart_order_id'] = $virtuemart_order_id;
        $response[$this->_name . '_response_payment_amount'] = $payzen_response->getFloatAmount();
        $response[$this->_name . '_response_payment_currency'] = $payzen_response->get('currency');
        $response[$this->_name . '_response_auth_number'] = $payzen_response->get('auth_number');
        $response[$this->_name . '_response_payment_mean'] = $payzen_response->get('card_brand');
        $response[$this->_name . '_response_payment_date'] = gmdate('Y-m-d H:i:s', time());
        $response[$this->_name . '_response_payment_status'] = $payzen_response->getResult();
        $response[$this->_name . '_response_payment_message'] = $payzen_response->getMessage();

        // Save card brand user choice.
        if ($payzen_response->get('brand_management')) {
            $brand_info = json_decode($payzen_response->get('brand_management'));
            $card_brand_choice = '';
            if (isset($brand_info->userChoice) && $brand_info->userChoice) {
                $card_brand_choice = 'VMPAYMENT_' . $this->_name . '_CARD_BRAND_BUYER_CHOICE';
            } else {
                $card_brand_choice = 'VMPAYMENT_' . $this->_name . '_CARD_BRAND_DEFAULT_CHOICE';
            }

            $response[$this->_name . '_response_payment_card_brand_choice'] = $card_brand_choice;
        }

        $response[$this->_name . '_response_card_number'] = $payzen_response->get('card_number');
        $response[$this->_name . '_response_trans_id'] = $payzen_response->get('trans_id');
        $response[$this->_name . '_response_expiry_month'] = $payzen_response->get('expiry_month');
        $response[$this->_name . '_response_expiry_year'] = $payzen_response->get('expiry_year');
        $this->storePSPluginInternalData($response, $this->_tablepkey, true);
    }

    function _getTablepkeyValue($virtuemart_order_id)
    {
        $db = JFactory::getDBO();
        $q = 'SELECT ' . $this->_tablepkey . ' FROM `' . $this->_tablename . '` '
            . 'WHERE `virtuemart_order_id` = ' . $virtuemart_order_id;
        $db->setQuery($q);

        if (! ($pkey = $db->loadResult())) {
            JError::raiseWarning(500, $db->getErrorMsg());
            return '';
        }

        return $pkey;
    }

    function emptyCart($session_id = null, $order_number = null)
    {
        if ($session_id != null) {
            $session = JFactory::getSession();
            $session->destroy();

            // Restore payment session.
            $_COOKIE[session_name()] = $session_id;
            $session->start();
        }

        return parent::emptyCart();
    }

    function managePaymentResponse($virtuemart_order_id, $payzen_response, $new_status, $session_id = null)
    {
        // Save platform response data.
        $this->savePaymentData($virtuemart_order_id, $payzen_response);

        if (! class_exists('VirtueMartModelOrders')) {
            require(VMPATH_ADMIN . DS . 'models' . DS . 'orders.php');
        }

        // Save order data.
        $modelOrder = new VirtueMartModelOrders();
        $order['order_status'] = $new_status;
        $order['virtuemart_order_id'] = $virtuemart_order_id;
        $order['customer_notified'] = 1;

        $date = JFactory::getDate();
        $format = '%Y-%m-%d %H:%M:%S';
        $date_str = method_exists($date, 'format') ? $date->format($format) : $date->toFormat($format);
        $order['comments'] = JText::sprintf('VMPAYMENT_' . $this->_name . '_NOTIFICATION_RECEVEIVED', $date_str);

        // updateStatusForOneOrder function is sending notification e-mail since VM2.0.2
        $modelOrder->updateStatusForOneOrder($virtuemart_order_id, $order, true);

        if ($payzen_response->isAcceptedPayment()) {
            // Empty cart for a successful order.
            $this->emptyCart($session_id);
        }
    }

    /**
     * We must reimplement this triggers for joomla 1.7.
     */

    /**
     * Create the table for this plugin if it does not yet exist.
     * This functions checks if the called plugin is active one.
     * When yes it is calling the standard method to create the tables
     *
     * @author Valérie Isaksen
     */
    function plgVmOnStoreInstallPaymentPluginTable($jplugin_id)
    {
        return $this->onStoreInstallPluginTable($jplugin_id);
    }

    /**
     * This event is fired after the payment method has been selected. It can be used to store
     * additional payment info in the cart.
     *
     * @author Max Milbers
     * @author Valérie isaksen
     *
     * @param VirtueMartCart $cart: the actual cart
     * @return null if the payment was not selected, true if the data is valid, error message if the data is not vlaid
     */
    public function plgVmOnSelectCheckPayment(VirtueMartCart $cart,  &$msg)
    {
        return $this->OnSelectCheck($cart);
    }

    /**
     * plgVmDisplayListFEPayment
     * This event is fired to display the plugin methods in the cart(edit shipment/payment) for example
     *
     * @param object  $cart Cart object
     * @param integer $selected ID of the method selected
     * @return boolean true on succes, false on failures, null when this plugin was not selected.
     * On errors, JError::raiseWarning(or JError::raiseError) must be used to set a message.
     *
     * @author Valerie Isaksen
     * @author Max Milbers
     */
    public function plgVmDisplayListFEPayment(VirtueMartCart $cart, $selected = 0, &$htmlIn)
    {
        return $this->displayListFE($cart, $selected, $htmlIn);
    }

    /**
     * plgVmonSelectedCalculatePricePayment
     * Calculate the price(value, tax_id) of the selected method
     * It is called by the calculator
     * This function does NOT to be reimplemented. If not reimplemented, then the default values from this function are taken.
     *
     * @author Valerie Isaksen
     * @cart: VirtueMartCart the current cart
     * @cart_prices: array the new cart prices
     * @return null if the method was not selected, false if the shiiping rate is not valid any more, true otherwise
     */
    public function plgVmonSelectedCalculatePricePayment(VirtueMartCart $cart, array &$cart_prices, &$cart_prices_name)
    {
        return $this->onSelectedCalculatePrice($cart, $cart_prices, $cart_prices_name);
    }

    /**
     * plgVmOnCheckAutomaticSelectedPayment
     * Checks how many plugins are available. If only one, the user will not have the choice. Enter edit_xxx page
     * The plugin must check first if it is the correct type
     *
     * @author Valerie Isaksen
     * @param VirtueMartCart cart: the cart object
     * @return null if no plugin was found, 0 if more then one plugin was found,  virtuemart_xxx_id if only one plugin is found
     *
     */
    function plgVmOnCheckAutomaticSelectedPayment(VirtueMartCart $cart, array $cart_prices = array(), &$paymentCounter)
    {
        return $this->onCheckAutomaticSelected($cart, $cart_prices, $paymentCounter);
    }

    /**
     * This method is fired when showing the order details in the frontend.
     * It displays the method-specific data.
     *
     * @param integer $order_id The order ID
     * @return mixed null for methods that aren't active, text(HTML) otherwise
     * @author Max Milbers
     * @author Valerie Isaksen
     */
    public function plgVmOnShowOrderFEPayment($virtuemart_order_id, $virtuemart_paymentmethod_id, &$payment_name)
    {
        $this->onShowOrderFE($virtuemart_order_id, $virtuemart_paymentmethod_id, $payment_name);
    }

    /**
     * This method is fired when showing when priting an Order
     * It displays the the payment method-specific data.
     *
     * @param integer $_virtuemart_order_id The order ID
     * @param integer $method_id  method used for this order
     * @return mixed null when for payment methods that were not selected, text(HTML) otherwise
     * @author Valerie Isaksen
     */
    function plgVmonShowOrderPrintPayment($order_number, $method_id)
    {
        return $this->onShowOrderPrint($order_number, $method_id);
    }

    function plgVmSetOnTablePluginParamsPayment($name, $id, &$table)
    {
        return $this->setOnTablePluginParams($name, $id, $table);
    }

    function plgVmDeclarePluginParamsPaymentVM3(&$data)
    {
        return $this->declarePluginParams('payment', $data);
    }
}
