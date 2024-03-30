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

    $user_info = get_userdata($user_id);
    $user_display_name = $user_info ? $user_info->display_name : 'Unknown User';
    // Prepare post data
    $post_data = [
        'post_title'    => wp_strip_all_tags($user_display_name . ' : ' . $lead_data['registration'] . ' -  ' . $lead_data['model'] . ' - ' . $lead_data['postcode']), // Use the car's registration as the post title for easy identification
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

add_action('restrict_manage_posts', 'custom_lead_filters', 10, 2);
function custom_lead_filters($post_type, $which) {
    if ('lead' !== $post_type) {
        return;
    }

    // Date filter dropdown
    ?>
    <select name="lead_date_filter">
        <option value=""><?php _e('All Dates'); ?></option>
        <option value="today" <?php selected(isset($_GET['lead_date_filter']), 'today'); ?>><?php _e('Today'); ?></option>
        <option value="yesterday" <?php selected(isset($_GET['lead_date_filter']), 'yesterday'); ?>><?php _e('Yesterday'); ?></option>
        <option value="this_week" <?php selected(isset($_GET['lead_date_filter']), 'this_week'); ?>><?php _e('This Week'); ?></option>
        <option value="last_week" <?php selected(isset($_GET['lead_date_filter']), 'last_week'); ?>><?php _e('Last Week'); ?></option>
        <option value="this_month" <?php selected(isset($_GET['lead_date_filter']), 'this_month'); ?>><?php _e('This Month'); ?></option>
        <option value="last_month" <?php selected(isset($_GET['lead_date_filter']), 'last_month'); ?>><?php _e('Last Month'); ?></option>
    </select>
    <?php

    // Assuming 'assigned_user' corresponds to WP user IDs
    wp_dropdown_users([
        'show_option_all' => __('All Agents'),
        'name' => 'assigned_user',
        'selected' => isset($_GET['assigned_user']) ? $_GET['assigned_user'] : '',
    ]);

    // Submit button for the filters
    submit_button(__('Filter'), null, 'filter_action', false);
}

add_action('pre_get_posts', 'filter_leads_by_custom_filters');
function filter_leads_by_custom_filters($query) {
    global $pagenow;

    if (is_admin() && 'edit.php' === $pagenow && 'lead' === $query->query['post_type'] && $query->is_main_query()) {
        // Handle the date filter
        if (!empty($_GET['lead_date_filter'])) {
            apply_date_filter($query, $_GET['lead_date_filter']);
        }

        // Handle the 'assigned_user' filter if set
        if (!empty($_GET['assigned_user'])) {
            $query->set('meta_query', [
                [
                    'key' => 'assigned_user',
                    'value' => $_GET['assigned_user'],
                    'compare' => '='
                ]
            ]);
        }
    }
}

function apply_date_filter(&$query, $filter_value) {
    $date_query = [];
    switch ($filter_value) {
        case 'today':
            $date_query = ['year' => date('Y'), 'month' => date('m'), 'day' => date('d')];
            break;
        case 'yesterday':
            $yesterday = strtotime('-1 day');
            $date_query = ['year' => date('Y', $yesterday), 'month' => date('m', $yesterday), 'day' => date('d', $yesterday)];
            break;
        // Add cases for this_week, last_week, this_month, last_month
    }
    if ($date_query) {
        $query->set('date_query', [$date_query]);
    }
}
function enqueue_admin_scripts() {
    global $pagenow, $typenow;

    if ( $pagenow == 'edit.php' && $typenow == 'lead' ) {
        ?>
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                // Change month dropdown text
                $("select[name='m'] option[value='0']").text('By Months');

                // Hide the second filter button if it exists
                $("#filter_action").hide();
            });
        </script>
        <?php
    }
}
add_action('admin_footer', 'enqueue_admin_scripts');
