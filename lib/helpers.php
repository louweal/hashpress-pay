<?php
function hashpress_pay_update_transaction_history($post_id, $transaction_id)
{
    $current_transaction_history = get_post_meta($post_id, "hashpress_transaction_history", true);

    if (empty($transaction_id)) return;

    if (empty($current_transaction_history) || !is_array($current_transaction_history)) {
        $current_transaction_history = array();
    }

    $current_transaction_history[] = $transaction_id;
    update_post_meta($post_id, "hashpress_transaction_history", $current_transaction_history);
}
