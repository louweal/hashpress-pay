<?php
// register rest routes
add_action('rest_api_init', function () {
    register_rest_route('hashpress_pay/v1', '/get_data', array(
        'methods' => 'GET',
        'callback' => 'hashpress_pay_get_data',
        'permission_callback' => 'hashpress_pay_validate_nonce',
    ));
    register_rest_route('hashpress_pay/v1', '/set_transaction_id', [
        'methods'  => 'POST',
        'callback' => 'set_transaction_id',
        'permission_callback' => 'hashpress_pay_validate_nonce'
    ]);
});

function hashpress_pay_validate_nonce(WP_REST_Request $request)
{
    $nonce = $request->get_header('X-WP-Nonce');
    if (wp_verify_nonce($nonce, 'wp_rest')) {
        return true;
    }
    return new WP_Error('rest_forbidden', __('Invalid nonce.'), ['status' => 403]);
}

// get button data
function hashpress_pay_get_data(WP_REST_Request $request)
{
    $id = $request->get_param('id');

    // Retrieve data from the transient using the unique ID
    $data = get_transient("hashpress_pay_{$id}");

    if (!$data) {
        return new WP_REST_Response(['error' => 'Data not found'], 404);
    }

    return new WP_REST_Response($data, 200);
}


function set_transaction_id(WP_REST_Request $request)
{
    $post_id = $request->get_param('postId');
    $transactionId = $request->get_param('transactionId');

    if (!$post_id || !$transactionId) {
        return new WP_Error('missing_data', 'Post ID and variable are required', ['status' => 400]);
    }

    // Validate that the post ID exists
    if (!get_post($post_id)) {
        return new WP_Error('invalid_post', 'Post does not exist', ['status' => 404]);
    }

    // Retrieve the existing metadata array
    $existing_meta = get_post_meta($post_id, '_transaction_ids', true);

    // If the metadata does not exist, initialize it as an array
    if (!is_array($existing_meta)) {
        $existing_meta = [];
    }

    // Append the new variable to the array
    $existing_meta[] = sanitize_text_field($transactionId);

    // Update the post meta with the new array
    update_post_meta($post_id, '_transaction_ids', $existing_meta);

    return rest_ensure_response(['success' => true, 'updated_meta' => $existing_meta]);
}
