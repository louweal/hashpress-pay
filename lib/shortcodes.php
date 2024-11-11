<?php


// Register the hashpress transaction button shortcode
add_shortcode('hashpress_pay', 'hashpress_pay_wrapper_function');
function hashpress_pay_wrapper_function($atts)
{
    $shortcode = true;
    $output = hashpress_pay_function($atts, $shortcode);
    return $output;
}

function hashpress_pay_function($atts, $shortcode)
{
    $unique_id = bin2hex(random_bytes(8));


    if ($shortcode) {
        $title = isset($atts['title']) ? esc_html($atts['title']) : 'Pay';
        $memo = isset($atts['memo']) ? esc_html($atts['memo']) : null;
        $amount = isset($atts['amount']) ? floatval(esc_html($atts['amount'])) : null; // convert string to float
        $currency = isset($atts['currency']) ? strtolower(esc_html($atts['currency'])) : 'hbar';
        $store = isset($atts['store']) ? esc_html($atts['store']) : false;
        $network = isset($atts['network']) ? esc_html($atts['network']) : "testnet";
        $wallet = isset($atts['wallet']) ? esc_html($atts['wallet']) : null;
        $accepts = isset($atts['accepts']) ? esc_html($atts['accepts']) : 'HBAR';
    } else {
        $title = get_field("field_title") ?: 'Pay';
        $memo = get_field("field_memo") ?: null;
        $amount = get_field("field_amount") ?: null;
        $currency = get_field("field_currency") ?: 'hbar';
        $store = get_field("field_store") ?: false;
        $network = get_field("field_network") ?: "testnet";
        $wallet = get_field("field_wallet") ?: null;
        $accepts = get_field("field_accepts") ?: 'HBAR';
    }

    if (!$wallet) {
        echo "<p>Receiver Wallet ID missing.</p>";
        return;
    }

    if (!str_starts_with($wallet, '0.0.')) {
        return "A Hedera Account ID should start with 0.0.";
    }

    $badge = $network != "mainnet" ? '<span class="badge">' . $network . '</span>' : '';

    $data = array(
        "title" => $title,
        "memo" => $memo,
        "amount" => $amount,
        "currency" => $currency,
        "store" => $store,
        "network" => $network,
        "wallet" => $wallet,
        "accepts" => $accepts
    );

    // Store the attributes in a transient with the unique ID as part of the key
    set_transient("hashpress_pay_{$unique_id}", $data, 12 * HOUR_IN_SECONDS);

    ob_start();
    if (!is_admin()) {

?>
        <div class="hashpress-pay">
            <?php if ($amount == null) { ?>
                <input type="number" class="input" placeholder="<?php echo strtoupper($currency); ?>">
            <?php }; //if
            ?>

            <button type="button" class="btn hashpress-btn pay" data-id="<?php echo $unique_id; ?>">
                <?php echo $title; ?><?php echo $badge; ?>
            </button>

            <div class="notice"></div>

            <?php
            global $post;
            $post_id = $post->ID;

            $transaction_id = isset($_GET['transaction_id']) ? $_GET['transaction_id'] : null;
            if ($transaction_id) {
                add_meta_to_post($post_id, '_transaction_ids', $transaction_id);
            }

            ?>
        </div>
<?php
    }
    $output = ob_get_clean();
    return $output;
}

function getAccountAndNetwork($testnet_account, $previewnet_account, $mainnet_account)
{
    if (isset($testnet_account)) {
        return [
            "network" => "testnet",
            "account" => $testnet_account
        ];
    }
    if (isset($previewnet_account)) {
        return [
            "network" => "previewnet",
            "account" => $previewnet_account
        ];
    }
    if (isset($mainnet_account)) {
        return [
            "network" => "mainnet",
            "account" => $mainnet_account
        ];
    }
}
