<?php

add_action('acf/init', 'hashpress_pay_blocks_init');
function hashpress_pay_blocks_init()
{
    // Check function exists.
    if (function_exists('acf_register_block_type')) {
        // Register the transaction button block.
        acf_register_block_type(array(
            'name'              => 'hashpress-pay-block',
            'title'             => __('HashPress Pay', 'hfh'),
            'description'       => __('Button for transactions on the Hedera Network', 'hfh'),
            'render_template'   => dirname(plugin_dir_path(__FILE__)) . '/blocks/hashpress-pay.php',
            'mode'              => 'edit',
            'category'          => 'common',
            'icon'              => 'dashicons-money-alt',
            'keywords'          => array('hashpress', 'hedera', 'transaction', 'hbar', 'usdc', 'button'),
        ));
    }
}


add_action('acf/init', 'add_hashpress_pay_field_groups', 11);
function add_hashpress_pay_field_groups()
{
    if (function_exists('acf_add_local_field_group')) {
        if (!acf_get_local_field_group('group_hashpress_pay')) {
            acf_add_local_field_group(array(
                'key' => 'group_hashpress_pay', // Unique key for the field group
                'title' => 'HashPress Pay',
                'fields' => array(
                    array(
                        'key' => 'field_network',
                        'label' => 'Network',
                        'name' => 'network',
                        'type' => 'select',
                        'required' => 0,
                        'choices' => array(
                            'testnet' => 'Testnet',
                            'previewnet' => 'Previewnet',
                            'mainnet' => 'Mainnet',
                        ),
                        'wrapper' => array(
                            'width' => '50%',
                        ),
                        'allow_null' => 0, // Do not allow null value
                    ),
                    array(
                        'key' => 'field_wallet',
                        'label' => 'Wallet',
                        'name' => 'wallet',
                        'type' => 'text',
                        'required' => 1,
                        'wrapper' => array(
                            'width' => '50%',
                        ),
                        'default_value' => '0.0.4505361',
                    ),
                    array(
                        'key' => 'field_title',
                        'label' => 'Button text',
                        'name' => 'title',
                        'type' => 'text',
                        'required' => 0,
                        'default_value' => 'Pay',
                        'wrapper' => array(
                            'width' => '50%',
                        ),
                    ),
                    array(
                        'key' => 'field_memo',
                        'label' => 'Memo',
                        'name' => 'memo',
                        'type' => 'text',
                        'required' => 0,
                        'wrapper' => array(
                            'width' => '50%',
                        ),
                    ),
                    array(
                        'key' => 'field_amount',
                        'label' => 'Amount',
                        'name' => 'amount',
                        'type' => 'number',
                        'instructions' => 'Leave empty to show an input field.',
                        'required' => 0,
                        'min' => 0,
                        'wrapper' => array(
                            'width' => '25%',
                        ),
                    ),
                    array(
                        'key' => 'field_currency',
                        'label' => 'Currency',
                        'name' => 'currency',
                        'type' => 'select',
                        'instructions' => 'Select the currency the amount is in. It will be converted to HBAR using the CoinGecko API.',
                        'required' => 0,
                        'choices' => array(
                            'usd' => 'USD',
                            'eur' => 'EUR',
                            'jpy' => 'JPY',
                            'gbp' => 'GBP',
                            'aud' => 'AUD',
                            'cad' => 'CAD',
                            'cny' => 'CNY',
                            'inr' => 'INR',
                            'brl' => 'BRL',
                            'zar' => 'ZAR',
                            'chf' => 'CHF',
                            'rub' => 'RUB',
                            'nzd' => 'NZD',
                            'mxn' => 'MXN',
                            'sgd' => 'SGD',
                        ),
                        'default_value' => 'usd',
                        'wrapper' => array(
                            'width' => '25%',
                        ),
                    ),
                    array(
                        'key' => 'field_accepts',
                        'label' => 'Accepts',
                        'instructions' => 'Currently supported currencies are HBAR and USDC.',
                        'name' => 'accepts',
                        'type' => 'text',
                        'required' => 0,
                        'wrapper' => array(
                            'width' => '50%',
                        ),
                        'default_value' => 'HBAR',
                    ),
                ),
                'location' => array(
                    array(
                        array(
                            'param' => 'block',
                            'operator' => '==',
                            'value' => 'acf/hashpress-pay-block',
                        ),
                    ),
                ),
            ));
        }
    }
}
