<?php
/*
Plugin Name: Bkash Sandbox API Test
Description: Bkash Sandbox API Test for PGW URL-based checkout.
Author:      IT ASSIST 360
Author URI:  https://www.youtube.com/@ITASSIST360
License:     GPL-2.0+
License URI: http://www.gnu.org/licenses/gpl-2.0.txt
*/

add_action( 'plugins_loaded', 'init_pgw_url_checkout_gateway_class' );

function init_pgw_url_checkout_gateway_class() {
    class WC_Gateway_PGW_URL_Checkout extends WC_Payment_Gateway {
        public function __construct() {
            $this->id = 'pgw_url_checkout';
            $this->method_title = __( 'Bkash Sandbox API Test', 'pgw-url-checkout' );
            $this->method_description = __( 'Bkash Sandbox API Test for PGW URL-based checkout.', 'pgw-url-checkout' );
            $this->has_fields = true;
            // ... add more settings fields as needed
            $this->init_form_fields();
            $this->init_settings();

			$this->title = $this->get_option('title');
            $this->app_key = $this->get_option( 'app_key' );
            $this->app_secret = $this->get_option( 'app_secret' );
            $this->username = $this->get_option( 'username' );
            $this->password = $this->get_option( 'password' );
            $this->id_token = $this->get_option( 'id_token' );
            $this->Create_Payment = $this->get_option( 'Create_Payment' );
            $this->Execute_Payment = $this->get_option( 'Execute_Payment' );

            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));

            add_action( 'woocommerce_api_pgw_url_checkout', array( $this, 'handle_callback' ) );
        }


        

        public function init_form_fields() {
            $this->form_fields = array(
                'enabled' => array(
                    'title'   => __( 'Enable/Disable', 'pgw-url-checkout' ),
                    'type'    => 'checkbox',
                    'label'   => __( 'Enable Bkash Sandbox API Test', 'pgw-url-checkout' ),
                    'default' => 'yes',
                ),
                'title' => array(
                    'title'       => __( 'Title', 'pgw-url-checkout' ),
                    'type'        => 'text',
                    'description' => __( 'This controls the title which the user sees during checkout.', 'pgw-url-checkout' ),
                    'default'     => __( 'Bkash Sandbox API Test', 'pgw-url-checkout' ),
                    'desc_tip'    => true,
                ),
                'username'    => array(
                    'title'       => __('username', 'pgw-url-checkout'),
                    'type'        => 'text',
                    'description' => __('username', 'pgw-url-checkout'),
                    'default'     => '',
                ),
                'password'    => array(
                    'title'       => __('password', 'pgw-url-checkout'),
                    'type'        => 'text',
                    'description' => __('password', 'pgw-url-checkout'),
                    'default'     => '',
                ),
                'app_key'    => array(
                    'title'       => __('app_key', 'pgw-url-checkout'),
                    'type'        => 'text',
                    'description' => __('app_key', 'pgw-url-checkout'),
                    'default'     => '',
                ),
                'app_secret'    => array(
                    'title'       => __('app_secret', 'pgw-url-checkout'),
                    'type'        => 'text',
                    'description' => __('app_secret', 'pgw-url-checkout'),
                    'default'     => '',
                ),
                'id_token'    => array(
                    'title'       => __('id_token', 'pgw-url-checkout'),
                    'type'        => 'text',
                    'description' => __('id_token', 'pgw-url-checkout'),
                    'default'     => '',
                ),
                'Create_Payment'    => array(
                    'title'       => __('Create_Payment', 'pgw-url-checkout'),
                    'type'        => 'text',
                    'description' => __('Create_Payment', 'pgw-url-checkout'),
                    'default'     => '',
                ),
                'Execute_Payment'    => array(
                    'title'       => __('Execute_Payment', 'pgw-url-checkout'),
                    'type'        => 'text',
                    'description' => __('Execute_Payment', 'pgw-url-checkout'),
                    'default'     => '',
                )

                // ... add more settings fields as needed
            );
        }

        public function process_payment( $order_id ) {
            $order = wc_get_order( $order_id );
            // generate the PGW checkout URL
            $checkout_url = $this->generate_checkout_url( $order, $order_id );
            // update the order status and meta data
            $order->update_status( 'pending', __( 'Awaiting payment', 'pgw-url-checkout' ) );
            $order->add_meta_data( 'pgw_checkout_url', $checkout_url );
            $order->save();
            // return the redirect URL
            return array(
                'result' => 'success',
                'redirect' => $checkout_url
            );
        }

        public function generate_checkout_url( $order, $order_id ) {
// code to generate the PGW checkout URL using the order details
// ...
$username = $this->get_option('username');
$password = $this->get_option('password');
$app_key = $this->get_option('app_key');
$app_secret = $this->get_option('app_secret');

$headers = array(
    'accept' => 'application/json',
    'content-type' => 'application/json',
    'password' => $password,
    'username' => $username
);

$data = array(
    'app_key' => $app_key,
    'app_secret' => $app_secret
);

$response = wp_remote_post( 'https://tokenized.sandbox.bka.sh/v1.2.0-beta/tokenized/checkout/token/grant', array(
    'headers' => $headers,
    'body' => json_encode( $data ),
) );

if ( is_wp_error( $response ) ) {
    // handle error
} else {
    $response_body = json_decode( wp_remote_retrieve_body( $response ) );
    $id_token = $response_body->id_token;

    $this->update_option('id_token', $id_token);

    $headers = array(
    'Authorization' => 'Bearer '.$id_token,
    'X-APP-Key' => $app_key,
    'accept' => 'application/json',
    'content-type' => 'application/json'
);
$order_data = $order->get_data();

$data = array(
    'callbackURL' => get_option( 'siteurl' ).'/wc-api/pgw_url_checkout?order_id='.$order_id,
    'mode' => '0011',
    'amount' => $order_data['total'],
    'currency' => 'BDT',
    'intent' => 'sale',
    'merchantInvoiceNumber' => $order_id,
    'payerReference' => $order_id
);

$response = wp_remote_post( 'https://tokenized.sandbox.bka.sh/v1.2.0-beta/tokenized/checkout/create', array(
    'headers' => $headers,
    'body' => json_encode( $data ),
) );

if ( is_wp_error( $response ) ) {
    // handle error
} else {
    $response_body = json_decode( wp_remote_retrieve_body( $response ) );
    // do something with the response
    $this->update_option('Create_Payment', wp_remote_retrieve_body( $response ));
    $checkout_url = $response_body->bkashURL;
}


    // do something with the response
}


return $checkout_url;
}

    public function verify_callback( $data ) {
        // code to verify the callback data using the PGW API
        // ...
        return true;
    }

    public function handle_callback() {
        $app_key = $this->get_option('app_key');

        $order_id = $_GET['order_id'];
        $order = wc_get_order( $order_id );

        $id_token = $this->get_option('id_token');
        if ( ! $order ) {
            wp_die( __( 'Invalid order.', 'pgw-url-checkout' ) );
        }
        // verify the callback data
        if ( ! $this->verify_callback( $_GET ) ) {
            wp_die( __( 'Invalid callback.', 'pgw-url-checkout' ) );
        }
        // update the order status
        if ( $_GET['status'] == 'success' ) {


            $headers = array(
                'Authorization' => $id_token,
                'X-APP-Key' => $app_key,
                'accept' => 'application/json',
                'content-type' => 'application/json',
            );
            
            $body = array(
                'paymentID' => $_GET['paymentID'],
            );
            
            $response = wp_remote_post(
                'https://tokenized.sandbox.bka.sh/v1.2.0-beta/tokenized/checkout/execute',
                array(
                    'method'      => 'POST',
					'timeout'     => 20,
					'redirection' => 5,
					'httpversion' => '1.1',
					'blocking'    => true,
                    'headers' => $headers,
                    'body' => json_encode($body),
                    'cookies'     => array()
                )
            );

            $this->update_option('Execute_Payment', wp_remote_retrieve_body( $response ));
            // check for errors
            if (is_wp_error($response)) {
                $error = new WP_Error(400, $data);
                wp_send_json_error( $error );
            } else {
                $response_body = json_decode( wp_remote_retrieve_body( $response ) );
                // do something with the response
                $order->payment_complete();
                $order->add_order_note( __( 'Payment completed via PGW URL checkout. trx id: '.$response_body->trxID, 'pgw-url-checkout' ) );
                wp_safe_redirect( site_url("/my-account/orders") );
                exit;
            }


}
        
    }
}

function add_pgw_url_checkout_gateway_class( $methods ) {
    $methods[] = 'WC_Gateway_PGW_URL_Checkout';
    return $methods;
}

add_filter( 'woocommerce_payment_gateways', 'add_pgw_url_checkout_gateway_class' );


}