<?php

/*
Plugin Name: ArsenalPay
Plugin URI: https://github.com/ArsenalPay/WooCommerce-ArsenalPay-CMS
Description: Extends WooCommerce with ArsenalPay gateway.
Version: 1.2.0
Author: Arsenal Media Dev.
Author URI: https://arsenalpay.ru
License: GNU General Public License v3.0
License URI: http://www.gnu.org/licenses/gpl-3.0.html
Text Domain: wc-arsenalpay
Domain Path: /languages/
*/

//Tips for plugin developer:
//Don't use any cyrillic symbols in comments to display this code properly in wordpress plugin-editor after clicking on /change the plugin/.

if (!defined('ABSPATH')) {
	exit;
} // Exit if accessed directly
add_action('plugins_loaded', 'wc_arsenalpay_init', 0);

function wc_arsenalpay_init() {
	if (!class_exists('WC_Payment_Gateway')) {
		return;
	}
	if (class_exists('WC_GW_Arsenalpay')) {
		return;
	}
	/**
	 * Localisation, function responsible for translation
	 */
	load_plugin_textdomain('wc-arsenalpay', false, dirname(plugin_basename(__FILE__)) . '/languages/');

	/**
	 * Gateway class
	 */
	class WC_GW_Arsenalpay extends WC_Payment_Gateway {
		private $_callback;

		public function __construct() {
			global $woocommerce;
			$this->id                 = 'arsenalpay';
			$this->icon               = apply_filters('woocommerce_arsenalpay_icon', '' . plugin_dir_url(__FILE__) . 'wc-arsenalpay.png');
			$this->method_title       = __('ArsenalPay', 'woocommerce');
			$this->method_description = __('Allows payments with ArsenalPay gateway', 'woocommerce');
			$this->has_fields         = false;

			// Load settings fields.
			$this->init_form_fields();
			$this->init_settings();

			// Get the settings and load them into variables
			$this->title                   = $this->get_option('title');
			$this->description             = $this->get_option('description');
			$this->debug                   = 'yes' === $this->get_option('debug', 'no');
			$this->arsenalpay_callback_key = $this->get_option('arsenalpay_callback_key');
			$this->arsenalpay_widget_key   = $this->get_option('arsenalpay_widget_key');
			$this->arsenalpay_widget_id    = $this->get_option('arsenalpay_widget_id');
			$this->arsenalpay_ip           = $this->get_option('arsenalpay_ip');

			// Logs
			if ($this->debug) {
				$this->loger = new WC_Logger();
			}
                        
                        // Add display hook of receipt and save hook for settings:
			add_action('woocommerce_receipt_arsenalpay', array($this, 'receipt_page'));
			add_action('woocommerce_update_options_payment_gateways_' . $this->id, array(
				$this,
				'process_admin_options'
			));
                        
			// ArsenalPay callback hook:
			add_action('woocommerce_api_wc_gw_arsenalpay', array($this, 'callback_listener'));

			if (!$this->is_valid_for_use()) {
				$this->enabled = false;
			}
		}

		/**
		 * Check if this gateway is enabled and available in the user's country
		 *
		 * @access public
		 * @return bool
		 */
		function is_valid_for_use() {
			if (!in_array(get_woocommerce_currency(), apply_filters('woocommerce_arsenalpay_supported_currencies', array('RUB')))) {
				return false;
			}

			return true;
		}

		private function _get_arsenalpay_taxes() {
			return array(
				"none"   => "Не облагается",
				"vat0"   => "НДС 0%",
				"vat10"  => "НДС 10%",
				"vat18"  => "НДС 18%",
				"vat110" => "НДС 10/110",
				"vat118" => "НДС 18/118"
			);
		}

		private function _get_all_wc_taxes() {
			global $wpdb;

			$query = "
				SELECT *
				FROM {$wpdb->prefix}woocommerce_tax_rates
				WHERE 1 = 1
			";

			$order_by = ' ORDER BY tax_rate_order';

			$result = $wpdb->get_results($query . $order_by);

			return $result;
		}

		public function init_form_fields() {
			$this->form_fields = array(
				'enabled'     => array(
					'title'   => __('Enable/Disable', 'woocommerce'),
					'type'    => 'checkbox',
					'label'   => __('Enable ArsenalPay', 'wc-arsenalpay'),
					'default' => 'yes'
				),
				'title'       => array(
					'title'       => __('Title', 'woocommerce'),
					'type'        => 'text',
					'description' => __('This controls the title which the user sees during checkout.', 'woocommerce'),
					'default'     => __('ArsenalPay', 'woocommerce'),
					'desc_tip'    => true,
				),
				'description' => array(
					'title'       => __('Description', 'woocommerce'),
					'type'        => 'text',
					'default'     => __('Pay with ArsenalPay.', 'wc-arsenalpay'),
					'description' => __('This controls the description which the user sees during checkout.', 'woocommerce'),
					'desc_tip'    => true,
				),
				'debug'       => array(
					'title'       => __('Debug Log', 'woocommerce'),
					'type'        => 'checkbox',
					'label'       => __('Enable logging', 'woocommerce'),
					'default'     => 'no',
					'description' => __('Log ArsenalPay events, such as callback requests', 'wc-arsenalpay')
				),

				'arsenalpay_widget_id'    => array(
					'title'       => __('Widget ID', 'wc-arsenalpay'),
					'type'        => 'text',
					'description' => __('Assigned to merchant for the access to ArsenalPay payment widget. Required.', 'wc-arsenalpay'),
					'desc_tip'    => true,
				),
				'arsenalpay_widget_key'   => array(
					'title'       => __('Widget key', 'wc-arsenalpay'),
					'type'        => 'text',
					'description' => __('Assigned to merchant for the access to ArsenalPay payment widget. Required.', 'wc-arsenalpay'),
					'desc_tip'    => true,
				),
				'arsenalpay_callback_key' => array(
					'title'       => __('Callback key', 'wc-arsenalpay'),
					'type'        => 'text',
					'description' => __('With this key you check a validity of sign that comes with callback payment data. Required.', 'wc-arsenalpay'),
					'desc_tip'    => true,
				),
				'arsenalpay_ip'           => array(
					'title'       => __('Allowed IP-address', 'wc-arsenalpay'),
					'type'        => 'text',
					'description' => __('It can be allowed to receive ArsenalPay payment confirmation callback requests only from the ip address pointed out here. Optional.', 'wc-arsenalpay'),
					'desc_tip'    => true,
				),
				'default_tax'             => array(
					'title'       => __('Default tax', 'wc-arsenalpay'),
					'type'        => 'select',
					'options'     => $this->_get_arsenalpay_taxes(),
					'description' => __('The tax rate will default to check if the product is not specified a different rate.', 'wc-arsenalpay'),
					'desc_tip'    => false,
				),
			);

			$WC_taxes = $this->_get_all_wc_taxes();
			if (count($WC_taxes) > 0) {
			    $this->form_fields["tax_title"] = array(
					"title" => __('On the left is the tax rate in your store, right in the Federal Tax Service. Match them.', 'wc-arsenalpay'),
					"type"  => 'title',
				);
            }
			foreach ($WC_taxes as $wc_tax) {
				$option_name                     = $this->_get_option_name_for_tax($wc_tax->tax_rate_id);
				$this->form_fields[$option_name] = array(
					"title"       => $wc_tax->tax_rate_name . " (" . round($wc_tax->tax_rate) . "%)",
					"type"        => 'select',
					"options"     => $this->_get_arsenalpay_taxes(),
				);

			}
		}

		private function _get_option_name_for_tax($tax_id) {
			return "wc_tax_" . $tax_id;
		}

		public function admin_options() {
			echo "
                <br>
                <h3>Redirect URL</h3>
                    <ul>
						<li>" . __('Callback URL') . ": <code>" . get_bloginfo("url") . "/?wc-api=wc_gw_arsenalpay&arsenalpay=callback</code></li>
                    </ul>
                ";

			if ($this->is_valid_for_use()) {
				?>
                <h2><?php _e('ArsenalPay', 'woocommerce'); ?></h2>
                <table class="form-table">
					<?php $this->generate_settings_html(); ?>
                </table> <?php
			}
			else {
				?>
                <div class="inline error"><p>
                        <strong><?php _e('Gateway Disabled', 'woocommerce'); ?></strong>: <?php _e('ArsenalPay does not support your store currency.', 'woocommerce'); ?>
                    </p></div>
				<?php
			}
		}

		public function receipt_page($order_id) {
			$order       = wc_get_order($order_id);
			$user_id     = $order->get_user_id();
			$total       = number_format($order->get_total(), 2, '.', '');
			$nonce       = md5(microtime(true) . mt_rand(100000, 999999));
			$sign_data   = "$user_id;$order_id;$total;$this->arsenalpay_widget_id;$nonce";
			$widget_sign = hash_hmac('sha256', $sign_data, $this->arsenalpay_widget_key);

			$html = "
				<script src='https://arsenalpay.ru/widget/script.js'></script>
				<div id='app-widget'></div>
				<script>
					var APWidget = new ArsenalpayWidget({
						element: 'app-widget',
						destination: \"{$order_id}\",
						widget: \"{$this->arsenalpay_widget_id}\",
						amount: \"{$total}\",
						userId: \"{$user_id}\",
						nonce: \"{$nonce}\",
						widgetSign: \"{$widget_sign}\"
					});
					APWidget.render();
				</script>";
			echo $html;
		}

		public function process_payment($order_id) {
			$order = wc_get_order($order_id);

			return array(
				'result'   => 'success',
				'redirect' => $order->get_checkout_payment_url(true)
			);
		}


		function callback_listener() {
			global $woocommerce;
			@ob_clean();
			$callback_params = stripslashes_deep($_POST);

			$REMOTE_ADDR = $_SERVER["REMOTE_ADDR"];
			//$this->log('Remote IP: ' . $REMOTE_ADDR . ' with POST params: ' . json_encode($callback_params));

			$IP_ALLOW = trim($this->arsenalpay_ip);
			if (strlen($IP_ALLOW) > 0 && $IP_ALLOW != $REMOTE_ADDR) {
				$this->log('IP ' . $REMOTE_ADDR . ' is not allowed.');
				$this->exitf('ERR');
			}
                        

			if (!$this->check_params($callback_params)) {
				$this->exitf('ERR');
			}
			$this->_callback = $callback_params;
			$order           = wc_get_order($this->_callback['ACCOUNT']);
			$function        = $this->_callback['FUNCTION'];

			if (!$order || empty($order) || !($order instanceof WC_Order)) {
				if ($function == "check") {
					$this->log(" Order %s doesn't exist. ", $this->_callback['ACCOUNT']);
					$this->exitf('NO');
				}
				$this->exitf("ERR");
			}

			if (!($this->check_sign($this->_callback, $this->arsenalpay_callback_key))) {
				$this->log('Error in callback parameters ERR_INVALID_SIGN');
				$this->exitf('ERR');
			}

			switch ($function) {
				case 'check':
					$this->callback_check($order, $woocommerce);
					break;

				case 'payment':
					$this->callback_payment($order, $woocommerce);
					break;

				case 'cancel':
					$this->callback_cancel($order, $woocommerce);
					break;

				case 'cancelinit':
					$this->callback_cancel($order, $woocommerce);
					break;

				case 'refund':
					$this->callback_refund($order, $woocommerce);
					break;

				case 'reverse':
					$this->callback_reverse($order, $woocommerce);
					break;

				case 'reversal':
					$this->callback_reverse($order, $woocommerce);
					break;

				case 'hold':
					$this->callback_hold($order, $woocommerce);
					break;

				default: {
					$order->update_status('failed', sprintf(__('Payment failed via callback.', 'woocommerce')));
					$this->log('Error in callback');
					$this->exitf('ERR');
				}
			}
		}

		private function callback_check($order, $woocommerce) {
			if ($order->is_paid() || $order->has_status('cancelled') || $order->has_status('refunded')) {
				$this->log('Aborting, Order #' . $order->get_id() . ' is ' . $order->get_status());
				$this->exitf('ERR');
			}
			$isCorrectAmount = ($order->get_total() == $this->_callback['AMOUNT'] && $this->_callback['MERCH_TYPE'] == '0') ||
			                   ($order->get_total() > $this->_callback['AMOUNT'] && $this->_callback['MERCH_TYPE'] == '1' && $order->get_total() == $this->_callback['AMOUNT_FULL']);
			if (!$isCorrectAmount) {
				$this->log('Payment error: Amounts do not match (amount ' . $this->_callback['AMOUNT'] . ')');
				// Put this order on-hold for manual checking
				$order->update_status('on-hold', sprintf(__('Validation error: ArsenalPay amounts do not match (amount %s).', 'woocommerce'), $this->_callback['AMOUNT']));
				$this->exitf('ERR');
			}

			$fiscal = array();
			if (isset($this->_callback['OFD']) && $this->_callback['OFD'] == 1) {
				$fiscal = $this->prepareFiscalDocument($order);
				if (!$fiscal) {
					$this->log('Error during preparing fiscal document!');
					$this->exitf('ERR');
				}
			}
			$order->update_status('pending', sprintf(__('Order number has been checked.', 'wc-arsenalpay')));
			$order->add_order_note(__('Waiting for payment confirmation after checking.', 'wc-arsenalpay'));
			$this->exitf('YES', $fiscal);
		}


		private function preparePhone($phone) {
			$phone = preg_replace('/[^0-9]/', '', $phone);
			if (strlen($phone) < 10) {
				return false;
			}
			if (strlen($phone) == 10) {
				return $phone;
			}
			if (strlen($phone) == 11) {
				return substr($phone, 1);
			}

			return false;

		}

		private function _get_arsenalpay_tax_rate($taxes) {
			$default_tax_rate = $this->get_option('default_tax');
			$taxes_subtotal   = $taxes['total'];
			if ($taxes_subtotal) {
				$wc_tax_ids          = array_keys($taxes_subtotal);
				$wc_tax_id           = $wc_tax_ids[0];
				$arsenalpay_tax_rate = $this->get_option($this->_get_option_name_for_tax($wc_tax_id));
				if ($arsenalpay_tax_rate) {
					return $arsenalpay_tax_rate;
				}
			}

			return $default_tax_rate;
		}

		/**
		 * @param $order WC_Order
		 *
		 * @return array
		 */
		private function prepareFiscalDocument($order) {
			$fiscal = array(
				"id"      => $this->_callback['ID'],
				"type"    => "sell",
				"receipt" => [
					"attributes" => [
						"email" => $order->get_billing_email(),
					],
					"items"      => array(),
				]

			);

			$phone = $this->preparePhone($order->get_billing_phone());
			if ($phone) {
				$fiscal['receipt']['attributes']['phone'] = $phone;
			}
			/**
			 * @var $line_item WC_Order_Item
			 */
			foreach ($order->get_items('line_item') as $line_item) {
				$item = array(
					"name"     => $line_item->get_name(),
					"quantity" => $line_item->get_quantity(),
					"price"    => $order->get_item_total($line_item, true, true),
					"sum"      => $order->get_line_total($line_item, true, true),
				);
				$tax  = $this->_get_arsenalpay_tax_rate($line_item->get_taxes());
				if ($tax) {
					$item['tax'] = $tax;
				}
				$fiscal['receipt']['items'][] = $item;
			}
			/**
			 * @var $shipping WC_Order_Item
			 */
			foreach ($order->get_items('shipping') as $shipping) {
				$item = array(
					"name"     => $shipping->get_name(),
					"quantity" => $shipping->get_quantity(),
					"price"    => $order->get_item_total($shipping, true, true),
					"sum"      => $order->get_line_total($shipping, true, true),
				);
				$tax  = $this->_get_arsenalpay_tax_rate($shipping->get_taxes());
				if ($tax) {
					$item['tax'] = $tax;
				}
				$fiscal['receipt']['items'][] = $item;
			}

			return $fiscal;
		}

		private function callback_payment($order, $woocommerce) {
			if ($order->is_paid() || $order->has_status('cancelled') || $order->has_status('refunded')) {
				$this->log('Aborting, Order #' . $order->get_id() . ' is ' . $order->get_status());
				$this->exitf('ERR');
			}

			if ($order->get_total() == $this->_callback['AMOUNT'] && $this->_callback['MERCH_TYPE'] == '0') {
				$order->add_order_note(__('Payment completed.', 'wc-arsenalpay'));
			}
            elseif ($order->get_total() > $this->_callback['AMOUNT'] && $this->_callback['MERCH_TYPE'] == '1' && $order->get_total() == $this->_callback['AMOUNT_FULL']) {
				$order->add_order_note(sprintf(__("Payment received with less amount equal to %s.", 'wc-arsenalpay'), $this->_callback['AMOUNT']));
			}
			else {
				$this->log('Payment error: Amounts do not match (amount ' . $this->_callback['AMOUNT'] . ')');
				// Put this order on-hold for manual checking
				$order->update_status('on-hold', sprintf(__('Validation error: ArsenalPay amounts do not match (amount %s).', 'woocommerce'), $this->_callback['AMOUNT']));
				$this->exitf('ERR');
			}
			$order->payment_complete();
			$woocommerce->cart->empty_cart();
			$this->exitf('OK');
		}

		private function callback_hold($order, $woocommerce) {
			if (!$order->has_status('on-hold') && !$order->has_status('pending')) {
				$this->log('Aborting, Order #' . $order->get_id() . ' has not been checked.');
				$this->exitf('ERR');
			}
			$isCorrectAmount = ($order->get_total() == $this->_callback['AMOUNT'] && $this->_callback['MERCH_TYPE'] == '0') ||
			                   ($order->get_total() > $this->_callback['AMOUNT'] && $this->_callback['MERCH_TYPE'] == '1' && $order->get_total() == $this->_callback['AMOUNT_FULL']);
			if (!$isCorrectAmount) {
				$this->log('Payment error: Amounts do not match (amount ' . $this->_callback['AMOUNT'] . ')');
				// Put this order on-hold for manual checking
				$order->update_status('on-hold', sprintf(__('Validation error: ArsenalPay amounts do not match (amount %s).', 'woocommerce'), $this->_callback['AMOUNT']));
				$this->exitf('ERR');
			}
			$order->update_status('on-hold', sprintf(__('Order number has been holden.', 'wc-arsenalpay')));
			$order->add_order_note(__('Waiting for payment or cancel confirmation after hold request.', 'wc-arsenalpay'));
			$this->exitf('OK');
		}

		private function callback_cancel($order, $woocommerce) {
			if (!$order->has_status('on-hold') && !$order->has_status('pending')) {
				$this->log('Aborting, Order #' . $order->get_id() . ' has not been checked status.');
				$this->exitf('ERR');
			}

			$order->update_status('cancelled', sprintf(__('Order has been cancelled', 'wc-arsenalpay')));
			$this->exitf('OK');
		}

		private function callback_refund($order, $woocommerce) {
			if (!$order->is_paid() && !$order->has_status('refunded')) {
				$this->log('Aborting, Order #' . $order->get_id() . ' is not paid.');
				$this->exitf('ERR');
			}

			$arsenalPaidSum = $order->get_total() - $order->get_total_refunded();

			$isCorrectAmount = ($this->_callback['MERCH_TYPE'] == 0 && $arsenalPaidSum >= $this->_callback['AMOUNT']) ||
			                   ($this->_callback['MERCH_TYPE'] == 1 && $arsenalPaidSum >= $this->_callback['AMOUNT'] && $arsenalPaidSum >= $this->_callback['AMOUNT_FULL']);
			if (!$isCorrectAmount) {
				$this->log("Refund error: Paid amount({$arsenalPaidSum}) < refund amount({$this->_callback['AMOUNT']})");
				$this->exitf('ERR');
			}

			$res = wc_create_refund(array(
				'amount'         => $this->_callback['AMOUNT'],
				'reason'         => 'Partition refund via Arsenalpay',
				'order_id'       => $order->get_id(),
				'refund_payment' => false,
			));
			if ($res instanceof WP_Error) {
				$this->log('Error during refund: ' . $res->get_error_message());
				$this->exitf('ERR');
			}
			$this->exitf('OK');
		}

		private function callback_reverse($order, $woocommerce) {
			if (!$order->is_paid()) {
				$this->log('Aborting, Order #' . $order->get_id() . ' is not complete.');
				$this->exitf('ERR');
			}

			$arsenalPaidSum = $order->get_total() - $order->get_total_refunded();

			$isCorrectAmount = ($this->_callback['MERCH_TYPE'] == 0 && $arsenalPaidSum == $this->_callback['AMOUNT']) ||
			                   ($this->_callback['MERCH_TYPE'] == 1 && $arsenalPaidSum >= $this->_callback['AMOUNT'] && $arsenalPaidSum == $this->_callback['AMOUNT_FULL']);

			if (!$isCorrectAmount) {
				$this->log('Reverse error: Amounts do not match (amount ' . $this->_callback['AMOUNT'] . ' and ' . $arsenalPaidSum . ')');
				$this->exitf('ERR');
			}
			$order->update_status('refunded', sprintf(__('Order has been reversed', 'wc-arsenalpay')));
			$this->exitf('OK');
		}


		private function check_sign($callback_params, $pass) {
			$validSign = ($callback_params['SIGN'] === md5(md5($callback_params['ID']) .
			                                               md5($callback_params['FUNCTION']) . md5($callback_params['RRN']) .
			                                               md5($callback_params['PAYER']) . md5($callback_params['AMOUNT']) . md5($callback_params['ACCOUNT']) .
			                                               md5($callback_params['STATUS']) . md5($pass))) ? true : false;

			return $validSign;
		}

		private function check_params($callback_params) {
			$required_keys = array
			(
				'ID',           /* Merchant identifier */
				'FUNCTION',     /* Type of request to which the response is received*/
				'RRN',          /* Transaction identifier */
				'PAYER',        /* Payer(customer) identifier */
				'AMOUNT',       /* Payment amount */
				'ACCOUNT',      /* Order number */
				'STATUS',       /* When /check/ - response for the order number checking, when
									// payment/ - response for status change.*/
				'DATETIME',     /* Date and time in ISO-8601 format, urlencoded.*/
				'SIGN',         /* Response sign  = md5(md5(ID).md(FUNCTION).md5(RRN).md5(PAYER).md5(AMOUNT).
									// md5(ACCOUNT).md(STATUS).md5(PASSWORD)) */
			);

			/**
			 * Checking the absence of each parameter in the post request.
			 */
			foreach ($required_keys as $key) {
				if (empty($callback_params[$key]) || !array_key_exists($key, $callback_params)) {
					$this->log('Error in callback parameters ERR' . $key);

					return false;
				}
				else {
					$this->log(" $key=$callback_params[$key]");

				}
			}

			if ($callback_params['FUNCTION'] != $callback_params['STATUS']) {
				$this->log("Error: FUNCTION ({$callback_params['FUNCTION']} not equal STATUS ({$callback_params['STATUS']})");

				return false;
			}

			return true;
		}

		/**
		 * Log file path: /wp-content/uploads/wc-logs/log-[a-z0-9]+.log
		 *
		 * @param $msg
		 */
		private function log($msg) {
			if ($this->debug) {
				$this->loger->log('debug', "Arsenalpay: " . $msg);
			}
		}

		public function exitf($msg, $ofd = array()) {

			if (isset($this->_callback['FORMAT']) && $this->_callback['FORMAT'] == 'json') {
				$response = array("response" => $msg);
				if (isset($this->_callback['OFD']) && $this->_callback['OFD'] == 1 && $ofd) {
					$response['ofd'] = $ofd;
				}
				$msg = json_encode($response);
			}

			$this->log(" $msg ");
			echo $msg;
			die;
		}


	}

	/**
	 * Add the ArsenalPay Gateway to WooCommerce
	 **/
	function add_wc_arsenalpay($methods) {
		$methods[] = 'WC_GW_ArsenalPay';
		return $methods;
	}

	add_filter('woocommerce_payment_gateways', 'add_wc_arsenalpay');
        
        /**
        * Support Cart and Checkout blocks from WooCommerce Blocks.
        */
        function woocommerce_arsenalpay_blocks_support() {
                if ( class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
                        require_once dirname( __FILE__ ) . '/includes/class-wc-gateway-arsenalpay-blocks-support.php';
                        add_action(
                                'woocommerce_blocks_payment_method_type_registration',
                                function( Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {
                                        $payment_method_registry->register( new WC_Gateway_Arsenalpay_Blocks_Support() );
                                }
                        );
                }
       }
       
       add_action( 'before_woocommerce_init', 'woocommerce_arsenalpay_blocks_support');
}
