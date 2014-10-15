<?php
/*
Plugin Name: ArsenalPay
Description: Extends WooCommerce with ArsenalPay gateway.
Version: 1.0
 
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
    class WC_Gateway_ArsenalPay extends WC_Payment_Gateway 
    {
        public function __construct() 
        {
            $this->id 	          = 'arsenalpay';
            $this->method_title       = __( 'ArsenalPay', 'woocommerce' );
            $this->method_description = __( 'ArsenalPay', 'woocommerce' );
            $this->has_fields         = false;
            $this->icon	          = '';

            // Load settings fields.
            $this->init_form_fields();
            $this->init_settings();

            // Get the settings and load them into variables
            $this->title 		       = $this->get_option( 'title' );
            $this->description             = $this->get_option( 'description' );
            $this->debug                   = $this->get_option( 'debug' );
            $this->arsenalpay_token        = $this->get_option( 'arsenalpay_token' );
            $this->arsenalpay_other_code   = $this->get_option( 'arsenalpay_other_code' ); 
            $this->arsenalpay_key	   = $this->get_option( 'arsenalpay_key' );
            $this->arsenalpay_css          = $this->get_option( 'arsenalpay_css' );
            $this->arsenalpay_ip           = $this->get_option( 'arsenalpay_ip_address' );
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
            // Add a save hook for settings:
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
                    'description' => __( 'Payment type. Possible options: «mk» - payment from mobile phone (mobile commerce), «card» - payment by bank card (internet-acquiring), optional.', 'woocommerce' ),
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
            $order->update_status( 'processing', __( 'Payment to be made upon confirmation.', 'woocommerce' ) );
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
            @ob_clean();
            global $woocommerce; 
            $ars_callback = stripslashes_deep($_POST);
            $order = wc_get_order( $ars_callback['ACCOUNT'] );
            $total = $order->order_total;
            $keyArray = array
            (
                'ID',           /* Идентификатор ТСП/ merchant identifier */
                'FUNCTION',     /* Тип запроса/ type of request to which the response is received*/
                'RRN',          /* Идентификатор транзакции/ transaction identifier */
                'PAYER',        /* Идентификатор плательщика/ payer(customer) identifier */
                'AMOUNT',       /* Сумма платежа/ payment amount */
                'ACCOUNT',      /* Номер получателя платежа (номер заказа, номер ЛС) на стороне ТСП/ order number */
                'STATUS',       /* Статус платежа - check - запрос на проверку номера получателя : payment - запрос на передачу статуса платежа
                /* Payment status. When 'check' - response for the order number checking, when 'payment' - response for status change.*/
                'DATETIME',     /* Дата и время в формате ISO-8601 (YYYY-MM-DDThh:mm:ss±hh:mm), УРЛ-кодированное */
                /* Date and time in ISO-8601 format, urlencoded.*/
                'SIGN',         /* Подпись запроса/ response sign.
                 //* = md5(md5(ID).md(FUNCTION).md5(RRN).md5(PAYER).md5(AMOUNT).md5(ACCOUNT).md(STATUS).md5(PASSWORD)) */       
            ); 
            /**
            * Checking the absence of each parameter in the post request.
            * Проверка на присутствие каждого из параметров и их значений в передаваемом запросе. 
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
            $REMOTE_ADDR = $_SERVER["REMOTE_ADDR"];
            if ( 'yes' == $this->debug ) 
            {
                $this->log->add( 'arsenalpay', 'Daytime of received callback for order ' . $order->get_order_number() . '. ' . date('Y-m-d H:i:s')." ".$REMOTE_ADDR );
            }
            $KEY = $this->arsenalpay_key;
            echo $this->arsenalpay_frame_url;
            $IP_ALLOW = $this->arsenalpay_ip;
            if( strlen( $IP_ALLOW ) > 0 && $IP_ALLOW != $REMOTE_ADDR ) 
            {
                $this->exitf( 'ERR_IP' );

            }
                    //============== For testing, delete after testing =============================
              $S=md5(md5($ars_callback['ID']).
                        md5($ars_callback['FUNCTION']).md5($ars_callback['RRN']).
                      md5($ars_callback['PAYER']).md5($ars_callback['AMOUNT']).md5($ars_callback['ACCOUNT']).
                       md5($ars_callback['STATUS']).md5($KEY) );
                echo $S.'</br>';
                //======================================
            if ($ars_callback['AMOUNT'] != $total)
            {
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
                //if ($ars_callback['ACCOUNT'] != $cart->id)
                //{
               //     $this->exitf( 'NO' );
               // }
                $this->exitf( 'YES' );
            }
            elseif( $ars_callback['FUNCTION']=="payment" )
            {
                // Payment callback
                /*
                        Here is callback payment saving procedure
                        Result:
                        OK - success saving
                        ERR - error saving*/
                $order = wc_get_order( $order_id );
                $arsenalpay->validateOrder($ars_callback['ACCOUNT'], $newOrderState, $ars_callback['AMOUNT'], $arsenalpay->displayName);
                if( $ars_callback['STATUS'] = "cancelinit" ) 
                {
                    $order->update_status( 'failed', sprintf( __( 'Payment %s via callback.', 'woocommerce' ), strtolower( $ars_callback['STATUS'] ) ) );
                }
                $order->update_status('on-hold', __('Платеж успешно оплачен', 'woocommerce'));
                $woocommerce->cart->empty_cart();
                $this->exitf('OK');
            }
            else 
            {   
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
            die();
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