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
        'post_title'    => wp_strip_all_tags($lead_data['registration'] . ' -  ' . $lead_data['model'] ),
        'post_content'  => wp_json_encode($lead_data),
        'post_status'   => 'publish',
        'post_type'     => 'lead',
        'post_author'   => $user_id,
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
            'mot_due' => $lead_data['mot_due'],
            'leadid' => $lead_data['leadid'],
            'vin' => $lead_data['vin'],
            'resend' => $lead_data['resend'],
            'submission_url' => $lead_data['submission_url'],
            'source_domain' => $lead_data['source_domain'],
            'ip_address' => $lead_data['ip_address'],
            'campaign_id' => $lead_data['campaign_id'], // Campaign ID (from vt_campaign)
            'vt_campaign' => $lead_data['vt_campaign'],   // Store vt_campaign directly
            'utm_source'  => $lead_data['utm_source'],    // Store utm_source directly
            'vt_keyword'  => $lead_data['vt_keyword'],    // Store vt_keyword directly
            'vt_adgroup'  => $lead_data['vt_adgroup'],    // Store vt_adgroup directly
            'milage' => $lead_data['milage'], 
        ],
    ];

    $post_id = wp_insert_post($post_data);
    return $post_id;

    if (is_wp_error($post_id)) {
        error_log('Failed to store lead: ' . $post_id->get_error_message());
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

    // Add postcode search input
    ?>
    <input type="text" name="lead_postcode_search" placeholder="<?php _e('Search Postcode prefix...'); ?>" value="<?php echo isset($_GET['lead_postcode_search']) ? esc_attr($_GET['lead_postcode_search']) : ''; ?>">
    <?php

    // Submit button for the filters
    submit_button(__('Filter'), null, 'filter_action', false);
}

add_action('pre_get_posts', 'filter_leads_by_custom_filters');
function filter_leads_by_custom_filters($query) {
    global $pagenow;

    // Ensure we are on the correct admin page for 'lead' post type and main query
    if (is_admin() && 'edit.php' === $pagenow && 'lead' === $query->query['post_type'] && $query->is_main_query()) {
        $meta_query = []; // Initialize array for meta queries
        $date_query_args = []; // Initialize array for date query arguments

        // Check if ANY custom filter is active (date, assigned user, or postcode search)
        $custom_filter_active = !empty($_GET['lead_date_filter']) || !empty($_GET['assigned_user']) || !empty($_GET['lead_postcode_search']);

        if ($custom_filter_active) {
            // Crucial: Always clear default search ('s') and month ('m') parameters
            // if any of our custom filters are actively being used.
            $query->set('s', '');
            $query->set('m', '');
        }

        // 1. Handle Postcode Search (remains as fixed previously)
        // if (!empty($_GET['lead_postcode_search'])) {
        //     $search_term = sanitize_text_field($_GET['lead_postcode_search']);
        //     $meta_query[] = [
        //         'key' => 'postcode',
        //         'value' => $search_term . '%', // Search for postcodes starting with the term
        //         'compare' => 'LIKE',
        //     ];
        // }

        // 2. Handle Assigned User filter (remains as fixed previously)
        if (!empty($_GET['assigned_user'])) {
            $meta_query[] = [
                'key' => 'assigned_user',
                'value' => $_GET['assigned_user'],
                'compare' => '='
            ];
        }

        // Apply meta_query if any conditions exist
        if (!empty($meta_query)) {
            // If there are multiple meta queries, ensure they are combined with 'AND' relation
            if (count($meta_query) > 1) {
                $meta_query['relation'] = 'AND';
            }
            $query->set('meta_query', $meta_query);
        }

        // 3. Handle Date filter (UPDATED to use wp_date consistently for date boundaries)
        if (!empty($_GET['lead_date_filter'])) {
            $start_of_week = get_option('start_of_week', 0); // 0 (Sunday) to 6 (Saturday)
            $current_day_of_week = (int) wp_date('w'); // Get current day of week using wp_date

            switch ($_GET['lead_date_filter']) {
                case 'today':
                    $today_start = wp_date('Y-m-d 00:00:00');
                    $today_end = wp_date('Y-m-d 23:59:59');
                    $date_query_args = [
                        'after' => $today_start,
                        'before' => $today_end,
                        'inclusive' => true,
                    ];
                    break;
                case 'yesterday':
                    // Calculate start and end of yesterday using wp_date and strtotime
                    $yesterday_start = wp_date('Y-m-d 00:00:00', strtotime('-1 day'));
                    $yesterday_end = wp_date('Y-m-d 23:59:59', strtotime('-1 day'));
                    $date_query_args = [
                        'after' => $yesterday_start,
                        'before' => $yesterday_end,
                        'inclusive' => true,
                    ];
                    break;
                case 'this_week':
                    // Adjust calculations to use wp_date consistently
                    $days_since_start_of_week = ( $current_day_of_week - $start_of_week + 7 ) % 7;
                    $startOfWeek = wp_date('Y-m-d', strtotime('-' . $days_since_start_of_week . ' days'));
                    $endOfWeek = wp_date('Y-m-d', strtotime($startOfWeek . ' +6 days'));
                    $date_query_args = [
                        'after' => $startOfWeek . ' 00:00:00',
                        'before' => $endOfWeek . ' 23:59:59',
                        'inclusive' => true,
                    ];
                    break;
                case 'last_week':
                    // Adjust calculations to use wp_date consistently
                    $days_since_start_of_week = ( $current_day_of_week - $start_of_week + 7 ) % 7;
                    $startOfThisWeek = wp_date('Y-m-d', strtotime('-' . $days_since_start_of_week . ' days'));
                    $startOfLastWeek = wp_date('Y-m-d', strtotime($startOfThisWeek . ' -7 days'));
                    $endOfLastWeek = wp_date('Y-m-d', strtotime($startOfThisWeek . ' -1 day'));
                    $date_query_args = [
                        'after' => $startOfLastWeek . ' 00:00:00',
                        'before' => $endOfLastWeek . ' 23:59:59',
                        'inclusive' => true,
                    ];
                    break;
                case 'this_month':
                    $start_of_month = wp_date('Y-m-01'); // Use wp_date
                    $end_of_month = wp_date('Y-m-t 23:59:59'); // Get last day of month and add end time
                    $date_query_args = [
                        'after' => $start_of_month . ' 00:00:00',
                        'before' => $end_of_month,
                        'inclusive' => true,
                    ];
                    break;
                case 'last_month':
                    // Adjust calculations to use wp_date consistently
                    $start_of_last_month = wp_date('Y-m-01', strtotime('first day of last month'));
                    $end_of_last_month = wp_date('Y-m-t 23:59:59', strtotime('last day of last month'));
                    $date_query_args = [
                        'after' => $start_of_last_month . ' 00:00:00',
                        'before' => $end_of_last_month,
                        'inclusive' => true,
                    ];
                    break;
            }
            if (!empty($date_query_args)) {
                $query->set('date_query', [$date_query_args]);
            }
        }

        // 4. Handle sorting by postcode (remains as fixed previously, affects orderby)
        if (isset($query->query_vars['orderby']) && 'postcode' === $query->query_vars['orderby']) {
            $query->set('meta_key', 'postcode');
            $query->set('orderby', 'meta_value');
        }
    }
}
// Re-add the posts_where filter for postcode search, which previously worked
add_filter('posts_where', 'lead_postcode_search_where', 10, 2);

function lead_postcode_search_where($where, $query) {
    global $wpdb;

    // Only apply if it's the main query on the leads admin page and our custom search parameter is present
    if (is_admin() && $query->is_main_query() && isset($query->query_vars['post_type']) && $query->query_vars['post_type'] === 'lead') {
        if (!empty($_GET['lead_postcode_search'])) {
            $search_term = sanitize_text_field($_GET['lead_postcode_search']);
            // Escape the search term for LIKE, then manually add the wildcard
            $escaped_search_term = $wpdb->esc_like($search_term);
            // Ensure this is added as an AND condition to existing WHERE clause
            // This EXISTS subquery is robust for meta_key LIKE searches
            $where .= " AND EXISTS (SELECT 1 FROM {$wpdb->postmeta} WHERE post_id = {$wpdb->posts}.ID AND meta_key = 'postcode' AND meta_value LIKE '{$escaped_search_term}%')";
        }
    }
    return $where;
}

function apply_date_filter(&$query, $filter_value) {
    $date_query = [];
    $start_of_week = get_option('start_of_week', 0); // Get the WordPress start of the week (0=Sunday)
    $current_day_of_week = date('w'); // 0 (Sunday) to 6 (Saturday)

    switch ($filter_value) {
        case 'today':
            $date_query = [
                'year' => date('Y'), 
                'month' => date('m'), 
                'day' => date('d')
            ];
            break;
        case 'yesterday':
            $yesterday = strtotime('-1 day');
            $date_query = [
                'year' => date('Y', $yesterday), 
                'month' => date('m', $yesterday), 
                'day' => date('d', $yesterday)
            ];
            break;
        case 'this_week':
            // Calculate start and end of this week based on WordPress setting
            $days_since_start_of_week = ( $current_day_of_week - $start_of_week + 7 ) % 7;
            $startOfWeek = date('Y-m-d', strtotime('-' . $days_since_start_of_week . ' days'));
            $endOfWeek = date('Y-m-d', strtotime($startOfWeek . ' +6 days'));
            $date_query = [
                'after' => $startOfWeek, 
                'before' => $endOfWeek, 
                'inclusive' => true
            ];
            break;
        case 'last_week':
            // Calculate start and end of last week
            $days_since_start_of_week = ( $current_day_of_week - $start_of_week + 7 ) % 7;
            $startOfThisWeek = date('Y-m-d', strtotime('-' . $days_since_start_of_week . ' days'));
            $startOfLastWeek = date('Y-m-d', strtotime($startOfThisWeek . ' -7 days'));
            $endOfLastWeek = date('Y-m-d', strtotime($startOfThisWeek . ' -1 day'));
            $date_query = [
                'after' => $startOfLastWeek, 
                'before' => $endOfLastWeek, 
                'inclusive' => true
            ];
            break;
        // Add other cases if needed
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


add_filter('manage_lead_posts_columns', 'add_custom_lead_columns');
function add_custom_lead_columns($columns) {
    // Add new columns
    $columns['leadid'] = __('Lead ID');
    $columns['postcode'] = __('Postcode');
    $columns['vin'] = __('VIN'); // Add VIN column
    $columns['post_author'] = __('Agent');

    return $columns;
}

add_action('manage_lead_posts_custom_column', 'custom_lead_column_content', 10, 2);
function custom_lead_column_content($column_name, $post_id) {
    switch ($column_name) {
        case 'leadid':
            echo get_post_meta($post_id, 'leadid', true);
            break;
        case 'postcode':
            echo get_post_meta($post_id, 'postcode', true);
            break;
        case 'vin': // Handle VIN column
            echo get_post_meta($post_id, 'vin', true);
            break;
        case 'post_author':
            $author_id = get_post_field('post_author', $post_id);
            $author = get_user_by('id', $author_id);
            echo $author ? $author->user_login : __('Unknown');
            break;
    }
}

add_filter('posts_search', 'search_lead_id_in_admin', 10, 2);
function search_lead_id_in_admin($search, $wp_query) {
    global $wpdb;
    if (!is_admin()) return $search;
    if (!$wp_query->is_search) return $search;
    if (!isset($wp_query->query['post_type']) || 'lead' != $wp_query->query['post_type']) return $search;

    $search_terms = $wp_query->query_vars['s'];
    $search_terms = $wpdb->_escape($search_terms);

    if (empty($search_terms)) return $search;

    $search = " AND (";
    $search .= "$wpdb->posts.post_title LIKE '%$search_terms%'";
    $search .= " OR $wpdb->posts.post_content LIKE '%$search_terms%'";
    $search .= " OR EXISTS (";
    $search .= "     SELECT * FROM $wpdb->postmeta";
    $search .= "     WHERE post_id = $wpdb->posts.ID";
    $search .= "     AND meta_key = 'leadid'";
    $search .= "     AND meta_value LIKE '%$search_terms%'";
    $search .= " )";
    $search .= ") ";

    return $search;
}

// Add meta box to lead post type
function add_lead_resend_meta_box() {
    add_meta_box(
        'lead_resend_meta_box',
        'Resend Lead',
        'render_lead_resend_meta_box',
        'lead',
        'side', // Display this meta box on the side
        'default'
    );
}
add_action('add_meta_boxes', 'add_lead_resend_meta_box');

// Render the meta box
// Render the meta box
function render_lead_resend_meta_box($post) {
    // Retrieve the existing resend message if available
    $resend_message = get_post_meta($post->ID, '_lead_resend_message', true);
    $resend_checked = get_post_meta($post->ID, '_lead_resend_checked', true);
    $resend_count = (int)get_post_meta($post->ID, '_lead_resend_count', true);

    // Display the fields
    ?>
    <label for="lead_resend">
        <input type="checkbox" name="lead_resend" id="lead_resend" value="1" <?php checked($resend_checked, '1'); ?>>
        Resend this lead
    </label>
    <br><br>
    <label for="lead_resend_message">Resend Message</label><br>
    <textarea name="lead_resend_message" id="lead_resend_message" rows="4" style="width:100%;"><?php echo esc_textarea($resend_message); ?></textarea>
    <br><br>

    <?php if ($resend_count > 0) : ?>
        <p><strong><?php echo esc_html($resend_count); ?></strong> <?php echo _n('resend', 'resends', $resend_count, 'text-domain'); ?> have been made for this lead.</p>
    <?php endif; ?>

    <?php
    wp_nonce_field('save_lead_resend_meta_box_data', 'lead_resend_meta_box_nonce');
}


// Save the checkbox and message data
function save_lead_resend_meta_box_data($post_id) {
    // Verify the nonce to ensure the request is valid
    if (!isset($_POST['lead_resend_meta_box_nonce']) || !wp_verify_nonce($_POST['lead_resend_meta_box_nonce'], 'save_lead_resend_meta_box_data')) {
        return;
    }

    // Ensure it's not an autosave
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    // Check the user's permission
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    // Save the checkbox
    if (isset($_POST['lead_resend'])) {
        update_post_meta($post_id, '_lead_resend_checked', '1');
    } else {
        update_post_meta($post_id, '_lead_resend_checked', '0');
    }

    // Save the message
    if (isset($_POST['lead_resend_message'])) {
        update_post_meta($post_id, '_lead_resend_message', sanitize_textarea_field($_POST['lead_resend_message']));
    }
}
add_action('save_post', 'save_lead_resend_meta_box_data');

// Hook into the post save action to check if resend is triggered
function maybe_resend_lead($post_id) {
    // Only trigger for lead post type
    if (get_post_type($post_id) !== 'lead') {
        return;
    }

    // Check if the resend checkbox is checked
    $resend_checked = get_post_meta($post_id, '_lead_resend_checked', true);
    if ($resend_checked === '1') {
        // Get the lead owner (author)
        $lead_owner_id = get_post_field('post_author', $post_id);
        $lead_owner = get_userdata($lead_owner_id);

        // Get the resend message from the custom textbox
        $resend_message = get_post_meta($post_id, '_lead_resend_message', true);

        // Get the lead details (you can customize this part to include the relevant lead information)
        $lead_details = get_post($post_id)->post_content; // Assuming lead data is in post_content

        // Send email and SMS using the send_lead_email_to_user function
        $lead_data = [
            'leadid' => get_post_meta($post_id, 'leadid', true),
            'registration' => get_post_meta($post_id, 'registration', true),
            'model' => get_post_meta($post_id, 'model', true),
            'keepers' => get_post_meta($post_id, 'keepers', true),
    'contact' => get_post_meta($post_id, 'contact', true),
            // Add any other lead details you want to include here
        ];

        // Include the resend message in the lead details
        $lead_data['resend_message'] = $resend_message;

        // Send lead details via email and SMS (using @txtlocal)
        $mail_sent = resend_lead_email_to_user($lead_owner_id, $lead_data);

        if ($mail_sent) {
            // Optionally add a flag to mark that the lead has been resent
            update_post_meta($post_id, '_lead_resent', '1');

            // Uncheck the resend box to prevent resending on the next save
            update_post_meta($post_id, '_lead_resend_checked', '0');

            // Log the resend action
            $resend_count = (int)get_post_meta($post_id, '_lead_resend_count', true);
            $resend_count++;
            update_post_meta($post_id, '_lead_resend_count', $resend_count);

            // Notify admin of successful resend
            add_action('admin_notices', function() {
                ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php _e('The lead has been successfully resent via email and SMS.', 'text-domain'); ?></p>
                </div>
                <?php
            });
        } else {
            add_action('admin_notices', function() {
                ?>
                <div class="notice notice-error is-dismissible">
                    <p><?php _e('Failed to resend the lead.', 'text-domain'); ?></p>
                </div>
                <?php
            });
        }
    }
}
add_action('save_post', 'maybe_resend_lead');

function resend_lead_email_to_user($user_id, $lead_data) {
    // Log lead data for debugging
    error_log(print_r($lead_data, true));

    // Retrieve user's email address
    $user_info = get_userdata($user_id);
    $to = $user_info->user_email;

    // Retrieve user's phone number from user meta data
    $user_phone = get_user_meta($user_id, 'billing_phone', true);
    $phone_email = $user_phone . '@txtlocal.co.uk';

    // Set the subject of the email
    $subject = "Resend Lead: " . $lead_data['leadid'];

    // Retrieve custom fields from lead data
    $keepers = isset($lead_data['keepers']) ? $lead_data['keepers'] : get_post_meta($lead_data['ID'], 'keepers', true);
    $contact = isset($lead_data['contact']) ? $lead_data['contact'] : get_post_meta($lead_data['ID'], 'contact', true);

    // Prepare the email body without "%n" for the primary email
    $body = "<html><body>";
    $body .= "<h3>Customer Callback or Message Regarding Lead" . esc_html($lead_data['leadid']) . "</h3>";

    if (isset($lead_data['registration']) && isset($lead_data['model'])) {
        $body .= "<h4>". esc_html($lead_data['registration']) . " - " . esc_html($lead_data['model']) . "</h4>";
    }

    if ($keepers) {
        $body .= "<p><strong>Name:</strong> " . esc_html($keepers) . "</p>";
    }

    if ($contact) {
        $body .= "<p><strong>Contact:</strong> " . esc_html($contact) . "</p>";
    }

    if (isset($lead_data['resend_message']) && !empty($lead_data['resend_message'])) {
        $body .= "<p><strong>Message:</strong> " . esc_html($lead_data['resend_message']) . "</p>";
    }

    $body .= "</body></html>";

    // Send the primary email without "%n" line breaks
    $headers = array('Content-Type: text/html; charset=UTF-8');
    $email_sent = wp_mail($to, $subject, $body, $headers);

    // Prepare the email body with "%n" for the SMS email
    $body_sms = "<html><body>";
    $body_sms .= "<h3>Customer Callback or Message Regarding Lead " . esc_html($lead_data['leadid']) . "</h3>%n";

    if (isset($lead_data['registration']) && isset($lead_data['model'])) {
        $body_sms .= "<h4>". esc_html($lead_data['registration']) . " - " . esc_html($lead_data['model']) . "</h4>%n";
    }

    if ($keepers) {
        $body_sms .= "<p><strong>Name:</strong> " . esc_html($keepers) . "</p>%n";
    }

    if ($contact) {
        $body_sms .= "<p><strong>Contact:</strong> " . esc_html($contact) . "</p>%n";
    }

    if (isset($lead_data['resend_message']) && !empty($lead_data['resend_message'])) {
        $body_sms .= "<p><strong>Message:</strong> " . esc_html($lead_data['resend_message']) . "</p>%n";
    }

    $body_sms .= "</body></html>";

    // Send the SMS email with "%n" line breaks
    $headers_sms = array('Content-Type: text/html; charset=UTF-8');
    $sms_sent = wp_mail($phone_email, $subject, $body_sms, $headers_sms);

    // Return true if both emails were sent successfully
    return $email_sent && $sms_sent;
}


// Add the checkbox field to the user profile
add_action('show_user_profile', 'add_lead_priority_checkbox');
add_action('edit_user_profile', 'add_lead_priority_checkbox');

/*************  ✨ Codeium Command ⭐  *************/
/**
 * Adds a checkbox field to the user profile to allow users to increase their lead reception probability.
 *
 * @param WP_User $user The user object.
 */
/******  b1e0b10d-83b7-476c-949c-0005f4da6cf8  *******/
function add_lead_priority_checkbox($user) {
    ?>
    <h3>Lead Reception Priority</h3>
    <table class="form-table">
        <tr>
            <th><label for="lead_priority">Increase Lead Reception Probability</label></th>
            <td>
                <input type="checkbox" name="lead_priority" id="lead_priority" value="1" <?php checked(get_user_meta($user->ID, 'lead_priority', true), '1'); ?> />
                <span class="description">Check this box to increase the probability of receiving leads.</span>
            </td>
        </tr>
    </table>
    <?php
}

// Save the checkbox value
add_action('personal_options_update', 'save_lead_priority_checkbox');
add_action('edit_user_profile_update', 'save_lead_priority_checkbox');

function save_lead_priority_checkbox($user_id) {
    if (current_user_can('edit_user', $user_id)) {
        update_user_meta($user_id, 'lead_priority', isset($_POST['lead_priority']) ? '1' : '0');
    }
}
//Add My Account link to the login form

function my_account_link_shortcode() {
    if ( is_user_logged_in() ) {
        return '<div class="my-account-link" style="text-align: center;">
                    <a href="/my-account" class="button">My Account</a>
                </div>';
    }
    return '';
}
add_shortcode( 'my_account_link', 'my_account_link_shortcode' );

// Redirect to checkout after adding to cart when 'redirect_to=checkout' is present
// Force redirect to checkout when 'redirect_to=checkout' is present in the URL
add_action( 'template_redirect', 'force_redirect_to_checkout' );
function force_redirect_to_checkout() {
    if ( isset( $_GET['redirect_to'] ) && $_GET['redirect_to'] === 'checkout' ) {
        // Perform the add-to-cart action if 'add-to-cart' parameter is present
        // if ( isset( $_GET['add-to-cart'] ) ) {
        //     // Handle the add-to-cart action
        //     WC_Form_Handler::add_to_cart_action();
        // }

        // Redirect to checkout
        wp_safe_redirect( wc_get_checkout_url() );
        exit;
    }
}

// Ensure notices are displayed on the checkout page
add_action( 'woocommerce_before_checkout_form', 'woocommerce_output_all_notices', 10 );

// Automatically apply coupon when 'coupon_code' parameter is present in the URL
add_action( 'woocommerce_add_to_cart', 'apply_coupon_code_from_url', 10, 6 );
function apply_coupon_code_from_url( $cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data ) {
    if ( isset( $_GET['coupon_code'] ) ) {
        $coupon_code = sanitize_text_field( $_GET['coupon_code'] );

        // Check if the coupon is valid and not already applied
        if ( ! WC()->cart->has_discount( $coupon_code ) ) {
            // Apply the coupon
            WC()->cart->apply_coupon( $coupon_code );
            wc_clear_notices(); // Clear default WooCommerce notice

            // Optionally add a custom notice
            wc_add_notice( sprintf( 'Coupon code "%s" has been applied to your order.', esc_html( $coupon_code ) ), 'success' );
        }
    }
}

add_filter('manage_edit_lead_sortable_columns', 'make_postcode_column_sortable');
function make_postcode_column_sortable($columns) {
    $columns['postcode'] = 'postcode'; // 'postcode' is the meta_key for sorting
    return $columns;
}
/**
 * Helper function to get the currently active SMS provider URL from the database.
 * @return string The active provider URL (e.g., '@txtlocal.co.uk') or empty string if none is set.
 */
function wc_custom_get_active_sms_provider_url() {
    $options = get_option( 'wc_sms_providers' );
    $active_key = isset( $options['active'] ) ? $options['active'] : '';
    $providers = isset( $options['providers'] ) ? $options['providers'] : array();

    if ( ! empty( $active_key ) && isset( $providers[ $active_key ] ) ) {
        return $providers[ $active_key ]['url'];
    }

    return ''; // Return empty if no active provider is found
}