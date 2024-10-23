<?php
/**
 * ArsenalPay Support for Cart and Checkout blocks.
 *
 * @package WooCommerce ArsenalPay
 */

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

/**
 * ArsenalPay payment method integration
 *
 * @since 3.2.0
 */
final class WC_Gateway_Arsenalpay_Blocks_Support extends AbstractPaymentMethodType {
	
        private $gateway;
        /**
	 * Name of the payment method.
	 *
	 * @var string
	 */
	protected $name = 'arsenalpay';

	/**
	 * Initializes the payment method type.
	 */
	public function initialize() {
		$this->settings = get_option( 'woocommerce_arsenalpay_settings', [] );
                $this->gateway = new WC_GW_Arsenalpay();
	}

	

	/**
	 * Returns if this payment method should be active. If false, the scripts will not be enqueued.
	 *
	 * @return boolean
	 */
	public function is_active() {
		return $this->gateway->is_available();
	}

	/**
	 * Returns an array of scripts/handles to be registered for this payment method.
	 *
	 * @return array
	 */
        public function get_payment_method_script_handles() {
                wp_register_script(
                    'arsenalpay-blocks-integration',
                    plugin_dir_url(dirname(__FILE__)) . 'assets/js/arsenalpay-blocks.js',
                    [
                        'wc-blocks-registry',
                        'wc-settings',
                        'wp-element',
                        'wp-html-entities',
                        'wp-i18n',
                    ],
                    null,
                    true
                );
                if( function_exists( 'wp_set_script_translations' ) ) {            
                    wp_set_script_translations( 'arsenalpay-blocks-integration');

                }
                return [ 'arsenalpay-blocks-integration' ];
        }

	/**
	 * Returns an array of key=>value pairs of data made available to the payment methods script.
	 *
	 * @return array
	 */
	public function get_payment_method_data() {
		return [
			'title'                  => $this->get_setting( 'title' ),
			'description'            => $this->get_setting( 'description' ),
			'supports'               => $this->get_supported_features()
		];
	}
}
