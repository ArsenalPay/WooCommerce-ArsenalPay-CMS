<?php
/*
Plugin Name: ArsenalPay
Author: ArsenalPay
Author URI: https://arsenalpay.ru
Description: Extends WooCommerce with ArsenalPay gateway.
Version: 1.0.0
 
	License: GNU General Public License v3.0
	License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
add_action('plugins_loaded', 'woocommerce_gateway_arsenalpay_init', 0);
 
function woocommerce_gateway_arsenalpay_init() 
{
    if ( !class_exists( 'WC_Payment_Gateway' ) ) return;

    /**
     * Localisation
     */
    load_plugin_textdomain('wc-gateway-arsenalpay', false, dirname( plugin_basename( __FILE__ ) ) . '/languages');

    /**
     * Gateway class
     */
    class WC_Gateway_Arsenalpay extends WC_Payment_Gateway 
    {
        public function __construct() 
        {
            global $woocommerce;
            $this->id 	          = 'arsenalpay';
            $this->icon = apply_filters( 'woocommerce_arsenalpay_icon', '' . plugin_dir_url(__FILE__) . 'arsenalpay.png' );
            $this->method_title       = __( 'ArsenalPay', 'woocommerce' );
            $this->method_description = __( 'Allows payments with ArsenalPay gateway', 'woocommerce' );
            $this->has_fields         = false;

            // Load settings fields.
            $this->init_form_fields();
            $this->init_settings();

            // Get the settings and load them into variables
            $this->title 		   = $this->get_option( 'title' );
            $this->description             = $this->get_option( 'description' );
            $this->debug                   = $this->get_option( 'debug' );
            $this->arsenalpay_token        = $this->get_option( 'arsenalpay_token' );
            $this->arsenalpay_other_code   = $this->get_option( 'arsenalpay_other_code' ); 
            $this->arsenalpay_key	   = $this->get_option( 'arsenalpay_key' );
            $this->arsenalpay_css          = $this->get_option( 'arsenalpay_css' );
            $this->arsenalpay_ip           = $this->get_option( 'arsenalpay_ip' );
            $this->arsenalpay_callback_url = $this->get_option( 'arsenalpay_callback_url' );
            $this->arsenalpay_check_url    = $this->get_option( 'arsenalpay_check_url' );
            $this->arsenalpay_src	   = $this->get_option( 'arsenalpay_src' );
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
            add_action( 'woocommerce_api_wc_gateway_arsenalpay', array( $this, 'callback_listener' ) );
            
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
                    'label'   => __( 'Enable ArsenalPay', 'woocommerce' ),
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
                    'default'     => __( 'Pay via ArsenalPay with your bank card or mobile phone number.', 'woocommerce' ),
                    'description' => __( 'This controls the description which the user sees during checkout.', 'woocommerce' ),
                    'desc_tip'    => true,
                    ),
                'arsenalpay_token' => array(
                    'title'       => __( 'Unique token', 'woocommerce' ),
                    'type'        => 'text',
                    'description' => __( 'Assigned to merchant for the access to ArsenalPay payment frame, required', 'woocommerce' ),
                    'desc_tip'    => true,			
                    ),
                'arsenalpay_other_code' => array(
                    'title'       => __( 'Other number or code requered for making payments.', 'woocommerce' ),
                    'type'        => 'text',
                    'description' => __( 'Not accessible for editing to the user and not displayed if it is set, optional.', 'woocommerce' ),
                    'desc_tip'    => true,			
                    ),
                'arsenalpay_key' => array(
                    'title'       => __( 'Sign key', 'woocommerce' ),
                    'type'        => 'text',
                    'description' => __( 'Key to check the request sign, required.', 'woocommerce' ),
                    'desc_tip'    => true,
                    ),
                'arsenalpay_css' => array(
                    'title'       => __( 'css option', 'woocommerce' ),
                    'type'        => 'text',
                    'description' => __( 'URL of CSS file, optional.', 'woocommerce' ),
                    'desc_tip'    => true,
                    ),
                'arsenalpay_ip' => array(
                    'title'       => __( 'IP-address', 'woocommerce' ),
                    'type'        => 'text',
                    'description' => __( 'Allowed IP-address to accept ArsenalPay requests from, required.', 'woocommerce' ),
                    'desc_tip'    => true,
                    ),
                'arsenalpay_callback_url' => array(
                    'title'       => __( 'Callback URL', 'woocommerce' ),
                    'type'        => 'text',
                    'description' => __( 'Callback URL of payment, required.', 'woocommerce' ),
                    'desc_tip'    => true,
                    'default'     => WC()->api_request_url( 'WC_Gateway_ArsenalPay' ),
                    ),
                'arsenalpay_check_url' => array(
                    'title'       => __( 'Check URL.', 'woocommerce' ),
                    'type'        => 'text',
                    'description' => __( 'URL to check the existance of transaction, optional', 'woocommerce' ),
                    'desc_tip'    => true,
                    ),
                'arsenalpay_src' => array(
                    'title'       => __( 'Src parameter', 'woocommerce' ),
                    'type'        => 'select',
                    'description' => __( 'Payment type. Possible options: �mk� - payment from mobile phone (mobile commerce), �card� - payment by bank card (internet-acquiring), optional.', 'woocommerce' ),
                    'desc_tip'    => true,
                    'default'     => 'mk',
                    'options'     => array(
                                    'mk'     => __( 'mk', 'woocommerce' ),
                                    'card'   => __( 'card', 'woocommerce' )
                                    )
                    ),
                'arsenalpay_frame_url' => array(
                    'title'       => __( 'Frame URL', 'woocommerce' ),
                    'type'        => 'text',
                    'description' => __( 'URL-address of ArsenalPay payment frame, required.', 'woocommerce' ),
                    'default'     => 'https://arsenalpay.ru/payframe/pay.php',
                    'desc_tip'    => true,
                    ),
                'arsenalpay_frame_mode' => array(
                    'title'       => __( 'Frame display mode', 'woocommerce' ),
                    'type'        => 'text',
                    'description' => __( '"1"-to display inside a frame, otherwise fullscreen.', 'woocommerce' ),
                    'desc_tip'    => true,
                    'default'     => '1',
                    ),
                'arsenalpay_frame_params' => array(
                    'title'       => __( 'Frame parameters', 'woocommerce' ),
                    'type'        => 'text',
                    'description' => __( 'Parameters of iFrame.', 'woocommerce' ),
                    'desc_tip'    => true,
                    'default'	  => 'width = "700" height = "500" border = "0" scrolling = "auto"',
                    ),
                'debug' => array(
                    'title'       => __( 'Debug Log', 'woocommerce' ),
                    'type'        => 'checkbox',
                    'label'       => __( 'Enable logging', 'woocommerce' ),
                    'default'     => 'no',
                    'description' => sprintf( __( 'Log ArsenalPay events, such as callback requests', 'woocommerce' ), wc_get_log_file_path( 'arsenalpay' ) )
                    ),
                );		
        }

        public function admin_options() 
        {
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
            // Mark as processing (payment won't be taken until delivery)
            $html = '<iframe name="arspay" src='.$this->arsenalpay_frame_url.'?src='.$this->arsenalpay_src.'&t='.$this->arsenalpay_token.'&n='.$order_id.'&a='.$total.'&css='.$this->arsenalpay_css
                    .'&frame='.$this->arsenalpay_frame_mode.' '.$this->arsenalpay_frame_params.'></iframe>';
            //$order->update_status( 'processing', __( 'Payment to be made upon confirmation.', 'woocommerce' ) );
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
                if( $ars_callback['FUNCTION']=="check" )
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
                        $this->log->add( 'arsenalpay', 'Aborting, Order #' . $order->id . ' is already complete.' );
                }
                exit;
            }
                
            $keyArray = array
            (
                'ID',           /* ������������� ���/ merchant identifier */
                'FUNCTION',     /* ��� �������/ type of request to which the response is received*/
                'RRN',          /* ������������� ����������/ transaction identifier */
                'PAYER',        /* ������������� �����������/ payer(customer) identifier */
                'AMOUNT',       /* ����� �������/ payment amount */
                'ACCOUNT',      /* ����� ���������� ������� (����� ������, ����� ��) �� ������� ���/ order number */
                'STATUS',       /* ������ ������� - check - ������ �� �������� ������ ���������� : payment - ������ �� �������� ������� �������
                /* Payment status. When 'check' - response for the order number checking, when 'payment' - response for status change.*/
                'DATETIME',     /* ���� � ����� � ������� ISO-8601 (YYYY-MM-DDThh:mm:ss�hh:mm), ���-������������ */
                /* Date and time in ISO-8601 format, urlencoded.*/
                'SIGN',         /* ������� �������/ response sign.
                 //* = md5(md5(ID).md(FUNCTION).md5(RRN).md5(PAYER).md5(AMOUNT).md5(ACCOUNT).md(STATUS).md5(PASSWORD)) */       
            ); 
            /**
            * Checking the absence of each parameter in the post request.
            * �������� �� ����������� ������� �� ���������� � �� �������� � ������������ �������. 
            */   
            foreach( $keyArray as $key ) 
            {
                if( empty( $ars_callback[$key] ) || !array_key_exists( $key, $ars_callback ) )
                {
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
            
                    //============== For testing, delete after testing =============================
            //  $S=md5(md5($ars_callback['ID']).
            //            md5($ars_callback['FUNCTION']).md5($ars_callback['RRN']).
            //          md5($ars_callback['PAYER']).md5($ars_callback['AMOUNT']).md5($ars_callback['ACCOUNT']).
            //           md5($ars_callback['STATUS']).md5($KEY) );
            //    echo $S.'</br>';
                //======================================
            // Validate Amount
            if ($wc_order->get_total() != $ars_callback['AMOUNT'] )
            {
                if ( 'yes' == $this->debug ) 
                {
                    $this->log->add( 'arsenalpay', 'Payment error: Amounts do not match (amount ' . $ars_callback['AMOUNT'] . ')' );
                }
                // Put this order on-hold for manual checking
		$order->update_status( 'on-hold', sprintf( __( 'Validation error: ArsenalPay amounts do not match (amount %s).', 'woocommerce' ), $ars_callback['AMOUNT'] ) );
                $this->exitf( 'ERR_AMOUNT' );
            }
                 
     
            //======================================
            /**
            * Checking validness of the request sign.
            */
            if( !( $this->_checkSign( $ars_callback, $KEY) ) ) 
            {
                $this->exitf( 'ERR_INVALID_SIGN' );

            }
            if( $ars_callback['FUNCTION'] == "check" )
            {
                // Check account
                /*
                        Here is account check procedure
                        Result:
                        YES - account exists
                        NO - account not exists
                */
             
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
                $wc_order->add_order_note( __( 'Payment completed', 'woocommerce' ) );
                $wc_order->payment_complete();
                $woocommerce->cart->empty_cart();
                wp_redirect( $this->get_return_url( $wc_order ) );
                $this->exitf('OK');  
            }
            else 
            {   
                $wc_order->update_status( 'failed', sprintf( __( 'Payment failed via callback.', 'woocommerce' ) ) );
                wp_redirect($wc_order->get_cancel_order_url());
                $this->exitf('ERR');
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
    function woocommerce_add_gateway_arsenalpay($methods) {
            $methods[] = 'WC_Gateway_ArsenalPay';
            return $methods;
    }

    add_filter('woocommerce_payment_gateways', 'woocommerce_add_gateway_arsenalpay' );
} 