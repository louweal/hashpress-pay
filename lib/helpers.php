<?php
function update_hashpress_pay_option($post_id, $value)
{
    error_log("Updating hashpress_pay_" . $post_id . " with " . $value);
    $current_meta_value = get_option("hashpress_pay_" . $post_id);

    if (empty($current_meta_value) || !is_array($current_meta_value)) {
        $current_meta_value = array();
    } else {
        // check it is already in
        foreach ($current_meta_value as $v) {
            if ($v == $value) {
                return; // don't add it again
            }
        }
    }

    $current_meta_value[] = $value;
    update_option("hashpress_pay_" . $post_id, $current_meta_value);
}
