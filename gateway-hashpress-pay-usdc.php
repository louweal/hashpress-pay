<?php

if (!defined('ABSPATH')) {
    exit;
}

class WC_Gateway_HashPress_Pay_USDC extends WC_Payment_Gateway
{
    public $enabled;
    public $title;
    public $network;
    public $wallet;

    public function init_form_fields()
    {
        $this->form_fields = array(
            'enabled' => array(
                'title' => 'Enable/Disable',
                'type' => 'checkbox',
                'label' => 'Enable USDC Payment',
                'default' => 'yes'
            ),
            'title' => array(
                'title' => 'Title',
                'type' => 'text',
                'description' => 'This is the title which the user sees during checkout.',
                'default' => 'Pay with Hedera - USDC',
            ),
            'network' => array(
                'title'       => __('Network', 'woocommerce'),
                'type'        => 'select',
                'default'     => 'testnet',
                'options'     => array(
                    'testnet' => __('Testnet', 'woocommerce'),
                    'previewnet' => __('Previewnet', 'woocommerce'),
                    'mainnet' => __('Mainnet', 'woocommerce')
                )
            ),
            'wallet' => array(
                'title' => 'Hedera Wallet ID',
                'type' => 'text',
            ),
        );
    }

    public function __construct()
    {
        $this->id = 'hashpress-pay-usdc';
        $this->icon = ''; // URL of the icon that will be displayed on the checkout page
        $this->has_fields = true;
        $this->method_title = 'HashPress Pay - USDC';
        $this->method_description = 'Have your customers pay with USDC from their Hedera wallet.';

        // Load the settings
        $this->init_form_fields();
        $this->init_settings();

        // Define user set variables
        $this->title = $this->get_option('title');
        $this->network = $this->get_option('network');
        $this->wallet = $this->get_option('wallet');

        // Actions
        add_action('woocommerce_receipt_' . $this->id, array($this, 'receipt_page'));
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));

        // Payment listener/API hook
        add_action('woocommerce_api_wc_gateway_' . $this->id, array($this, 'check_response'));
    }



    public function admin_options()
    {
        echo '<h2>' . esc_html($this->get_method_title()) . '</h2>';
        echo '<table class="form-table">';
        $this->generate_settings_html();
        echo '</table>';
    }

    public function payment_fields()
    {
        echo "Pay with HashPress Pay - USDC.";
    }


    public function validate_fields()
    {
        return true;
    }

    public function process_payment($order_id)
    {
        $order = wc_get_order($order_id);
        $order->update_status('pending', __('Awaiting USDC payment', 'woocommerce'));

        // Return thank you page redirect.
        return array(
            'result'   => 'success',
            'redirect' => $order->get_checkout_payment_url(true),
            // 'redirect' => $this->get_return_url($order),
        );
    }

    public function receipt_page($order_id)
    {
        $order = wc_get_order($order_id);

        if ($order) {
            $order_total = $order->get_total();

            $currency = get_woocommerce_currency();

            $transaction_id = isset($_GET['transaction_id']) ? urldecode($_GET['transaction_id']) : null;

            if (!$transaction_id) {
                echo '<p>Thank you for your order. WalletConnect should be opened now for you to complete the payment. Click the button below if it is not opened.</p>';
                echo "<div id='hashpress-pay-woocommerce'>";
                echo do_shortcode('[hashpress_pay network="' . $this->network . '" title="Open WalletConnect"  wallet="' . $this->wallet . '" accepts="USDC" currency="' . $currency . '" amount="' . $order_total . '" memo="Order at ' . get_bloginfo('name') . '" checkout="true"]');
                echo '</div>';
            } else {
                echo '<p>Payment received, order completed. Thank you!</p>';
                $order->update_status('completed', __('Payment received, order completed.', 'woocommerce'));
                wc_reduce_stock_levels($order_id);
                WC()->cart->empty_cart();

                // add meta info to products
                foreach ($order->get_items() as $item_id => $item) {
                    $product_id = $item->get_product_id();
                    add_meta_to_post($product_id, '_transaction_ids', $transaction_id);
                }
            }
        } else {
            var_dump("Order not found.");
        }
    }



    public function check_response()
    {
        // Handle the payment response
        echo "Handle response";
    }
}
