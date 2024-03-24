<?php

if ( ! defined( 'ABSPATH' ) ) exit;    
function process_lead_submission(WP_REST_Request $request) {
    $required_params = ['postcode', 'reg', 'model', 'date', 'cylinder', 'colour', 'keepers', 'contact', 'email', 'fuel', 'mot', 'trans', 'doors', 'motd'];
    foreach ($required_params as $param) {
        if (empty($request->get_param($param))) {
            return new WP_Error('missing_param', "Missing parameter: $param", ['status' => 400]);
        }
    }

    $lead_data = [
        'postcode' => sanitize_text_field($request->get_param('postcode')),
        'registration' => sanitize_text_field($request->get_param('reg')),
        'model' => sanitize_text_field($request->get_param('model')),
        'date' => sanitize_text_field($request->get_param('date')),
        'cylinder' => sanitize_text_field($request->get_param('cylinder')),
        'colour' => sanitize_text_field($request->get_param('colour')),
        'keepers' => sanitize_text_field($request->get_param('keepers')),
        'contact' => sanitize_text_field($request->get_param('contact')),
        'email' => sanitize_email($request->get_param('email')),
        'info' => sanitize_textarea_field($request->get_param('info')), 
        'fuel' => sanitize_text_field($request->get_param('fuel')),
        'mot' => sanitize_text_field($request->get_param('mot')),
        'trans' => sanitize_text_field($request->get_param('trans')),
        'doors' => intval($request->get_param('doors')),
        'motd' => sanitize_text_field($request->get_param('motd')),
    ];

    store_lead($lead_data);

    return new WP_REST_Response(['message' => 'Lead submitted successfully'], 200);
}
