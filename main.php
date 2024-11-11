<?php
/*
Plugin Name: HashPress Pay
Description: Integrates Hedera transactions into WordPress and WooCommerce
Version: 0.1
Author: HashPress
Author URI: https://hashpresspioneers.com/
*/

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

if (function_exists('opcache_reset')) {
    opcache_reset();
}

require_once plugin_dir_path(__FILE__) . 'lib/enqueue.php';
require_once plugin_dir_path(__FILE__) . 'lib/acf.php';
require_once plugin_dir_path(__FILE__) . 'lib/shortcodes.php';



add_action('plugins_loaded', 'init_hashpress_pay_gateways');
function init_hashpress_pay_gateways()
{
    // Check if WooCommerce is active
    if (!class_exists('WC_Payment_Gateway')) return;

    include_once 'gateway-hashpress-pay-hbar.php';
    include_once 'gateway-hashpress-pay-usdc.php';

    // Add the Gateway to WooCommerce
    add_filter('woocommerce_payment_gateways', 'add_hashpress_pay_hbar_gateway');
    add_filter('woocommerce_payment_gateways', 'add_hashpress_pay_usdc_gateway');
}


function add_hashpress_pay_hbar_gateway($gateways)
{
    $gateways[] = 'WC_Gateway_HashPress_Pay_HBAR';
    return $gateways;
}

function add_hashpress_pay_usdc_gateway($gateways)
{
    $gateways[] = 'WC_Gateway_HashPress_Pay_USDC';
    return $gateways;
}
