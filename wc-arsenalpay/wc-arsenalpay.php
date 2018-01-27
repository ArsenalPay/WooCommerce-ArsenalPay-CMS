<?php

/*
Plugin Name: ArsenalPay
Plugin URI: https://github.com/ArsenalPay/WooCommerce-ArsenalPay-CMS
Description: Extends WooCommerce with ArsenalPay gateway.
Version: 1.0.3
Author: Arsenal Media Dev.
Author URI: https://arsenalpay.ru
License: GNU General Public License v3.0
License URI: http://www.gnu.org/licenses/gpl-3.0.html
Text Domain: wc-arsenalpay
Domain Path: /languages/
*/

//Tips for plugin developer:
//Don't use any cyrillic symbols in comments to display this code properly in wordpress plugin-editor after clicking on /change the plugin/.

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly
add_action( 'plugins_loaded', 'wc_arsenalpay_init', 0 );

function wc_arsenalpay_init() {
	if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
		return;
	}
	if ( class_exists( 'WC_GW_Arsenalpay' ) ) {
		return;
	}
	/**
	 * Localisation, function responsible for translation
	 */
	load_plugin_textdomain( 'wc-arsenalpay', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

	/**
	 * Gateway class
	 */
	class WC_GW_Arsenalpay extends WC_Payment_Gateway {
		public function __construct() {
			global $woocommerce;
			$this->id                 = 'arsenalpay';
			$this->icon               = apply_filters( 'woocommerce_arsenalpay_icon', '' . plugin_dir_url( __FILE__ ) . 'wc-arsenalpay.png' );
			$this->method_title       = __( 'ArsenalPay', 'woocommerce' );
			$this->method_description = __( 'Allows payments with ArsenalPay gateway', 'woocommerce' );
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
			if ( $this->debug ) {
				$this->loger = new WC_Logger();
			}
			// Add display hook of receipt and save hook for settings:
			add_action( 'woocommerce_receipt_arsenalpay', array( $this, 'receipt_page' ) );
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array(
				$this,
				'process_admin_options'
			) );

			// ArsenalPay callback hook:
			add_action( 'woocommerce_api_wc_gw_arsenalpay', array( $this, 'callback_listener' ) );

			if ( ! $this->is_valid_for_use() ) {
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
			if ( ! in_array( get_woocommerce_currency(), apply_filters( 'woocommerce_arsenalpay_supported_currencies', array( 'RUB' ) ) ) ) {
				return false;
			}

			return true;
		}

		public function init_form_fields() {
			$this->form_fields = array(
				'enabled'     => array(
					'title'   => __( 'Enable/Disable', 'woocommerce' ),
					'type'    => 'checkbox',
					'label'   => __( 'Enable ArsenalPay', 'wc-arsenalpay' ),
					'default' => 'yes'
				),
				'title'       => array(
					'title'       => __( 'Title', 'woocommerce' ),
					'type'        => 'text',
					'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
					'default'     => __( 'ArsenalPay', 'woocommerce' ),
					'desc_tip'    => true,
				),
				'description' => array(
					'title'       => __( 'Description', 'woocommerce' ),
					'type'        => 'text',
					'default'     => __( 'Pay with ArsenalPay.', 'wc-arsenalpay' ),
					'description' => __( 'This controls the description which the user sees during checkout.', 'woocommerce' ),
					'desc_tip'    => true,
				),
				'debug'       => array(
					'title'       => __( 'Debug Log', 'woocommerce' ),
					'type'        => 'checkbox',
					'label'       => __( 'Enable logging', 'woocommerce' ),
					'default'     => 'no',
					'description' => sprintf( __( 'Log ArsenalPay events, such as callback requests', 'wc-arsenalpay' ), wc_get_log_file_path( 'arsenalpay' ) )
				),

				'arsenalpay_widget_id'    => array(
					'title'       => __( 'Widget ID', 'wc-arsenalpay' ),
					'type'        => 'text',
					'description' => __( 'Assigned to merchant for the access to ArsenalPay payment widget. Required.', 'wc-arsenalpay' ),
					'desc_tip'    => true,
				),
				'arsenalpay_widget_key'   => array(
					'title'       => __( 'Widget key', 'wc-arsenalpay' ),
					'type'        => 'text',
					'description' => __( 'Assigned to merchant for the access to ArsenalPay payment widget. Required.', 'wc-arsenalpay' ),
					'desc_tip'    => true,
				),
				'arsenalpay_callback_key' => array(
					'title'       => __( 'Callback key', 'wc-arsenalpay' ),
					'type'        => 'text',
					'description' => __( 'With this key you check a validity of sign that comes with callback payment data. Required.', 'wc-arsenalpay' ),
					'desc_tip'    => true,
				),
				'arsenalpay_ip' => array(
					'title'       => __( 'Allowed IP-address', 'wc-arsenalpay' ),
					'type'        => 'text',
					'description' => __( 'It can be allowed to receive ArsenalPay payment confirmation callback requests only from the ip address pointed out here. Optional.', 'wc-arsenalpay' ),
					'desc_tip'    => true,
				),

			);
		}

		public function admin_options() {
			echo "
                <br>
                <h3>Redirect URL</h3>
                    <ul>
						<li>" . __( 'Callback URL' ) . ": <code>" . get_bloginfo( "url" ) . "/?wc-api=wc_gw_arsenalpay&arsenalpay=callback</code></li>
                    </ul>
                ";

			if ( $this->is_valid_for_use() ) {
				?>
                <h2><?php _e( 'ArsenalPay', 'woocommerce' ); ?></h2>
                <table class="form-table">
					<?php $this->generate_settings_html(); ?>
                </table> <?php
			} else {
				?>
                <div class="inline error"><p>
                        <strong><?php _e( 'Gateway Disabled', 'woocommerce' ); ?></strong>: <?php _e( 'ArsenalPay does not support your store currency.', 'woocommerce' ); ?>
                    </p></div>
				<?php
			}
		}

		public function receipt_page( $order_id ) {
			$order       = wc_get_order( $order_id );
			$user_id     = $order->get_user_id();
			$total       = number_format( $order->get_total(), 2, '.', '' );
			$nonce       = md5( microtime( true ) . mt_rand( 100000, 999999 ) );
			$sign_data   = "$user_id;$order_id;$total;$this->arsenalpay_widget_id;$nonce";
			$widget_sign = hash_hmac( 'sha256', $sign_data, $this->arsenalpay_widget_key );

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

		public function process_payment( $order_id ) {
			$order = wc_get_order( $order_id );

			return array(
				'result'   => 'success',
				'redirect' => $order->get_checkout_payment_url( true )
			);
		}


		function callback_listener() {
			global $woocommerce;
			@ob_clean();
			$callback_params = stripslashes_deep( $_POST );

			$REMOTE_ADDR = $_SERVER["REMOTE_ADDR"];
			$this->log( 'Remote IP: ' . $REMOTE_ADDR );

			$IP_ALLOW = trim($this->arsenalpay_ip);
			if (strlen($IP_ALLOW) > 0 && $IP_ALLOW != $REMOTE_ADDR) {
				$this->log('IP '. $REMOTE_ADDR . ' is not allowed.');
				$this->exitf('ERR');
			}

			if ( ! $this->check_params( $callback_params ) ) {
				$this->exitf( 'ERR' );
			}
			$order    = wc_get_order( $callback_params['ACCOUNT'] );
			$function = $callback_params['FUNCTION'];

			if ( ! $order || empty( $order ) || ! ( $order instanceof WC_Order ) ) {
				if ( $function == "check" ) {
					$this->log( " Order %s doesn't exist. ", $callback_params['ACCOUNT'] );
					$this->exitf( 'NO' );
				}
				$this->exitf( "ERR" );
			}

			if ( ! ( $this->check_sign( $callback_params, $this->arsenalpay_callback_key ) ) ) {
				$this->log( 'Error in callback parameters ERR_INVALID_SIGN' );
				$this->exitf( 'ERR' );
			}

			switch ( $function ) {
				case 'check':
					$this->callback_check( $callback_params, $order, $woocommerce );
					break;

				case 'payment':
					$this->callback_payment( $callback_params, $order, $woocommerce );
					break;

				case 'cancel':
					$this->callback_cancel( $callback_params, $order, $woocommerce );
					break;

				case 'cancelinit':
					$this->callback_cancel( $callback_params, $order, $woocommerce );
					break;

				case 'refund':
					$this->callback_refund( $callback_params, $order, $woocommerce );
					break;

				case 'reverse':
					$this->callback_reverse( $callback_params, $order, $woocommerce );
					break;

				case 'reversal':
					$this->callback_reverse( $callback_params, $order, $woocommerce );
					break;

				case 'hold':
					$this->callback_hold( $callback_params, $order, $woocommerce );
					break;

				default: {
					$order->update_status( 'failed', sprintf( __( 'Payment failed via callback.', 'woocommerce' ) ) );
					$this->log( 'Error in callback' );
					$this->exitf( 'ERR' );
				}
			}
		}

		function callback_check( $callback_params, $order, $woocommerce ) {
			if ( $order->is_paid() || $order->has_status( 'cancelled' ) || $order->has_status( 'refunded' ) ) {
				$this->log( 'Aborting, Order #' . $order->get_id() . ' is ' . $order->get_status() );
				$this->exitf( 'ERR' );
			}
			$isCorrectAmount = ( $order->get_total() == $callback_params['AMOUNT'] && $callback_params['MERCH_TYPE'] == '0' ) ||
			                   ( $order->get_total() > $callback_params['AMOUNT'] && $callback_params['MERCH_TYPE'] == '1' && $order->get_total() == $callback_params['AMOUNT_FULL'] );
			if ( ! $isCorrectAmount ) {
				$this->log( 'Payment error: Amounts do not match (amount ' . $callback_params['AMOUNT'] . ')' );
				// Put this order on-hold for manual checking
				$order->update_status( 'on-hold', sprintf( __( 'Validation error: ArsenalPay amounts do not match (amount %s).', 'woocommerce' ), $callback_params['AMOUNT'] ) );
				$this->exitf( 'ERR' );
			}
			$order->update_status( 'pending', sprintf( __( 'Order number has been checked.', 'wc-arsenalpay' ) ) );
			$order->add_order_note( __( 'Waiting for payment confirmation after checking.', 'wc-arsenalpay' ) );
			$this->exitf( 'YES' );
		}


		function callback_payment( $callback_params, $order, $woocommerce ) {
			if ( $order->is_paid() || $order->has_status( 'cancelled' ) || $order->has_status( 'refunded' ) ) {
				$this->log( 'Aborting, Order #' . $order->get_id() . ' is ' . $order->get_status() );
				$this->exitf( 'ERR' );
			}

			if ( $order->get_total() == $callback_params['AMOUNT'] && $callback_params['MERCH_TYPE'] == '0' ) {
				$order->add_order_note( __( 'Payment completed.', 'wc-arsenalpay' ) );
			} elseif ( $order->get_total() > $callback_params['AMOUNT'] && $callback_params['MERCH_TYPE'] == '1' && $order->get_total() == $callback_params['AMOUNT_FULL'] ) {
				$order->add_order_note( sprintf( __( "Payment received with less amount equal to %s.", 'wc-arsenalpay' ), $callback_params['AMOUNT'] ) );
			} else {
				$this->log( 'Payment error: Amounts do not match (amount ' . $callback_params['AMOUNT'] . ')' );
				// Put this order on-hold for manual checking
				$order->update_status( 'on-hold', sprintf( __( 'Validation error: ArsenalPay amounts do not match (amount %s).', 'woocommerce' ), $callback_params['AMOUNT'] ) );
				$this->exitf( 'ERR' );
			}
			$order->payment_complete();
			$woocommerce->cart->empty_cart();
			$this->exitf( 'OK' );
		}

		function callback_hold( $callback_params, $order, $woocommerce ) {
			if ( ! $order->has_status( 'on-hold' ) && ! $order->has_status( 'pending' ) ) {
				$this->log( 'Aborting, Order #' . $order->get_id() . ' has not been checked.' );
				$this->exitf( 'ERR' );
			}
			$isCorrectAmount = ( $order->get_total() == $callback_params['AMOUNT'] && $callback_params['MERCH_TYPE'] == '0' ) ||
			                   ( $order->get_total() > $callback_params['AMOUNT'] && $callback_params['MERCH_TYPE'] == '1' && $order->get_total() == $callback_params['AMOUNT_FULL'] );
			if ( ! $isCorrectAmount ) {
				$this->log( 'Payment error: Amounts do not match (amount ' . $callback_params['AMOUNT'] . ')' );
				// Put this order on-hold for manual checking
				$order->update_status( 'on-hold', sprintf( __( 'Validation error: ArsenalPay amounts do not match (amount %s).', 'woocommerce' ), $callback_params['AMOUNT'] ) );
				$this->exitf( 'ERR' );
			}
			$order->update_status( 'on-hold', sprintf( __( 'Order number has been holden.', 'wc-arsenalpay' ) ) );
			$order->add_order_note( __( 'Waiting for payment or cancel confirmation after hold request.', 'wc-arsenalpay' ) );
			$this->exitf( 'OK' );
		}

		function callback_cancel( $callback_params, $order, $woocommerce ) {
			if ( ! $order->has_status( 'on-hold' ) && ! $order->has_status( 'pending' ) ) {
				$this->log( 'Aborting, Order #' . $order->get_id() . ' has not been checked status.' );
				$this->exitf( 'ERR' );
			}

			$order->update_status( 'cancelled', sprintf( __( 'Order has been cancelled', 'wc-arsenalpay' ) ) );
			$this->exitf( 'OK' );
		}

		function callback_refund( $callback_params, $order, $woocommerce ) {
			if ( ! $order->is_paid() && ! $order->has_status( 'refunded' ) ) {
				$this->log( 'Aborting, Order #' . $order->get_id() . ' is not paid.' );
				$this->exitf( 'ERR' );
			}

			$arsenalPaidSum = $order->get_total() - $order->get_total_refunded();

			$isCorrectAmount = ( $callback_params['MERCH_TYPE'] == 0 && $arsenalPaidSum >= $callback_params['AMOUNT'] ) ||
			                   ( $callback_params['MERCH_TYPE'] == 1 && $arsenalPaidSum >= $callback_params['AMOUNT'] && $arsenalPaidSum >= $callback_params['AMOUNT_FULL'] );
			if ( ! $isCorrectAmount ) {
				$this->log( "Refund error: Paid amount({$arsenalPaidSum}) < refund amount({$callback_params['AMOUNT']})" );
				$this->exitf( 'ERR' );
			}

			$res = wc_create_refund( array(
				'amount'         => $callback_params['AMOUNT'],
				'reason'         => 'Partition refund via Arsenalpay',
				'order_id'       => $order->get_id(),
				'refund_payment' => false,
			) );
			if ( $res instanceof WP_Error ) {
				$this->log( 'Error during refund: ' . $res->get_error_message() );
				$this->exitf( 'ERR' );
			}
			$this->exitf( 'OK' );
		}

		function callback_reverse( $callback_params, $order, $woocommerce ) {
			if ( ! $order->is_paid() ) {
				$this->log( 'Aborting, Order #' . $order->get_id() . ' is not complete.' );
				$this->exitf( 'ERR' );
			}

			$arsenalPaidSum = $order->get_total() - $order->get_total_refunded();

			$isCorrectAmount = ( $callback_params['MERCH_TYPE'] == 0 && $arsenalPaidSum == $callback_params['AMOUNT'] ) ||
			                   ( $callback_params['MERCH_TYPE'] == 1 && $arsenalPaidSum >= $callback_params['AMOUNT'] && $arsenalPaidSum == $callback_params['AMOUNT_FULL'] );

			if ( ! $isCorrectAmount ) {
				$this->log( 'Reverse error: Amounts do not match (amount ' . $callback_params['AMOUNT'] . ' and ' . $arsenalPaidSum . ')' );
				$this->exitf( 'ERR' );
			}
			$order->update_status( 'refunded', sprintf( __( 'Order has been reversed', 'wc-arsenalpay' ) ) );
			$this->exitf( 'OK' );
		}


		private function check_sign( $callback_params, $pass ) {
			$validSign = ( $callback_params['SIGN'] === md5( md5( $callback_params['ID'] ) .
			                                                 md5( $callback_params['FUNCTION'] ) . md5( $callback_params['RRN'] ) .
			                                                 md5( $callback_params['PAYER'] ) . md5( $callback_params['AMOUNT'] ) . md5( $callback_params['ACCOUNT'] ) .
			                                                 md5( $callback_params['STATUS'] ) . md5( $pass ) ) ) ? true : false;

			return $validSign;
		}

		private function check_params( $callback_params ) {
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
			foreach ( $required_keys as $key ) {
				if ( empty( $callback_params[ $key ] ) || ! array_key_exists( $key, $callback_params ) ) {
					$this->log( 'Error in callback parameters ERR' . $key );

					return false;
				} else {
					$this->log( " $key=$callback_params[$key]" );

				}
			}

			if ( $callback_params['FUNCTION'] != $callback_params['STATUS'] ) {
				$this->log( "Error: FUNCTION ({$callback_params['FUNCTION']} not equal STATUS ({$callback_params['STATUS']})" );

				return false;
			}

			return true;
		}

		/**
		 * Log file path: /wp-content/uploads/wc-logs/log-[a-z0-9]+.log
		 *
		 * @param $msg
		 */
		private function log( $msg ) {
			if ( $this->debug ) {
				$this->loger->log( 'debug', "Arsenalpay: " . $msg );
			}
		}

		public function exitf( $msg ) {
			$this->log( " $msg " );
			echo $msg;
			die;
		}

	}

	/**
	 * Add the ArsenalPay Gateway to WooCommerce
	 **/
	function add_wc_arsenalpay( $methods ) {
		$methods[] = 'WC_GW_ArsenalPay';

		return $methods;
	}

	add_filter( 'woocommerce_payment_gateways', 'add_wc_arsenalpay' );
} 