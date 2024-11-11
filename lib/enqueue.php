<?php

/**
 * Template:       		enqueue.php
 * Description:    		Add CSS and Javascript to the page
 */

add_action('wp_enqueue_scripts', 'enqueue_hashpress_pay_script', 11);
function enqueue_hashpress_pay_script()
{
    // Enqueue the script
    $path = plugin_dir_url(dirname(__FILE__, 1));

    if (is_user_logged_in()) {
        $current_user = wp_get_current_user();

        $target_username = 'anneloes';

        if ($current_user->user_login === $target_username) {
            wp_enqueue_script('hashpress-pay-main-script', $path . 'dist/main.bundle.js', array(), null, array(
                'strategy'  => 'defer', 'in_footer' => false
            ));

            // Localize the REST API URL and nonce for authentication
            wp_localize_script('hashpress-pay-main-script', 'hashpressData', array(
                'nonce' => wp_create_nonce('wp_rest'),  // Generate a nonce for authentication
                'rest_url' => esc_url_raw(rest_url('hashpress/v1/get-project-settings'))  // REST API endpoint URL
            ));

            wp_enqueue_script('hashpress-pay-vendor-script', $path .  'dist/vendors.bundle.js', array(), null, array(
                'strategy'  => 'defer', 'in_footer' => false
            ));
        }
    }
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
