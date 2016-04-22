<?php
/*
Plugin Name: ArsenalPay
Plugin URI: https://github.com/ArsenalPay/WooCommerce-ArsenalPay-CMS
Description: Extends WooCommerce with ArsenalPay gateway.
Version: 1.0.2
Author: Arsenal Media Dev.
Author URI: https://arsenalpay.ru
License: GNU General Public License v3.0
License URI: http://www.gnu.org/licenses/gpl-3.0.html
Text Domain: wc-arsenalpay
Domain Path: /languages/
*/

//Tips for plugin developer:
//Don't use any cyrillic symbols in comments to display this code properly in wordpress plugin-editor after clicking on /change the plugin/.

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
add_action('plugins_loaded', 'wc_arsenalpay_init', 0);
 
function wc_arsenalpay_init() 
{
    if ( !class_exists( 'WC_Payment_Gateway' ) ) return;
	if(class_exists('WC_GW_Arsenalpay'))
		return;
    /**
     * Localisation, function responsible for translation
     */
    load_plugin_textdomain('wc-arsenalpay', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/'); 

    /**
     * Gateway class
     */
    class WC_GW_Arsenalpay extends WC_Payment_Gateway 
    {
        public function __construct() 
        {
            global $woocommerce;
            $this->id 	          = 'arsenalpay';
            $this->icon = apply_filters( 'woocommerce_arsenalpay_icon', '' . plugin_dir_url(__FILE__) . 'wc-arsenalpay.png' );
            $this->method_title       = __( 'ArsenalPay', 'woocommerce' );
            $this->method_description = __( 'Allows payments with ArsenalPay gateway', 'woocommerce' );
            $this->has_fields         = false;

            // Load settings fields.
            $this->init_form_fields();
            $this->init_settings();

            // Get the settings and load them into variables
            $this->title 		   		   = $this->get_option( 'title' );
            $this->description             = $this->get_option( 'description' );
            $this->debug                   = $this->get_option( 'debug' );
            $this->arsenalpay_token        = $this->get_option( 'arsenalpay_token' );
            $this->arsenalpay_other_code   = $this->get_option( 'arsenalpay_other_code' ); 
            $this->arsenalpay_key	       = $this->get_option( 'arsenalpay_key' );
            $this->arsenalpay_css          = $this->get_option( 'arsenalpay_css' );
            $this->arsenalpay_ip           = $this->get_option( 'arsenalpay_ip' );
            $this->arsenalpay_check_url    = $this->get_option( 'arsenalpay_check_url' );
            $this->arsenalpay_src	       = $this->get_option( 'arsenalpay_src' );
            $this->arsenalpay_frame_url    = $this->get_option( 'arsenalpay_frame_url' ) ;
            $this->arsenalpay_frame_mode   = $this->get_option( 'arsenalpay_frame_mode' ); 
            $this->arsenalpay_frame_params = $this->get_option( 'arsenalpay_frame_params' );

            // Logs
            if ( 'yes' == $this->debug ) 
            {
                $this->log = new WC_Logger();
            }
            // Add display hook of receipt and save hook for settings:
            add_action( 'woocommerce_receipt_arsenalpay', array( $this, 'receipt_page' ) );
            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

            // ArsenalPay callback hook:
            add_action( 'woocommerce_api_wc_gw_arsenalpay', array( $this, 'callback_listener' ) );
            
            if ( ! $this->is_valid_for_use() ) 
            {
				$this->enabled = false;
            }
        }
        
         /**
        * Check if this gateway is enabled and available in the user's country
         *
         * @access public
         * @return bool
         */
        function is_valid_for_use() 
        {
            if ( ! in_array( get_woocommerce_currency(), apply_filters( 'woocommerce_arsenalpay_supported_currencies', array( 'RUB' ) ) ) ) 
            {
                return false;
            }

            return true;
        }

        public function init_form_fields()
        {
            $this->form_fields = array(
                'enabled' => array(
                    'title'   => __( 'Enable/Disable', 'woocommerce' ),
                    'type'    => 'checkbox',
                    'label'   => __( 'Enable ArsenalPay', 'wc-arsenalpay' ),
                    'default' => 'yes'
                    ),
                'title' => array(
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
                'arsenalpay_token' => array(
                    'title'       => __( 'Unique token', 'wc-arsenalpay' ),
                    'type'        => 'text',
                    'description' => __( 'Assigned to merchant for the access to ArsenalPay payment frame. Required.', 'wc-arsenalpay' ),
                    'desc_tip'    => true,			
                    ),
                'arsenalpay_other_code' => array(
                    'title'       => __( 'Other number or code required for making payments', 'wc-arsenalpay' ),
                    'type'        => 'text',
                    'description' => __( 'Not accessible for editing to the user and not displayed if it is set. Optional.', 'wc-arsenalpay' ),
                    'desc_tip'    => true,			
                    ),	
                'arsenalpay_frame_url' => array(
                    'title'       => __( 'Frame URL', 'wc-arsenalpay' ),
                    'type'        => 'text',
                    'description' => __( 'URL-address of ArsenalPay payment frame. Required.', 'wc-arsenalpay' ),
                    'default'     => 'https://arsenalpay.ru/payframe/pay.php',
                    'desc_tip'    => true,
                    ),	
                'arsenalpay_src' => array(
                    'title'       => __( 'src parameter', 'wc-arsenalpay' ),
                    'type'        => 'select',
                    'description' => __( 'Payment type. Possible options: mk - payment from mobile phone (mobile commerce), card - payment by bank card (internet-acquiring). Optional.', 'wc-arsenalpay' ),
                    'desc_tip'    => true,
                    'default'     => 'mk',
                    'options'     => array(
                                    'mk'     => __( 'mk', 'wc-arsenalpay' ),
                                    'card'   => __( 'card', 'wc-arsenalpay' )
                                    )
                    ),
                'arsenalpay_css' => array(
                    'title'       => __( 'css option', 'wc-arsenalpay' ),
                    'type'        => 'text',
                    'description' => __( 'URL of CSS file if exists. Optional.', 'wc-arsenalpay' ),
                    'desc_tip'    => true,
                    ),
                'arsenalpay_key' => array(
                    'title'       => __( 'Sign key', 'wc-arsenalpay' ),
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
                'arsenalpay_check_url' => array(
                    'title'       => __( 'Check URL.', 'wc-arsenalpay' ),
                    'type'        => 'text',
                    'description' => __( 'URL to check the existance of transaction. Optional', 'wc-arsenalpay' ),
                    'desc_tip'    => true,
                    ),
                'arsenalpay_frame_mode' => array(
                    'title'       => __( 'Frame display mode', 'wc-arsenalpay' ),
                    'type'        => 'text',
                    'description' => __( '1 - to display inside a frame, otherwise on fullscreen payment frame page. Optional.', 'wc-arsenalpay' ),
                    'desc_tip'    => true,
                    'default'     => '1',
                    ),
                'arsenalpay_frame_params' => array(
                    'title'       => __( 'Frame parameters', 'wc-arsenalpay' ),
                    'type'        => 'text',
                    'description' => __( 'Parameters of iFrame. Optional.', 'wc-arsenalpay' ),
                    'desc_tip'    => true,
                    'default'	  => 'width = "700" height = "500" border = "0" scrolling = "auto"',
                    ),
                'debug' => array(
                    'title'       => __( 'Debug Log', 'woocommerce' ),
                    'type'        => 'checkbox',
                    'label'       => __( 'Enable logging', 'woocommerce' ),
                    'default'     => 'no',
                    'description' => sprintf( __( 'Log ArsenalPay events, such as callback requests', 'wc-arsenalpay' ), wc_get_log_file_path( 'arsenalpay' ) )
                    ),
                );		
        }

        public function admin_options() 
        {
            echo "
                <br>
                <h3>Redirect URLs</h3>
                    <ul>
                    <li>".__('Success redirect').": <code>".get_bloginfo("url")."/?wc-api=wc_gw_arsenalpay&arsenalpay=success</code></li>
                    <li>".__('Failure redirect').": <code>".get_bloginfo("url")."/?wc-api=wc_gw_arsenalpay&arsenalpay=fail</code></li>
					<li>".__('Callback URL').": <code>".get_bloginfo("url")."/?wc-api=wc_gw_arsenalpay&arsenalpay=callback</code></li>
                    </ul>
                ";
        
            if ( $this->is_valid_for_use() ) 
            {	
                ?>
                <h2><?php _e('ArsenalPay','woocommerce'); ?></h2>
                <table class="form-table">
                <?php $this->generate_settings_html(); ?>
                </table> <?php
            }
            else 
            {	
                ?>
                <div class="inline error"><p><strong><?php _e( 'Gateway Disabled', 'woocommerce' ); ?></strong>: <?php _e( 'ArsenalPay does not support your store currency.', 'woocommerce' ); ?></p></div>
                <?php
            }
        }

        public function receipt_page( $order_id ) 
        {
            $order = wc_get_order( $order_id );
            $total = number_format($order->order_total, 2, '.', '');
			$url_params = array(
				'src' => $this->arsenalpay_src,
				't' => $this->arsenalpay_token,
				'n' => $order_id,
				'a' => $total,
				'msisdn'=> '',
				'css' => $this->arsenalpay_css,
				'frame' => $this->arsenalpay_frame_mode,
                'description' => $order->get_customer_order_notes(),
                'full_name' => strip_tags($order->get_formatted_shipping_full_name()),
                'phone' => $order->billing_phone,
                'email' => $order->billing_email, 
                'address' => strip_tags($order->get_formatted_shipping_address()), 
                'other' => '',
                );
                $f_url = $this->arsenalpay_frame_url;
                $ap_frame_src = $f_url.'?'.http_build_query($url_params, '', '&');
				$f_params = $this->arsenalpay_frame_params;
            // Mark as processing (payment won't be taken until delivery)
            $html = '<iframe name="arspay" src='.$ap_frame_src.' '.$f_params.'></iframe>';
            echo $html;
        }

        public function process_payment( $order_id ) 
        {
            $order = wc_get_order( $order_id );
            return array(
                'result' => 'success',
                'redirect' => add_query_arg('order', $order->id, add_query_arg('key', $order->order_key, get_permalink(woocommerce_get_page_id('pay'))))
                );
        }
        
        function callback_listener()
        {   
            global $woocommerce;
            @ob_clean();
            $ars_callback = stripslashes_deep($_POST);
            if (isset($_GET['arsenalpay']) AND $_GET['arsenalpay'] == 'callback') 
            {
                $wc_order = wc_get_order( $ars_callback['ACCOUNT'] );
                $REMOTE_ADDR = $_SERVER["REMOTE_ADDR"];

                if ( 'yes' == $this->debug ) 
                {
                    $this->log->add( 'arsenalpay', 'Remote IP for order ' . $wc_order->get_order_number() . ": ".$REMOTE_ADDR );
                }
                $IP_ALLOW = $this->arsenalpay_ip;
                if( strlen( $IP_ALLOW ) > 0 && $IP_ALLOW != $REMOTE_ADDR ) 
                {
                    if ( 'yes' == $this->debug ) 
                    {
                        $this->log->add( 'arsenalpay', 'IP %s is not allowed.', $REMOTE_ADDR );
                    }
                    $this->exitf( 'ERR_IP' );

                }

               if( !$wc_order || empty($wc_order) )
                {
                    if( $ars_callback['FUNCTION']=="check" && $ars_callback['FUNCTION']=="check")
                    {
                        if ( 'yes' == $this->debug ) 
                        {
                            $this->log->add( 'arsenalpay', " Order %s doesn't exist. ", $ars_callback['ACCOUNT']);
                        }
                        $this->exitf( 'NO' );
                    }
                    $this->exitf( "ERR_ACCOUNT" );
                }
                if ( $wc_order->has_status( 'completed' ) ) 
                {
                    if ( 'yes' == $this->debug )
                    {
                            $this->log->add( 'arsenalpay', 'Aborting, Order #' . $wc_order->id . ' is already complete.' );
                    }
                    exit;
                }

                $keyArray = array
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
                foreach( $keyArray as $key ) 
                {
                    if( empty( $ars_callback[$key] ) || !array_key_exists( $key, $ars_callback ) )
                    {
                        if ( 'yes' == $this->debug ) 
                        {
                            $this->log->add( 'arsenalpay', 'Error in callback parameters ERR' . $key );
                        }
                        $this->exitf( 'ERR_'.$key );
                    }
                    else 
                    {
                        if ( 'yes' == $this->debug ) 
                        {
                            $this->log->add( 'arsenalpay', " $key=$ars_callback[$key]" );
                        }   
                    }
                }


                $KEY = $this->arsenalpay_key;
     
                // Validate Amount
				$less_amount = false; 
                if ( $wc_order->get_total() == $ars_callback['AMOUNT'] && $ars_callback['MERCH_TYPE'] == '0' )
                {
                    $less_amount = false;
                }
				elseif ( $wc_order->get_total() > $ars_callback['AMOUNT'] && $ars_callback['MERCH_TYPE'] == '1' && $wc_order->get_total() == $ars_callback['AMOUNT_FULL']) {
					$less_amount = true;
				}
				else {
					if ( 'yes' == $this->debug ) 
                    {
                        $this->log->add( 'arsenalpay', 'Payment error: Amounts do not match (amount ' . $ars_callback['AMOUNT'] . ')' );
                    }
                    // Put this order on-hold for manual checking
                    $wc_order->update_status( 'on-hold', sprintf( __( 'Validation error: ArsenalPay amounts do not match (amount %s).', 'woocommerce' ), $ars_callback['AMOUNT'] ) );
                    $this->exitf( 'ERR_AMOUNT' );
				}

                //======================================
                /**
                * Checking validness of the request sign.
                */
                if( !( $this->_checkSign( $ars_callback, $KEY) ) ) 
                {
                     if ( 'yes' == $this->debug ) 
                    {
                        $this->log->add( 'arsenalpay', 'Error in callback parameters ERR_INVALID_SIGN' );
                    }
                    $this->exitf( 'ERR_INVALID_SIGN' );

                }
                if( $ars_callback['FUNCTION'] == "check" && $ars_callback['STATUS'] == "check" )
                {
                    // Check account
                    /*
                            Here is account check procedure
                            Result:
                            YES - account exists
                            NO - account not exists
                    */
					$wc_order->update_status( 'on-hold', sprintf( __( 'Order number has been checked.', 'wc-arsenalpay' ) ) );
					$wc_order->add_order_note( __( 'Waiting for payment confirmation after checking.', 'wc-arsenalpay' ) );
                    $this->exitf( 'YES' );
                } 
                elseif( ( $ars_callback['FUNCTION']=="payment" ) && ( $ars_callback['STATUS'] === "payment" ) )
                {
                    // Payment callback
                    /*
                            Here is callback payment saving procedure
                            Result:
                            OK - success saving
                            ERR - error saving*/
					if ( $less_amount == true ) {
                        $wc_order->update_status( 'processing' );
						$wc_order->add_order_note( sprintf(__( "Payment received with less amount equal to %s.", 'wc-arsenalpay' ), $ars_callback['AMOUNT'] ) );
					    
                    } 
					else {
						$wc_order->add_order_note( __( 'Payment completed.', 'wc-arsenalpay' ) );
                        $wc_order->payment_complete();
					}
                    $woocommerce->cart->empty_cart();
                    $this->exitf('OK');  
                }
                else 
                {   
                    $wc_order->update_status( 'failed', sprintf( __( 'Payment failed via callback.', 'woocommerce' ) ) );
                    if ( 'yes' == $this->debug ) 
                    {
                        $this->log->add( 'arsenalpay', 'Error in callback' );
                    }
                    $this->exitf('ERR');
                }
            }
            else if (isset($_GET['arsenalpay']) AND $_GET['arsenalpay'] == 'success') 
            {  
                $wc_order = wc_get_order($ars_callback['ACCOUNT']);
                $wc_order->add_order_note( __( 'Payer redirected to the success page.', 'woocommerce' ) );
                wp_redirect( $this->get_return_url( $wc_order ) );
                exit;
                
            }
            else if (isset($_GET['arsenalpay']) AND $_GET['arsenalpay'] == 'fail')
            {   
                $wc_order = wc_get_order($ars_callback['ACCOUNT']);
                $wc_order->update_status( 'failed', sprintf( __( 'Payer cancelled the payment or error occured. Redirected to fail page.', 'woocommerce' ) ) );
                wp_redirect($wc_order->get_cancel_order_url());
		exit; 
            }
            
        } 
            
        private function _checkSign( $ars_callback, $pass)
        {
            $validSign = ( $ars_callback['SIGN'] === md5(md5($ars_callback['ID']).
                    md5($ars_callback['FUNCTION']).md5($ars_callback['RRN']).
                    md5($ars_callback['PAYER']).md5($ars_callback['AMOUNT']).md5($ars_callback['ACCOUNT']).
                    md5($ars_callback['STATUS']).md5($pass) ) )? true : false;
            return $validSign; 
        }
            
        public function exitf($msg)
        {
            if ( 'yes' == $this->debug ) 
                {
                    $this->log->add( 'arsenalpay', " $msg " );
                } 
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

    add_filter('woocommerce_payment_gateways', 'add_wc_arsenalpay' );
} 