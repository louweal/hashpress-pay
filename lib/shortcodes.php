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
        $network = isset($atts['network']) ? esc_html($atts['network']) : "testnet";
        $wallet = isset($atts['wallet']) ? esc_html($atts['wallet']) : null;
        $accepts = isset($atts['accepts']) ? esc_html($atts['accepts']) : 'HBAR';
        $store = isset($atts['store']) ? true : false; // used by hashpress reviews
        $checkout = isset($atts['checkout']) ? true : false;
    } else {
        $title = get_field("field_title") ?: 'Pay';
        $memo = get_field("field_memo") ?: null;
        $amount = floatval(get_field("field_amount")) ?: null;
        $currency = strtolower(get_field("field_currency")) ?: 'hbar';
        $network = get_field("field_network") ?: "testnet";
        $wallet = get_field("field_wallet") ?: null;
        $accepts = get_field("field_accepts") ?: 'HBAR';
        $store = boolval(get_field("field_store")) ?: false; // used by hashpress reviews
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
        "network" => $network,
        "wallet" => $wallet,
        "accepts" => $accepts,
        "store" => $store,
        "checkout" => $checkout
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

            <button type="button" class="btn hashpress-btn pay" data-id="<?php echo $unique_id; ?>"><?php echo $title; ?><?php echo $badge; ?></button>
            <div class="notice"></div>

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
