<?php
add_action('plugins_loaded', 'bitcointhai_woocommerce_gateway_class', 0);
/**
 *
 */
function bitcointhai_woocommerce_gateway_class()
{
    if (!class_exists('WC_Payment_Gateway')) return;

    include_once('includes/client.php');
    include_once('bitcointhai_woocommerce.php');

    /**
     * Add the Gateway to WooCommerce
     **/
    function woocommerce_add_bitcointhai_gateway($methods)
    {
        $methods[] = 'BitcointhaiWoocommerce';
        return $methods;
    }

    add_filter('woocommerce_payment_gateways', 'woocommerce_add_bitcointhai_gateway');

    /**
     * JavaScript added to the Plugin
     **/
    function bitcoin_thai_woocommerce() {
      wp_register_script( 'bitcoin_thai_woocommerce', plugins_url( '/js/bitcoin_thai_woocommerce.js', __FILE__ ) );
      wp_enqueue_script( 'bitcoin_thai_woocommerce');
    }
    add_action('init','bitcoin_thai_woocommerce');
}
