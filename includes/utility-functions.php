<?php

if ( ! defined( 'ABSPATH' ) ) exit;    

add_action('init', 'register_lead_post_type');
function register_lead_post_type() {
    $args = [
        'public' => false,
        'label'  => 'Leads',
        'show_ui' => true, 
        'capability_type' => 'post',
        'hierarchical' => false,
        'supports' => ['title', 'editor', 'custom-fields'],
        'menu_icon' => 'dashicons-email',
    ];
    register_post_type('lead', $args);
}


function store_lead($lead_data, $user_id) {
    // Prepare post data
    $post_data = [
        'post_title'    => wp_strip_all_tags($lead_data['registration']), // Use the car's registration as the post title for easy identification
        'post_content'  => wp_json_encode($lead_data), // Store all lead data as JSON in the post content
        'post_status'   => 'publish',
        'post_type'     => 'lead',
        'post_author'   => $user_id, // Assign lead to this user
        'meta_input' => [
            'postcode' => $lead_data['postcode'],
            'registration' => $lead_data['registration'],
            'model' => $lead_data['model'],
            'date' => $lead_data['date'],
            'cylinder' => $lead_data['cylinder'],
            'colour' => $lead_data['colour'],
            'keepers' => $lead_data['keepers'],
            'contact' => $lead_data['contact'],
            'email' => $lead_data['email'],
            'fuel' => $lead_data['fuel'],
            'mot' => $lead_data['mot'],
            'transmission' => $lead_data['trans'],
            'doors' => $lead_data['doors'],
            'mot_due' => $lead_data['motd'], 
        ],
    ];

    // Insert the post into the database
    $post_id = wp_insert_post($post_data);
    return $post_id;

    // Check for errors
    if (is_wp_error($post_id)) {
        error_log('Failed to store lead: ' . $post_id->get_error_message());
        // Handle error appropriately
    }
}
