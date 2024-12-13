<?php
function update_transaction_history($post_id, $transaction_id)
{
    $current_meta_value = get_post_meta($post_id, "hashpress_transaction_history", true);

    if (empty($transaction_id)) return;

    if (empty($current_meta_value) || !is_array($current_meta_value)) {
        $current_meta_value = array();
    }

    $current_meta_value[] = $transaction_id;
    update_post_meta($post_id, "hashpress_transaction_history", $current_meta_value);
}
