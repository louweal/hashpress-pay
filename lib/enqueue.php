<?php

/**
 * Template:       		enqueue.php
 * Description:    		Add CSS and Javascript to the page
 */

add_action('wp_enqueue_scripts', 'enqueue_hashpress_pay_script', 11);
function enqueue_hashpress_pay_script()
{
    global $post;

    $post_id = (is_singular() && $post instanceof WP_Post) ? $post->ID : -1;
    // $order_id = isset($wp->query_vars['order-received']) ? $wp->query_vars['order-received'] : -1;

    // Enqueue the script
    $path = plugin_dir_url(dirname(__FILE__, 1));

    wp_enqueue_script('hashpress-pay-main-script', $path . 'dist/main.bundle.js', array(), null, array(
        'strategy'  => 'defer', 'in_footer' => false
    ));

    wp_localize_script('hashpress-pay-main-script', 'phpData', array(
        'nonce' => wp_create_nonce('wp_rest'),   // Nonce for security
        'getButtonDataUrl' => rest_url('hashpress_pay/v1/get_data'),
        'setTransactionIdUrl' => rest_url('hashpress_pay/v1/set_transaction_id'),
        'postId' => $post_id,
        // 'orderId' => $order_id
    ));

    wp_enqueue_script('hashpress-pay-vendor-script', $path .  'dist/vendors.bundle.js', array(), null, array(
        'strategy'  => 'defer', 'in_footer' => false
    ));
}

add_action('wp_enqueue_scripts', 'hashpress_pay_enqueue_styles', 5);
function hashpress_pay_enqueue_styles()
{
    $path = plugin_dir_url(dirname(__FILE__, 1));

    wp_enqueue_style(
        'hashpress-pay-styles', // Handle
        $path . 'src/css/hashpress-pay.css',
        array(), // Dependencies
        null, // Version number
        'all' // Media type
    );
}
