<?php

// 1. Register REST API route to retrieve shortcode data
add_action('rest_api_init', function () {
    register_rest_route('hashpress_pay/v1', '/get_data', array(
        'methods' => 'GET',
        'callback' => 'hashpress_pay_get_data',
        'permission_callback' => 'hashpress_pay_permission_check',
    ));
});

// 2. Define the REST API callback to fetch stored attributes
function hashpress_pay_get_data(WP_REST_Request $request)
{
    $id = $request->get_param('id');

    // Retrieve data from the transient or database using the unique ID
    $data = get_transient("hashpress_pay_{$id}");

    if (!$data) {
        return new WP_REST_Response(['error' => 'Data not found'], 404);
    }

    return new WP_REST_Response($data, 200);
}

// 3. Define the permission check for the REST API endpoint
function hashpress_pay_permission_check()
{
    return current_user_can('read'); // Customize as needed
}
