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

defined ('_JEXEC') or die('Restricted access');

if (!class_exists ('vmPSPlugin')) {
	require(JPATH_VM_PLUGINS . DS . 'vmpsplugin.php');
}

$plugin_path = JPATH_ROOT . DS . 'plugins' . DS . 'vmpayment';
if (version_compare (JVERSION, '1.6.0', 'ge')) {
	$plugin_path .= DS . 'payzen';
}
define('JPATH_VM_PAYZEN', $plugin_path);

class plgVMPaymentPayzen extends vmPSPlugin {

	// instance of class
	public static $_this = FALSE;

	function __construct (& $subject, $config) {
		parent::__construct ($subject, $config);

		$this->_loggable = TRUE;
		$this->tableFields = array_keys ($this->getTableSQLFields ());
		$this->_tablepkey = 'id';
		$this->_tableId = 'id';
		$varsToPush = $this->getVarsToPush ();
		$this->setConfigParameterable ($this->_configTableFieldName, $varsToPush);
	}

	protected function getVmPluginCreateTableSQL () {
		return $this->createTableSQL ('Payment ' . $this->_name . ' Table');
	}

	function getTableSQLFields () {

		$SQLfields = array(
				'id'                               => 'int(11) UNSIGNED NOT NULL AUTO_INCREMENT',
				'virtuemart_order_id'              => 'int(1) UNSIGNED',
				'order_number'                     => 'char(64)',
				'virtuemart_paymentmethod_id'      => 'mediumint(1) UNSIGNED',
				'payment_name'                     => 'varchar(5000)',
				'payment_order_total'              => 'decimal(15,5) NOT NULL DEFAULT \'0.00000\'',
				'payment_currency'                 => 'char(3)',
				'cost_per_transaction'             => 'decimal(10,2)',
				'cost_percent_total'               => 'decimal(10,2)',
				'tax_id'                           => 'smallint(1)',
				'payzen_custom'                    => 'varchar(255)',
				'payzen_response_payment_amount'   => 'char(15)',
				'payzen_response_auth_number'      => 'char(10)',
				'payzen_response_payment_currency' => 'char(3)',
				'payzen_response_payment_mean'     => 'char(255)',
				'payzen_response_payment_date'     => 'char(20)',
				'payzen_response_payment_status'   => 'char(3)',
				'payzen_response_payment_message'  => 'char(255)',
				'payzen_response_card_number'      => 'char(50)',
				'payzen_response_trans_id'         => 'char(6)',
				'payzen_response_expiry_month'     => 'char(2)',
				'payzen_response_expiry_year'      => 'char(4)',
		);

		return $SQLfields;
	}

	function getCosts (VirtueMartCart $cart, $method, $cart_prices) {
		if (preg_match ('/%$/', $method->cost_percent_total)) {
			$cost_percent_total = substr ($method->cost_percent_total, 0, -1);
		}
		else {
			$cost_percent_total = $method->cost_percent_total;
		}

		return ($method->cost_per_transaction + ($cart_prices['salesPrice'] * $cost_percent_total * 0.01));
	}

	/**
	 * Reimplementation of vmPaymentPlugin::checkPaymentConditions()
	 *
	 * @param array  $cart_prices all cart prices
	 * @param object $payment payment parameters object
	 * @return bool true if conditions verified
	 */
	function checkConditions ($cart, $method, $cart_prices) {
		$this->convert ($method);
		$amount = $cart_prices['salesPrice'];
		$amount_cond = ($amount >= $method->min_amount && $amount <= $method->max_amount
				|| ($amount >= $method->min_amount && empty($method->max_amount)));

		return $amount_cond;
	}

	function convert ($method) {
		$method->min_amount = (float)$method->min_amount;
		$method->max_amount = (float)$method->max_amount;
	}

	/**
	 * Prepare data and redirect to PayZen payment platform.
	 *
	 * @param object $cart
	 * @param object $order
	 * @return bool|null|void
	 */
	function plgVmConfirmedOrder($cart, $order) {
		if (!($method = $this->getVmPluginMethod ($order['details']['BT']->virtuemart_paymentmethod_id))) {
			return NULL; // another method was selected, do nothing
		}
		if (!$this->selectedThisElement ($method->payment_element)) {
			return FALSE;
		}

		$this->_debug = $method->debug; // enable debug
		$session = JFactory::getSession ();
		$session_id = $session->getId ();

		$this->logInfo ('plgVmOnConfirmedOrderGetPaymentForm -- order number: ' . $order['details']['BT']->order_number, 'message');

		if (!class_exists ('PayzenRequest')) {
			require_once(JPATH_VM_PAYZEN . DS . 'payzen' . DS . 'helpers' . DS . 'PayzenRequest.php');
		}

		$request = new PayzenRequest();

		// set config parameters
		$paramNames = array(
				'platform_url', 'key_test', 'key_prod', 'capture_delay', 'ctx_mode', 'site_id',
				'validation_mode', 'redirect_enabled', 'redirect_success_timeout', 'redirect_success_message',
				'redirect_error_timeout', 'redirect_error_message', 'return_mode'
		);
		foreach ($paramNames as $name) {
			$request->set ($name, $method->$name);
		}

		// set urls
		$url_return = JROUTE::_ (JURI::root () . 'index.php?option=com_virtuemart&view=pluginresponse&task=pluginresponsereceived');
		$uri = JURI::getInstance ($url_return);
		$uri->setVar ('pm', $order['details']['BT']->virtuemart_paymentmethod_id);
		$uri->setVar ('Itemid', JRequest::getInt ('Itemid'));
		$request->set ('url_return', $uri->toString ());

		$url_cancel = JROUTE::_ (JURI::root () . 'index.php?option=com_virtuemart&view=pluginresponse&task=pluginUserPaymentCancel');
		//$url_cancel = JRoute::_('index.php?option=com_virtuemart&view=cart');
		$uri = JURI::getInstance ($url_cancel);
		$uri->setVar ('on', $order['details']['BT']->order_number);
		$uri->setVar ('pm', $order['details']['BT']->virtuemart_paymentmethod_id);
		$uri->setVar ('Itemid', JRequest::getInt ('Itemid'));
		$request->set ('url_cancel', $uri->toString ());

		// set the language code
		$lang = JFactory::getLanguage ();
		$lang->load ('plg_vmpayment_' . $this->_name, JPATH_ADMINISTRATOR);

		$tag = substr ($lang->get ('tag'), 0, 2);
		$language = PayzenApi::isSupportedLanguage($tag) ? $tag : $method->language;
		$request->set ('language', $language);

		// set currency
		if (!class_exists ('VirtueMartModelCurrency')) {
			require(VMPATH_ADMIN . DS . 'models' . DS . 'currency.php');
		}
		$currencyModel = new VirtueMartModelCurrency();
		$currencyObj = $currencyModel->getCurrency ($cart->pricesCurrency);

		$currency =PayzenApi::findCurrencyByNumCode ($currencyObj->currency_numeric_code);
		if ($currency == NULL) {
			$this->logInfo ('plgVmOnConfirmedOrderGetPaymentForm -- Could not find currency numeric code for currency : ' . $currencyObj->currency_numeric_code, 'error');
			vmInfo (JText::_ ('VMPAYMENT_' . $this->_name . '_CURRENCY_NOT_SUPPORTED'));
			return NULL;
		}
		$request->set ('currency', $currency->getNum());

		// payment_cards may be one value or array
		$cards = $method->payment_cards;
		$cards = !is_array ($cards) ? $cards : (in_array ('', $cards) ? '' : implode (';', $cards));
		$request->set ('payment_cards', $cards);

		// available_languages may be one value or array
		$available_languages = $method->available_languages;
		$available_languages = !is_array ($available_languages) ? $available_languages : (in_array ('', $available_languages) ? '' : implode (';', $available_languages));
		$request->set ('available_languages', $available_languages);

		$request->set ('contrib', 'VirtueMart3.x_2.0.3/' . JVERSION . '_' . vmVersion::$RELEASE);

		// set customer info
		$usrBT = $order['details']['BT'];
		$usrST = isset($order['details']['ST']) ? $order['details']['ST'] : $order['details']['BT'];

		$request->set ('cust_email', $usrBT->email);
		$request->set ('cust_id', @$usrBT->virtuemart_user_id);
		$request->set ('cust_title', @$usrBT->title);
		$request->set ('cust_first_name', $usrBT->first_name);
		$request->set ('cust_last_name', $usrBT->last_name);
		$request->set ('cust_address', $usrBT->address_1 . ($usrBT->address_2 ? ' ' . $usrBT->address_2 : ''));
		$request->set ('cust_zip', $usrBT->zip);
		$request->set ('cust_city', $usrBT->city);
		$request->set ('cust_state', @ShopFunctions::getStateByID ($usrBT->virtuemart_state_id));
		$request->set ('cust_country', @ShopFunctions::getCountryByID ($usrBT->virtuemart_country_id, 'country_2_code'));
		$request->set ('cust_phone', $usrBT->phone_1);
		$request->set ('cust_cell_phone', $usrBT->phone_2);

		$request->set ('ship_to_first_name', $usrST->first_name);
		$request->set ('ship_to_last_name', $usrST->last_name);
		$request->set ('ship_to_city', $usrST->city);
		$request->set ('ship_to_street', $usrST->address_1);
		$request->set ('ship_to_street2', $usrST->address_2);
		$request->set ('ship_to_state', @ShopFunctions::getStateByID ($usrST->virtuemart_state_id));
		$request->set ('ship_to_country', @ShopFunctions::getCountryByID ($usrST->virtuemart_country_id, 'country_2_code'));
		$request->set ('ship_to_phone_num', $usrST->phone_1);
		$request->set ('ship_to_zip', $usrST->zip);

		// set order_id
		$request->set ('order_id', $order['details']['BT']->order_number);

		// set the amount to pay
		$exchangeRate = $currencyObj->currency_exchange_rate;
		if($exchangeRate == 0) {
			// not consider exchange rate
			$exchangeRate = 1;
		}

		$amount = $order['details']['BT']->order_total * $exchangeRate;
		$request->set ('amount', $currency->convertAmountToInteger(round($amount, $currencyObj->currency_decimal_place)));

		// 3DS activation according to amount
		$threeds_mpi = null;
		if($method->threeds_min_amount != '' && $amount < $method->threeds_min_amount) {
			$threeds_mpi = '2';
		}
		$request->set('threeds_mpi', $threeds_mpi);

		// prepare data that should be stored in the database
		$dbValues['order_number'] = $order['details']['BT']->order_number;
		$dbValues['virtuemart_paymentmethod_id'] = $cart->virtuemart_paymentmethod_id;
		$dbValues['payment_name'] = $this->renderPluginName ($method, $order);
		$dbValues['payment_order_total'] = $order['details']['BT']->order_total;
		$dbValues['payment_currency'] = $currencyObj->currency_numeric_code;
		$dbValues['cost_per_transaction'] = $method->cost_per_transaction;
		$dbValues['cost_percent_total'] = $method->cost_percent_total;
		$dbValues['tax_id'] = $method->tax_id;
		$dbValues[$this->_name . '_custom'] = $session_id;
		$this->storePSPluginInternalData ($dbValues);

		$this->logInfo ('plgVmOnConfirmedOrderGetPaymentForm -- payment data saved to table ' . $this->_tablename, 'message');

		// echo the redirect form
		$form = '<p>' . JText::_ ('VMPAYMENT_' . $this->_name . '_PLEASE_WAIT') . '</p>';
		$form .= '<p>' . JText::_ ('VMPAYMENT_' . $this->_name . '_CLICK_BUTTON_IF_NOT_REDIRECTED') . '</p>';
		$form .= '<form action="' . $request->get('platform_url') . '" method="POST" name="vm_' . $this->_name . '_form" >';
		$form .= '<input type="image" name="submit" src="' . JURI::base (TRUE) . '/images/stories/virtuemart/payment/' . $this->_name . '.png" alt="' . JText::_ ('VMPAYMENT_' . $this->_name . '_BTN_ALT') . '" title="' . JText::_ ('VMPAYMENT_PAYZEN_BTN_ALT') . '"/>';
		$form .= $request->getRequestHtmlFields ();
		$form .= '</form></div>';
		$form .= '<script type="text/javascript">document.forms["vm_' . $this->_name . '_form"].submit();</script>';

		$this->logInfo ('plgVmOnConfirmedOrderGetPaymentForm -- user redirected to ' . $this->_name, 'message');

		$cart->_confirmDone = FALSE;
		$cart->_dataValidated = FALSE;
		$cart->setCartIntoSession();

		vRequest::setVar('html', $form);
	}

	/**
	 * Check PayZen response, save order if not done by server call and redirect to response page
	 * when client comes back from payment platform.
	 *
	 * @param $html
	 * @return bool|null|string
	 */
	function plgVmOnPaymentResponseReceived (&$html) {
		if (!class_exists ('VirtueMartCart')) {
			require(JPATH_VM_SITE . DS . 'helpers' . DS . 'cart.php');
		}

		// the payment itself should send the parameter needed.
		$virtuemart_paymentmethod_id = JRequest::getInt ('pm', 0);
		$vendorId = 0;
		if (!($method = $this->getVmPluginMethod ($virtuemart_paymentmethod_id))) {
			return NULL; // another method was selected, do nothing
		}

		if (!$this->selectedThisElement ($method->payment_element)) {
			return FALSE;
		}

		$this->_debug = $method->debug; // enable debug
		$this->logInfo ('plgVmOnPaymentResponseReceived -- user returned back from ' . $this->_name, 'message');

		$data = JRequest::get ('request', 2);

		// load API
		if (!class_exists ('PayzenResponse')) {
			require_once(JPATH_VM_PAYZEN . DS . 'payzen' . DS . 'helpers' . DS . 'PayzenResponse.php');
		}

		$payzen_response = new PayzenResponse($data, $method->ctx_mode, $method->key_test, $method->key_prod);

		if (!$payzen_response->isAuthentified ()) {
			$this->logInfo ('plgVmOnPaymentResponseReceived -- suspect request sent to plgVmOnPaymentResponseReceived, IP : ' . $_SERVER['REMOTE_ADDR'], 'error');
			$html = $this->_getHtmlPaymentResponse ('VMPAYMENT_' . $this->_name . '_ERROR_MSG', FALSE);
			return NULL;
		}

		// retrieve order info from database
		if (!class_exists ('VirtueMartModelOrders')) {
			require(VMPATH_ADMIN . DS . 'models' . DS . 'orders.php');
		}
		// $payzen_response->get ('order_id') is the virtuemart order_number
		$virtuemart_order_id = VirtueMartModelOrders::getOrderIdByOrderNumber ($payzen_response->get ('order_id'));

		// order not found
		if (!$virtuemart_order_id) {
			// vmdebug('plgVmOnPaymentResponseReceived ' . $this->_name, $data, $payzen_response->get('order_id'));
			$this->logInfo ('plgVmOnPaymentResponseReceived -- payment check attempted on non existing order : ' . $payzen_response->get ('order_id'), 'error');
			$html = $this->_getHtmlPaymentResponse ('VMPAYMENT_' . $this->_name . '_ERROR_MSG', FALSE);
			return NULL;
		}

		$order = VirtueMartModelOrders::getOrder ($virtuemart_order_id);
		$order_status_code = $order['items'][0]->order_status;

		if ($payzen_response->isAcceptedPayment ()) {
			$currency = PayzenApi::findCurrencyByNumCode ($payzen_response->get ('currency'))->getAlpha3();
			$amount = $payzen_response->getFloatAmount () . ' ' . $currency;
			$html = $this->_getHtmlPaymentResponse ('VMPAYMENT_' . $this->_name . '_SUCCESS_MSG', TRUE, $payzen_response->get ('order_id'), $amount);

			$new_status = $method->order_success_status;
		}
		else {
			$html = $this->_getHtmlPaymentResponse ('VMPAYMENT_' . $this->_name . '_FAILURE_MSG', FALSE);

			$new_status = $method->order_failure_status;
		}

		// order not processed yet
		if ($order_status_code == 'P') {
			$this->logInfo ('plgVmOnPaymentResponseReceived -- check url does not work.', 'warning');

			 if ($method->ctx_mode == 'TEST') {
				// TEST mode warning : check URL not correctly called
				$msg  = '<div style="margin-bottom: 15px;">' . JText::_ ('VMPAYMENT_' . $this->_name . '_CHECK_URL_WARN');
				$msg .= '<br />' . JText::_ ('VMPAYMENT_' . $this->_name . '_CHECK_URL_WARN_DETAILS') . '</div>';

				$msg .= '<div style="margin-bottom: 15px;">' . JText::_ ('VMPAYMENT_' . $this->_name . '_SHOP_TO_PROD_INFO') . '<a href="https://secure.payzen.eu/html/faq/prod" target="_blank">https://secure.payzen.eu/html/faq/prod</a></div>';
				vmWarn ($msg, '');
			}

			$this->managePaymentResponse ($virtuemart_order_id, $payzen_response, $new_status);
		} elseif ($method->ctx_mode == 'TEST') {
			// TEST mode && other site than ALATAK : server URL is correctly configured, just show pass to prod info.
			vmWarn ('<div style="margin-bottom: 15px;">' . JText::_ ('VMPAYMENT_' . $this->_name . '_SHOP_TO_PROD_INFO') . '<a href="https://secure.payzen.eu/html/faq/prod" target="_blank">https://secure.payzen.eu/html/faq/prod</a></div>', '');
		}

		return NULL;
	}

	/**
	 * Process a PayZen payment cancellation.
	 *
	 * @return bool|null
	 */
	function plgVmOnUserPaymentCancel () {
		if (!class_exists ('VirtueMartModelOrders')) {
			require(VMPATH_ADMIN . DS . 'models' . DS . 'orders.php');
		}

		$order_number = JRequest::getString ('on');
		if (!$order_number) {
			return FALSE;
		}
		if (!$virtuemart_order_id = VirtueMartModelOrders::getOrderIdByOrderNumber ($order_number)) {
			return NULL;
		}
		if (!($paymentTable = $this->getDataByOrderId ($virtuemart_order_id))) {
			return NULL;
		}

		$session = JFactory::getSession ();
		$session_id = $session->getId ();
		$field = $this->_name . '_custom';
		if (strcmp ($paymentTable->$field, $session_id) === 0) {
			$this->handlePaymentUserCancel ($virtuemart_order_id);
		}
		return TRUE;
	}

	/**
	 * Check PayZen response, save order and empty cart if payment success when server notification
	 * is received from payment platform.
	 *
	 * @return bool|null
	 */
	function plgVmOnPaymentNotification () {
		// platform params and payment data
		$data = JRequest::get ('post', 2);
		if (!array_key_exists ('vads_order_id', $data) || !isset($data['vads_order_id'])) {
			$this->logInfo ('plgVmOnPaymentNotification -- Another method was selected, do nothing.', 'message');
			return NULL; // Another method was selected, do nothing
		}
		$this->logInfo ('plgVmOnPaymentNotification START ', 'message');

		// retrieve order info from database
		if (!class_exists ('VirtueMartModelOrders')) {
			require(VMPATH_ADMIN . DS . 'models' . DS . 'orders.php');
		}

		// payment params
		$virtuemart_order_id = VirtueMartModelOrders::getOrderIdByOrderNumber ($data['vads_order_id']);
		if (!($payment_data = $this->getDataByOrderId ($virtuemart_order_id))) {
			return FALSE;
		}

		$method = $this->getVmPluginMethod ($payment_data->virtuemart_paymentmethod_id);
		if (!$this->selectedThisElement ($method->payment_element)) {
			return FALSE;
		}

		$this->_debug = $method->debug;
		$custom = $this->_name . '_custom';
		$session_id = $payment_data->$custom;

		// load API
		if (!class_exists ('PayzenResponse')) {
			require(JPATH_VM_PAYZEN . DS . 'payzen' . DS . 'helpers'. DS . 'PayzenResponse.php');
		}

		$payzen_response = new PayzenResponse ($data, $method->ctx_mode, $method->key_test, $method->key_prod);

		if (!$payzen_response->isAuthentified ()) {
			$this->logInfo ('plgVmOnPaymentNotification -- suspect request sent to plgVmOnPaymentNotification, IP : ' . $_SERVER['REMOTE_ADDR'], 'error');

			die($payzen_response->getOutputForPlatform ('auth_fail'));
		}

		$order = VirtueMartModelOrders::getOrder ($virtuemart_order_id);
		$order_status_code = $order['items'][0]->order_status;

		// order not processed yet
		if ($order_status_code == 'P') {
			if ($payzen_response->isAcceptedPayment ()) {
				$currency = PayzenApi::findCurrencyByNumCode ($payzen_response->get ('currency'))->getAlpha3();
				$amount = $payzen_response->getFloatAmount() . ' ' . $currency;

				$new_status = $method->order_success_status;

				$this->logInfo ('plgVmOnPaymentNotification -- payment process OK, ' . $amount . ' paid for order ' . $payzen_response->get ('order_id') . ', new status ' . $new_status, 'message');
				echo ($payzen_response->getOutputForPlatform ('payment_ok'));
			}
			else {
				$new_status = $method->order_failure_status;

				$this->logInfo ('plgVmOnPaymentNotification -- payment process error ' . $payzen_response->getLogMessage() . ', new status ' . $new_status, 'error');
				echo ($payzen_response->getOutputForPlatform ('payment_ko'));
			}

			// save platform response
			$this->managePaymentResponse ($virtuemart_order_id, $payzen_response, $new_status, $session_id);
		}
		else {
			// order already processed
			if ($payzen_response->isAcceptedPayment ()) {
				echo ($payzen_response->getOutputForPlatform ('payment_ok_already_done'));
			}
			else {
				echo ($payzen_response->getOutputForPlatform ('payment_ko_on_order_ok'));
			}
		}

		die();
	}

	/**
	 * Display stored payment data for an order.
	 *
	 * @see components/com_virtuemart/helpers/vmPaymentPlugin::plgVmOnShowOrderPaymentBE()
	 */
	function plgVmOnShowOrderBEPayment ($virtuemart_order_id, $payment_method_id) {
		if (!$this->selectedThisByMethodId ($payment_method_id)) {
			return NULL; // another method was selected, do nothing
		}
		if (!($paymentTable = $this->getDataByOrderId ($virtuemart_order_id))) {
			return NULL;
		}

		$html = '<table class="adminlist">' . "\n";
		$html .= $this->getHtmlHeaderBE ();
		$html .= $this->getHtmlRowBE (strtoupper ($this->_name) . '_PAYMENT_NAME', $paymentTable->payment_name);

		$status = $this->_name . '_response_payment_status';
		$response_trans_id = $this->_name . '_response_trans_id';
		$response_card_number = $this->_name . '_response_card_number';
		$response_payment_mean = $this->_name . '_response_payment_mean';
		$response_payment_message = $this->_name . '_response_payment_message';
		$response_expiry_month = $this->_name . '_response_expiry_month';
		$response_expiry_year = $this->_name . '_response_expiry_year';

		$expiry = $paymentTable->$response_expiry_year ?
					str_pad ($paymentTable->$response_expiry_month, 2, '0', STR_PAD_LEFT) . ' / ' . $paymentTable->$response_expiry_year :
					'-';

		$html .= $this->getHtmlRowBE ($this->_name . '_RESULT', $paymentTable->$response_payment_message ? $paymentTable->$response_payment_message : '-');
		$html .= $this->getHtmlRowBE ($this->_name . '_TRANS_ID', $paymentTable->$response_trans_id ? $paymentTable->$response_trans_id : '-');
		$html .= $this->getHtmlRowBE ($this->_name . '_CC_NUMBER', $paymentTable->$response_card_number ? $paymentTable->$response_card_number : '-');
		$html .= $this->getHtmlRowBE ($this->_name . '_CC_EXPIRY', $expiry);
		$html .= $this->getHtmlRowBE ($this->_name . '_CC_TYPE', $paymentTable->$response_payment_mean ? $paymentTable->$response_payment_mean : '-');
		$html .= '</table>' . "\n";

		return $html;
	}

	function _getHtmlPaymentResponse ($msg, $is_success = TRUE, $order_id = NULL, $amount = NULL) {
		if (!$is_success) {
			return '<p style="text-align: center;">' . JText::_ ($msg) . '</p>';
		}
		else {
			$html = '<table>' . "\n";
			$html .= '<thead><tr><td colspan="2" style="text-align: center;">' . JText::_ ($msg) . '</td></tr></thead>';
			$html .= $this->getHtmlRow ($this->_name . '_ORDER_NUMBER', $order_id, 'style="width: 90px;" class="key"');
			$html .= $this->getHtmlRow ($this->_name . '_AMOUNT', $amount, 'style="width: 90px;" class="key"');
			$html .= '</table>' . "\n";

			return $html;
		}
	}

	function savePaymentData ($virtuemart_order_id, $payzen_response) {
		// vmdebug($this->_name . ' response', $payzen_response->raw_response);
		$response[$this->_tablepkey] = $this->_getTablepkeyValue ($virtuemart_order_id);
		$response['virtuemart_order_id'] = $virtuemart_order_id;
		$response[$this->_name . '_response_payment_amount'] = $payzen_response->getFloatAmount ();
		$response[$this->_name . '_response_payment_currency'] = $payzen_response->get ('currency');
		$response[$this->_name . '_response_auth_number'] = $payzen_response->get ('auth_number');
		$response[$this->_name . '_response_payment_mean'] = $payzen_response->get ('card_brand');
		$response[$this->_name . '_response_payment_date'] = gmdate ('Y-m-d H:i:s', time ());
		$response[$this->_name . '_response_payment_status'] = $payzen_response->getResult();
		$response[$this->_name . '_response_payment_message'] = $payzen_response->getMessage();
		$response[$this->_name . '_response_card_number'] = $payzen_response->get ('card_number');
		$response[$this->_name . '_response_trans_id'] = $payzen_response->get ('trans_id');
		$response[$this->_name . '_response_expiry_month'] = $payzen_response->get ('expiry_month');
		$response[$this->_name . '_response_expiry_year'] = $payzen_response->get ('expiry_year');
		$this->storePSPluginInternalData ($response, $this->_tablepkey, TRUE);
	}

	function _getTablepkeyValue ($virtuemart_order_id) {
		$db = JFactory::getDBO ();
		$q = 'SELECT ' . $this->_tablepkey . ' FROM `' . $this->_tablename . '` '
		. 'WHERE `virtuemart_order_id` = ' . $virtuemart_order_id;
		$db->setQuery ($q);

		if (!($pkey = $db->loadResult ())) {
			JError::raiseWarning (500, $db->getErrorMsg ());
			return '';
		}
		return $pkey;
	}

	function emptyCart ($session_id = NULL, $order_number = NULL) {
		if ($session_id != NULL) {
			$session = JFactory::getSession ();
			$session->destroy();

			// restore payment session
			$_COOKIE[session_name()] = $session_id;
			$session->start();
		}

		return parent::emptyCart();
	}

	function managePaymentResponse ($virtuemart_order_id, $payzen_response, $new_status, $session_id = NULL) {
		// save platform response data
		$this->savePaymentData ($virtuemart_order_id, $payzen_response);

		if (!class_exists ('VirtueMartModelOrders')) {
			require(VMPATH_ADMIN . DS . 'models' . DS . 'orders.php');
		}

		// save order data
		$modelOrder = new VirtueMartModelOrders();
		$order['order_status'] = $new_status;
		$order['virtuemart_order_id'] = $virtuemart_order_id;
		$order['customer_notified'] = 1;

		$date = JFactory::getDate ();
		$format = '%Y-%m-%d %H:%M:%S';
		$date_str = method_exists($date, 'format') ? $date->format ($format) : $date->toFormat ($format);
		$order['comments'] = JText::sprintf ('VMPAYMENT_' . $this->_name . '_NOTIFICATION_RECEVEIVED', $date_str);

		// updateStatusForOneOrder function is sending notification e-mail since VM2.0.2
		$modelOrder->updateStatusForOneOrder ($virtuemart_order_id, $order, TRUE);

		if ($payzen_response->isAcceptedPayment()) {
			// empty cart for a successful order
			$this->emptyCart($session_id);
		}
	}

	/**
	 * We must reimplement this triggers for joomla 1.7
	 */

	/**
	 * Create the table for this plugin if it does not yet exist.
	 * This functions checks if the called plugin is active one.
	 * When yes it is calling the standard method to create the tables
	 *
	 * @author Valérie Isaksen
	 */
	function plgVmOnStoreInstallPaymentPluginTable ($jplugin_id) {
		return $this->onStoreInstallPluginTable ($jplugin_id);
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
	public function plgVmOnSelectCheckPayment (VirtueMartCart $cart,  &$msg) {
		return $this->OnSelectCheck ($cart);
	}

	/**
	 * plgVmDisplayListFEPayment
	 * This event is fired to display the pluginmethods in the cart (edit shipment/payment) for exampel
	 *
	 * @param object  $cart Cart object
	 * @param integer $selected ID of the method selected
	 * @return boolean True on succes, false on failures, null when this plugin was not selected.
	 * On errors, JError::raiseWarning (or JError::raiseError) must be used to set a message.
	 *
	 * @author Valerie Isaksen
	 * @author Max Milbers
	 */
	public function plgVmDisplayListFEPayment (VirtueMartCart $cart, $selected = 0, &$htmlIn) {
		return $this->displayListFE ($cart, $selected, $htmlIn);
	}

	/**
	 * plgVmonSelectedCalculatePricePayment
	 * Calculate the price (value, tax_id) of the selected method
	 * It is called by the calculator
	 * This function does NOT to be reimplemented. If not reimplemented, then the default values from this function are taken.
	 *
	 * @author Valerie Isaksen
	 * @cart: VirtueMartCart the current cart
	 * @cart_prices: array the new cart prices
	 * @return null if the method was not selected, false if the shiiping rate is not valid any more, true otherwise
	 */
	public function plgVmonSelectedCalculatePricePayment (VirtueMartCart $cart, array &$cart_prices, &$cart_prices_name) {
		return $this->onSelectedCalculatePrice ($cart, $cart_prices, $cart_prices_name);
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
	function plgVmOnCheckAutomaticSelectedPayment (VirtueMartCart $cart, array $cart_prices = array(), &$paymentCounter) {
		return $this->onCheckAutomaticSelected ($cart, $cart_prices, $paymentCounter);
	}

	/**
	 * This method is fired when showing the order details in the frontend.
	 * It displays the method-specific data.
	 *
	 * @param integer $order_id The order ID
	 * @return mixed Null for methods that aren't active, text (HTML) otherwise
	 * @author Max Milbers
	 * @author Valerie Isaksen
	 */
	public function plgVmOnShowOrderFEPayment ($virtuemart_order_id, $virtuemart_paymentmethod_id, &$payment_name) {
		$this->onShowOrderFE ($virtuemart_order_id, $virtuemart_paymentmethod_id, $payment_name);
	}

	/**
	 * This method is fired when showing when priting an Order
	 * It displays the the payment method-specific data.
	 *
	 * @param integer $_virtuemart_order_id The order ID
	 * @param integer $method_id  method used for this order
	 * @return mixed Null when for payment methods that were not selected, text (HTML) otherwise
	 * @author Valerie Isaksen
	 */
	function plgVmonShowOrderPrintPayment ($order_number, $method_id) {
		return $this->onShowOrderPrint ($order_number, $method_id);
	}

	function plgVmSetOnTablePluginParamsPayment ($name, $id, &$table) {
		return $this->setOnTablePluginParams ($name, $id, $table);
	}

	function plgVmDeclarePluginParamsPaymentVM3( &$data) {
		return $this->declarePluginParams('payment', $data);
	}
}
