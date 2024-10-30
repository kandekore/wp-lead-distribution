<?php
include_once plugin_dir_path(__FILE__) . '../includes/load-postcodes.php';
function register_my_plugin_menu_pages() {
    add_menu_page('Lead Management', 'Lead Management', 'manage_options', 'lead-management-dashboard', 'render_lead_management_dashboard', 'dashicons-admin-site', 6);

    add_submenu_page('lead-management-dashboard', 'Manage Postcode Areas', 'Postcode Areas', 'manage_options', 'manage-postcode-areas', 'render_custom_admin_page');
    add_submenu_page('lead-management-dashboard', 'User Credits Management', 'User Credits', 'manage_options', 'user-credits-management', 'render_user_credits_admin_page');
    add_submenu_page('lead-management-dashboard', 'Regions and Users with Credits', 'Regions & Users', 'manage_options', 'regions-and-users-credits', 'render_regions_and_users_admin_page');
    add_submenu_page('lead-management-dashboard', 'Master Admin Settings', 'Master Admin Settings', 'manage_options', 'master-admin-settings', 'master_admin_settings_page');
    add_submenu_page('lead-management-dashboard', 'Fallback User Settings', 'Fallback User Settings', 'manage_options', 'fallback-user-settings', 'render_fallback_user_settings_page');

    remove_submenu_page('lead-management-dashboard', 'lead-management-dashboard');
}

add_action('admin_menu', 'register_my_plugin_menu_pages');
add_action('admin_init', 'register_my_custom_plugin_settings');

function register_my_custom_plugin_settings() {
    register_setting('custom_fallback_settings', 'fallback_settings');
    add_settings_section('fallback_user_section', 'Fallback User Settings', 'fallback_user_section_cb', 'fallback-user-settings');
    
    add_settings_field('fallback_user_enabled', 'Enable Fallback User', 'fallback_user_enabled_cb', 'fallback-user-settings', 'fallback_user_section');
    add_settings_field('fallback_user_email', 'Fallback User Email', 'fallback_user_email_cb', 'fallback-user-settings', 'fallback_user_section');
    add_settings_field('fallback_user_mobile', 'Fallback User Mobile', 'fallback_user_mobile_cb', 'fallback-user-settings', 'fallback_user_section');
    add_settings_field('fallback_user_id', 'Fallback User ID', 'fallback_user_id_cb', 'fallback-user-settings', 'fallback_user_section');
    add_settings_field('fallback_user_api_endpoint', 'Fallback User API Endpoint', 'fallback_user_api_endpoint_cb', 'fallback-user-settings', 'fallback_user_section');

    register_setting('my-custom-plugin-settings', 'master_admin_settings');
    add_settings_section('master_admin_section', 'Master Admin Settings', 'master_admin_section_cb', 'master-admin-settings');
    
    add_settings_field('master_admin_function_enabled', 'Enable Master Admin Function', 'master_admin_function_enabled_cb', 'master-admin-settings', 'master_admin_section');
    add_settings_field('master_admin_email', 'Master Admin Email', 'master_admin_email_cb', 'master-admin-settings', 'master_admin_section');
    add_settings_field('master_admin_mobile', 'Master Admin Mobile', 'master_admin_mobile_cb', 'master-admin-settings', 'master_admin_section');
    add_settings_field('master_admin_user_id', 'Master Admin User ID', 'master_admin_user_id_cb', 'master-admin-settings', 'master_admin_section');
    add_settings_field('minimum_year', 'Minimum Year', 'minimum_year_cb', 'master-admin-settings', 'master_admin_section');
}

function fallback_user_api_endpoint_cb() {
    $options = get_option('fallback_settings');
    $endpoint = isset($options['fallback_user_api_endpoint']) ? $options['fallback_user_api_endpoint'] : '';
    echo '<input type="url" id="fallback_user_api_endpoint" name="fallback_settings[fallback_user_api_endpoint]" value="' . esc_attr($endpoint) . '"/>';
}
function master_admin_section_cb() {
    echo '<p>Settings for Master Admin functionality.</p>';
}

// Callback for "Enable Master Admin Function"
function master_admin_function_enabled_cb() {
    $options = get_option('master_admin_settings');
    $checked = isset($options['master_admin_function_enabled']) ? checked(1, $options['master_admin_function_enabled'], false) : '';
    echo '<input type="checkbox" id="master_admin_function_enabled" name="master_admin_settings[master_admin_function_enabled]" value="1"' . $checked . '>';
}

// Callback for "Master Admin Email"
function master_admin_email_cb() {
    $options = get_option('master_admin_settings');
    $email = isset($options['master_admin_email']) ? $options['master_admin_email'] : '';
    echo '<input type="email" id="master_admin_email" name="master_admin_settings[master_admin_email]" value="' . esc_attr($email) . '"/>';
}

// Callback for "Master Admin Mobile"
function master_admin_mobile_cb() {
    $options = get_option('master_admin_settings');
    $mobile = isset($options['master_admin_mobile']) ? $options['master_admin_mobile'] : '';
    echo '<input type="text" id="master_admin_mobile" name="master_admin_settings[master_admin_mobile]" value="' . esc_attr($mobile) . '"/>';
}

// Callback for "Master Admin User ID"
function master_admin_user_id_cb() {
    $options = get_option('master_admin_settings');
    $user_id = isset($options['master_admin_user_id']) ? $options['master_admin_user_id'] : '';
    echo '<input type="number" id="master_admin_user_id" name="master_admin_settings[master_admin_user_id]" value="' . esc_attr($user_id) . '"/>';
}

// Callback for "Minimum Year"
function minimum_year_cb() {
    $options = get_option('master_admin_settings');
    $year = isset($options['minimum_year']) ? $options['minimum_year'] : '';
    echo '<input type="number" id="minimum_year" name="master_admin_settings[minimum_year]" value="' . esc_attr($year) . '"/>';
}

function fallback_user_section_cb() {
    echo '<p>Settings for the Fallback User who receives leads when no other recipients are available.</p>';
}

function fallback_user_enabled_cb() {
    $options = get_option('fallback_settings');
    $checked = isset($options['fallback_user_enabled']) ? checked(1, $options['fallback_user_enabled'], false) : '';
    echo '<input type="checkbox" id="fallback_user_enabled" name="fallback_settings[fallback_user_enabled]" value="1"' . $checked . '>';
}

function fallback_user_email_cb() {
    $options = get_option('fallback_settings');
    $email = isset($options['fallback_user_email']) ? $options['fallback_user_email'] : '';
    echo '<input type="email" id="fallback_user_email" name="fallback_settings[fallback_user_email]" value="' . esc_attr($email) . '"/>';
}

function fallback_user_mobile_cb() {
    $options = get_option('fallback_settings');
    $mobile = isset($options['fallback_user_mobile']) ? $options['fallback_user_mobile'] : '';
    echo '<input type="text" id="fallback_user_mobile" name="fallback_settings[fallback_user_mobile]" value="' . esc_attr($mobile) . '"/>';
}

function fallback_user_id_cb() {
    $options = get_option('fallback_settings');
    $user_id = isset($options['fallback_user_id']) ? $options['fallback_user_id'] : '';
    echo '<input type="number" id="fallback_user_id" name="fallback_settings[fallback_user_id]" value="' . esc_attr($user_id) . '"/>';
}

// Inside your existing my_custom_plugin_settings function, add the following:

function master_admin_settings_page() {
    echo '<div class="wrap"><h2>' . esc_html(get_admin_page_title()) . '</h2><form action="options.php" method="post">';
    settings_fields('my-custom-plugin-settings');
    do_settings_sections('master-admin-settings');
    submit_button('Save Settings');
    echo '</form></div>';
}

function render_fallback_user_settings_page() {
    echo '<div class="wrap"><h2>' . esc_html(get_admin_page_title()) . '</h2><form action="options.php" method="post">';
    settings_fields('custom_fallback_settings');
    do_settings_sections('fallback-user-settings');
    submit_button('Save Settings');
    echo '</form></div>';
}

// Define your page rendering functions below
function render_lead_management_dashboard() {
    echo '<h1>Lead Management Dashboard</h1>';
    // Dashboard content here
}
/**
 * Render a custom admin page for managing postcode areas.*/
function render_custom_admin_page() {
    // Nonce fields for security
    $nonce_action = 'save_postcode_areas_nonce_action';
    $nonce_name = 'save_postcode_areas_nonce';

    // Check if form is submitted and nonce is verified
    if (!empty($_POST['save_postcode_areas']) && check_admin_referer($nonce_action, $nonce_name)) {
        // Sanitize and save the selected postcode areas
        $sanitized_areas = [];
        foreach ($_POST['postcode_areas'] as $region => $codes) {
            if (is_array($codes)) {
                $sanitized_areas[$region] = array_map('sanitize_text_field', $codes);
            }
        }
        update_option('custom_postcode_areas', wp_json_encode($sanitized_areas));
        echo "<div class='notice notice-success'><p>Postcode areas updated successfully.</p></div>";
    }

    // Load the current saved or default postcode areas
    $postcode_areas = load_postcode_areas_from_json(); // Ensure this function returns an array
    $saved_postcode_areas = json_decode(get_option('custom_postcode_areas', wp_json_encode($postcode_areas)), true);
    if (!is_array($saved_postcode_areas)) {
        $saved_postcode_areas = [];
    }

    // Begin the form output
    echo '<div class="wrap"><h1>Manage Postcode Areas</h1><form method="post" action="">';
    wp_nonce_field($nonce_action, $nonce_name);

    // Iterate through regions and codes, rendering checkboxes
    foreach ($postcode_areas as $region => $codes) {
      // Inside your foreach loop for regions in render_custom_admin_page
echo "<h3>" . esc_html($region) . "</h3>";
echo "<label><input type='checkbox' class='region-select-all' data-region='" . esc_attr($region) . "'> Select All in " . esc_html($region) . "</label><br>";

        
        foreach ($codes as $code) {
            $is_checked = in_array($code, $saved_postcode_areas[$region] ?? [], true) ? 'checked="checked"' : '';
            echo "<label><input type='checkbox' class='region " . esc_attr($region) . "' name='postcode_areas[" . esc_attr($region) . "][]' value='" . esc_attr($code) . "' $is_checked> " . esc_html($code) . "</label><br>";
        }
    }
    ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.region-select-all').forEach(function(selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', function() {
                let region = this.getAttribute('data-region');
                let checkboxes = document.querySelectorAll('input[name="postcode_areas['+region+'][]"]');
                checkboxes.forEach(function(checkbox) {
                    checkbox.checked = selectAllCheckbox.checked;
                });
            });
        });
    });
</script>
<?php

    echo '<input type="submit" name="save_postcode_areas" value="Save Postcode Areas" class="button button-primary">';
    echo '</form></div>';
}

function enqueue_custom_admin_script() {
    // Use get_template_directory_uri() for a theme or plugins_url() for a plugin.
    wp_enqueue_script('custom-admin-js', get_template_directory_uri() . 'custom-admin.js', array('jquery', 'jquery-ui-accordion'), null, true);
}
add_action('admin_enqueue_scripts', 'enqueue_custom_admin_script');


function render_user_credits_admin_page() {
    // Handle addition and subtraction of credits for pre-pay users
    if (isset($_POST['action'], $_POST['user_id']) && in_array($_POST['action'], ['add', 'subtract']) && check_admin_referer('update_user_credits_nonce')) {
        $user_id = intval($_POST['user_id']);
        $current_credits = intval(get_user_meta($user_id, '_user_credits', true));
        $new_credits = $_POST['action'] === 'add' ? $current_credits + 1 : max($current_credits - 1, 0);
        
        update_user_meta($user_id, '_user_credits', $new_credits);
        echo "<div class='notice notice-success'><p>User credits updated successfully.</p></div>";
    }

    // Fetch pre-pay users with credits
    $args = [
        'meta_key' => '_user_credits',
        'meta_value' => '0',
        'meta_compare' => '>',
        'fields' => 'all_with_meta',
    ];
    $users_with_credits = get_users($args);

    // Fetch post-pay users
    $post_pay_users = get_users([
        'role' => 'post_pay',
        'fields' => 'all_with_meta',
    ]);

    echo '<div class="wrap"><h1>User Credits Management</h1>';
    wp_nonce_field('update_user_credits_nonce');

    // Table headers
    echo '<table class="wp-list-table widefat fixed striped"><thead><tr><th>User</th><th>Credits</th><th>Lead Reception</th><th>Actions</th></tr></thead><tbody>';

    // Display pre-pay users with credits
    foreach ($users_with_credits as $user) {
        $current_credits = get_user_meta($user->ID, '_user_credits', true);
        $edit_user_link = get_edit_user_link($user->ID);

        echo "<tr><td><a href='{$edit_user_link}'>{$user->display_name}</a> ({$user->user_email})</td><td>{$current_credits}</td>";

        // Lead reception for pre-pay users (always enabled if they have credits)
        echo '<td>Enabled (Credits)</td>';

        // Action buttons for managing credits
        echo '<td>';
        echo '<form method="post" action="" style="display: inline-block;">';
        wp_nonce_field('update_user_credits_nonce');
        echo '<input type="hidden" name="user_id" value="' . esc_attr($user->ID) . '">';
        echo '<input type="hidden" name="action" value="add">';
        echo '<input type="submit" value="+1 Credits" class="button button-primary">';
        echo '</form>';

        echo '<form method="post" action="" style="display: inline-block; margin-left: 10px;">';
        wp_nonce_field('update_user_credits_nonce');
        echo '<input type="hidden" name="user_id" value="' . esc_attr($user->ID) . '">';
        echo '<input type="hidden" name="action" value="subtract">';
        echo '<input type="submit" value="-1 Credits" class="button">';
        echo '</form>';
        echo '</td></tr>';
    }

    // Display post-pay users
    foreach ($post_pay_users as $user) {
        $lead_reception = get_user_meta($user->ID, 'enable_lead_reception', true) === '1' ? 'Enabled' : 'Disabled';
        $edit_user_link = get_edit_user_link($user->ID);

        echo "<tr><td><a href='{$edit_user_link}'>{$user->display_name}</a> ({$user->user_email})</td><td>N/A (Post Pay)</td>";

        // Lead reception status for post-pay users
        echo "<td>{$lead_reception}</td>";

        // No action buttons for post-pay users (no credits to manage)
        echo '<td>N/A</td></tr>';
    }

    echo '</tbody></table>';
    echo '</div>';
}

function render_lead_management_page() {
    // Nonce field for security
    $nonce_action = 'lead_management_filter_action';
    $nonce_name = 'lead_management_filter_nonce';

    // Display page title
    echo '<div class="wrap"><h1>Lead Management</h1>';

    // Start the form for filters
    echo '<form id="lead-management-filters" method="GET">';
    echo '<input type="hidden" name="page" value="lead-management"/>';

    // Add nonce for security
    wp_nonce_field($nonce_action, $nonce_name);

    // Dropdown for filtering by author
    wp_dropdown_users([
        'show_option_all' => 'All Authors',
        'name' => 'author',
        'role' => 'author', // Changed to lowercase
        'selected' => isset($_GET['author']) ? $_GET['author'] : 0,
    ]);
    

    // Dropdown for filtering by date
    ?>
    <select name="lead_date_filter">
        <option value="">All Dates</option>
        <option value="today" <?php selected(isset($_GET['lead_date_filter']) ? $_GET['lead_date_filter'] : '', 'today'); ?>>Today</option>
        <option value="yesterday" <?php selected(isset($_GET['lead_date_filter']) ? $_GET['lead_date_filter'] : '', 'yesterday'); ?>>Yesterday</option>
        <option value="this_week" <?php selected(isset($_GET['lead_date_filter']) ? $_GET['lead_date_filter'] : '', 'this_week'); ?>>This Week</option>
        <option value="last_week" <?php selected(isset($_GET['lead_date_filter']) ? $_GET['lead_date_filter'] : '', 'last_week'); ?>>Last Week</option>
        <option value="this_month" <?php selected(isset($_GET['lead_date_filter']) ? $_GET['lead_date_filter'] : '', 'this_month'); ?>>This Month</option>
        <option value="last_month" <?php selected(isset($_GET['lead_date_filter']) ? $_GET['lead_date_filter'] : '', 'last_month'); ?>>Last Month</option>
    </select>
    <?php
    // Submit button for the filters
    submit_button('Filter', 'primary', 'filter_action', false);

    echo '</form>';

    // Placeholder for the leads table (You will implement this part based on how you store and display leads)
    echo '<h2>Leads List</h2>';
    // TODO: Implement leads list table based on the applied filters

    echo '</div>';
}





function render_regions_and_users_admin_page() {
    echo '<div class="wrap"><h1>Regions and Users</h1>';
    
    // Fetch users with credits (pre-pay) and post-pay users
    $users_with_credits = get_users([
        'meta_key' => '_user_credits',
        'meta_value' => '0',
        'meta_compare' => '>',
        'fields' => 'all_with_meta',
    ]);

    $post_pay_users = get_users([
        'role' => 'post_pay',
        'fields' => 'all_with_meta',
    ]);

    // Merge both pre-pay and post-pay users
    $all_users = array_merge($users_with_credits, $post_pay_users);

    // Load postcodes assigned to users (based on your `get_customers_by_region` logic)
    $users_by_region = get_customers_by_region(); // Assuming this groups users by region

    if (empty($users_by_region)) {
        echo "<p>No users found for the regions.</p>";
        return;
    }

    foreach ($users_by_region as $region => $user_ids) {
        echo "<h2>" . esc_html($region) . "</h2>";

        foreach ($user_ids as $user_id) {
            $user_info = get_userdata($user_id);

            // Skip users that are not in pre-pay or post-pay roles
            if (!$user_info) {
                continue;
            }

            $is_post_pay = in_array('post_pay', $user_info->roles);
            $user_credits = get_user_meta($user_id, '_user_credits', true);

            // Get the lead reception status for post-pay users
            $lead_reception = $is_post_pay ? (get_user_meta($user_id, 'enable_lead_reception', true) === '1' ? 'Enabled' : 'Disabled') : 'N/A';

            // Retrieve the user's selected postcode areas (assuming stored in user meta)
            $selected_postcode_areas = json_decode(get_user_meta($user_id, 'selected_postcode_areas', true), true);

            // Display user info and links
            $edit_user_link = get_edit_user_link($user_id);
            echo "<p><strong><a href='" . esc_url($edit_user_link) . "'>" . esc_html($user_info->display_name) . "</a> ({$user_info->user_email})</strong>: ";

            // Show credits for pre-pay users and lead reception for post-pay users
            if ($is_post_pay) {
                echo "Lead Reception: {$lead_reception}";
            } else {
                echo "Credits: {$user_credits}";
            }

            // Display the user's assigned postcodes for the region
            echo "; Postcodes: ";
            if (!empty($selected_postcode_areas[$region])) {
                echo implode(', ', $selected_postcode_areas[$region]);
            } else {
                echo "None";
            }

            echo "</p>";
        }
    }
    echo '<pre>';
    print_r($users_by_region);
    echo '</pre>';
    echo '</div>';
}


function render_lead_reports_page() {
    // Nonce field for security
    $nonce_action = 'lead_reports_filter_action';
    $nonce_name = 'lead_reports_filter_nonce';

    // Display page title
    echo '<div class="wrap"><h1>Lead Reports</h1>';

    // Start the form for filters
    echo '<form id="lead-reports-filters" method="GET">';
    echo '<input type="hidden" name="page" value="lead-reports"/>';

    // Add nonce for security
    wp_nonce_field($nonce_action, $nonce_name);

    // Dropdown for filtering by date
    $selected_filter = isset($_GET['lead_date_filter']) ? $_GET['lead_date_filter'] : 'today';
    ?>
    <select name="lead_date_filter">
        <option value="today" <?php selected($selected_filter, 'today'); ?>>Today</option>
        <option value="yesterday" <?php selected($selected_filter, 'yesterday'); ?>>Yesterday</option>
        <option value="this_week" <?php selected($selected_filter, 'this_week'); ?>>This Week</option>
        <option value="last_week" <?php selected($selected_filter, 'last_week'); ?>>Last Week</option>
        <option value="this_month" <?php selected($selected_filter, 'this_month'); ?>>This Month</option>
        <option value="last_month" <?php selected($selected_filter, 'last_month'); ?>>Last Month</option>
    </select>
    <?php
    // Submit button for the filters
    submit_button('Filter', 'primary', 'filter_action', false);
    echo '</form>';

    // Fetch the data based on the selected filter
    $date_query = get_lead_date_query($selected_filter);

    // Query the leads based on the date range and group by assigned_user meta field
    $args = [
        'post_type' => 'lead', // Assuming 'lead' is the post type for leads
        'posts_per_page' => -1,
        'meta_key' => 'assigned_user', // Meta key for assigned user
        'date_query' => $date_query,
        'fields' => 'ids', // Only get the post IDs to count
    ];

    $leads = new WP_Query($args);

    // Debugging information: Show the total number of leads found
    echo '<h2>Total Leads: ' . $leads->found_posts . '</h2>';

    // Check if there are leads and display them
    if ($leads->have_posts()) {
        echo '<h3>Leads found for the selected period.</h3>';

        // Initialize an array to store lead counts by assigned user
        $user_lead_counts = [];

        // Loop through all leads and count them per assigned user
        foreach ($leads->posts as $lead_id) {
            $assigned_user_id = get_post_meta($lead_id, 'assigned_user', true);

            if (!empty($assigned_user_id)) {
                // Increment the lead count for the assigned user
                if (!isset($user_lead_counts[$assigned_user_id])) {
                    $user_lead_counts[$assigned_user_id] = 0;
                }
                $user_lead_counts[$assigned_user_id]++;
            }
        }

        // Display the lead counts in a table with an additional column for remaining credits
        echo '<h2>Number of Leads Per User</h2>';
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr><th>User</th><th>Leads Count</th><th>Remaining Credits</th></tr></thead><tbody>';

        foreach ($user_lead_counts as $user_id => $lead_count) {
            $user_info = get_userdata($user_id);
            $user_display_name = $user_info ? $user_info->display_name : 'Unknown User';

            // Retrieve the remaining credits for the user
            $remaining_credits = get_user_meta($user_id, '_user_credits', true);

            // Construct the URL for filtering by this user and the selected date
            $url = admin_url("edit.php?post_type=lead&lead_date_filter={$selected_filter}&assigned_user={$user_id}&filter_action=Filter");

            echo "<tr>";
            echo "<td><a href='" . esc_url($url) . "'>{$user_display_name}</a></td>";
            echo "<td>{$lead_count}</td>";
            echo "<td>{$remaining_credits}</td>";
            echo "</tr>";
        }

        echo '</tbody></table>';
    } else {
        echo '<p>No leads found for the selected period.</p>';
    }

    wp_reset_postdata();
    echo '</div>';
}



function get_lead_date_query($selected_filter) {
    $start_of_week = get_option('start_of_week', 0); // 0 (Sunday) to 6 (Saturday)
    $current_day_of_week = date('w'); // Current day of week

    $date_query = [];

    switch ($selected_filter) {
        case 'today':
            $today = current_time('Y-m-d');
            $date_query = [
                'after' => $today . ' 00:00:00',
                'before' => $today . ' 23:59:59',
                'inclusive' => true,
            ];
            break;
        case 'yesterday':
            $yesterday = date('Y-m-d', strtotime('-1 day'));
            $date_query = [
                'after' => $yesterday . ' 00:00:00',
                'before' => $yesterday . ' 23:59:59',
                'inclusive' => true,
            ];
            break;
        case 'this_week':
            $days_since_start_of_week = ( $current_day_of_week - $start_of_week + 7 ) % 7;
            $startOfWeek = date('Y-m-d', strtotime('-' . $days_since_start_of_week . ' days'));
            $endOfWeek = date('Y-m-d', strtotime($startOfWeek . ' +6 days'));
            $date_query = [
                'after' => $startOfWeek . ' 00:00:00',
                'before' => $endOfWeek . ' 23:59:59',
                'inclusive' => true,
            ];
            break;
        case 'last_week':
            $days_since_start_of_week = ( $current_day_of_week - $start_of_week + 7 ) % 7;
            $startOfThisWeek = date('Y-m-d', strtotime('-' . $days_since_start_of_week . ' days'));
            $startOfLastWeek = date('Y-m-d', strtotime($startOfThisWeek . ' -7 days'));
            $endOfLastWeek = date('Y-m-d', strtotime($startOfThisWeek . ' -1 day'));
            $date_query = [
                'after' => $startOfLastWeek . ' 00:00:00',
                'before' => $endOfLastWeek . ' 23:59:59',
                'inclusive' => true,
            ];
            break;
        case 'this_month':
            $start_of_month = date('Y-m-01');
            $date_query = [
                'after' => $start_of_month . ' 00:00:00',
                'inclusive' => true,
            ];
            break;
        case 'last_month':
            $start_of_last_month = date('Y-m-01', strtotime('first day of last month'));
            $end_of_last_month = date('Y-m-t', strtotime('last day of last month'));
            $date_query = [
                'after' => $start_of_last_month . ' 00:00:00',
                'before' => $end_of_last_month . ' 23:59:59',
                'inclusive' => true,
            ];
            break;
        // Add other cases as needed
    }

    return $date_query;
}

function load_postcodes_from_json() {
    $file_path = plugin_dir_path(__FILE__) . '../includes/postcodes.json';
    $json_data = file_get_contents($file_path);
    return json_decode($json_data, true);
}
// Main report rendering function
// Main report rendering function
function render_leads_by_postcode_report() {
    echo '<div class="wrap"><h1>Leads by Postcode Area Report</h1>';

    // Handle the time filter selection
    $selected_filter = isset($_GET['lead_date_filter']) ? $_GET['lead_date_filter'] : 'today';
    $order_by = isset($_GET['order_by']) ? $_GET['order_by'] : 'leads';
    $order = isset($_GET['order']) ? $_GET['order'] : 'desc';

    // Time filter dropdown
    echo '<form method="GET">';
    echo '<input type="hidden" name="page" value="leads-by-postcode-report"/>';
    ?>
    <label for="lead_date_filter">Filter by date: </label>
    <select name="lead_date_filter">
        <option value="today" <?php selected($selected_filter, 'today'); ?>>Today</option>
        <option value="yesterday" <?php selected($selected_filter, 'yesterday'); ?>>Yesterday</option>
        <option value="this_week" <?php selected($selected_filter, 'this_week'); ?>>This Week</option>
        <option value="last_week" <?php selected($selected_filter, 'last_week'); ?>>Last Week</option>
        <option value="this_month" <?php selected($selected_filter, 'this_month'); ?>>This Month</option>
        <option value="last_month" <?php selected($selected_filter, 'last_month'); ?>>Last Month</option>
    </select>
    <?php
    submit_button('Filter', 'primary', 'filter_action', false);
    echo '</form>';

    // Load the postcode areas from the JSON file
    $postcode_areas = load_postcodes_from_json();

    // Initialize array to store lead counts per postcode area
    $lead_counts_by_area = [];

    // Fetch the date query based on the selected filter
    $date_query = get_lead_date_query($selected_filter);

    // Fetch all leads (assuming 'lead' is the post type)
    $args = [
        'post_type' => 'lead',
        'posts_per_page' => -1,
        'fields' => 'ids', // Only get post IDs to speed up the query
        'date_query' => [$date_query], // Apply the date filter
    ];

    $leads = new WP_Query($args);

    if ($leads->have_posts()) {
        // Loop through each lead
        foreach ($leads->posts as $lead_id) {
            $lead_postcode = get_post_meta($lead_id, 'postcode', true);

            // Match the lead's postcode against the postcode areas
            foreach ($postcode_areas as $region => $postcodes) {
                foreach ($postcodes as $postcode) {
                    // Handle wildcard postcodes (e.g., "E#" matches "E1", "E2", etc.)
                    if (strpos($postcode, "#") !== false) {
                        $pattern = str_replace("#", "[0-9]", $postcode);
                        if (preg_match("/^$pattern/", $lead_postcode)) {
                            if (!isset($lead_counts_by_area[$postcode])) {
                                $lead_counts_by_area[$postcode] = ['region' => $region, 'count' => 0];
                            }
                            $lead_counts_by_area[$postcode]['count']++;
                        }
                    } else {
                        // Direct match for postcodes without wildcards
                        if (strpos($lead_postcode, $postcode) === 0) {
                            if (!isset($lead_counts_by_area[$postcode])) {
                                $lead_counts_by_area[$postcode] = ['region' => $region, 'count' => 0];
                            }
                            $lead_counts_by_area[$postcode]['count']++;
                        }
                    }
                }
            }
        }
    }

    // Sorting logic
    $order = strtolower($order);

    if ($order_by === 'leads') {
        uasort($lead_counts_by_area, function($a, $b) use ($order) {
            if ($a['count'] == $b['count']) {
                return 0;
            }
            if ($order === 'asc') {
                return ($a['count'] < $b['count']) ? -1 : 1;
            } else {
                return ($a['count'] > $b['count']) ? -1 : 1;
            }
        });
        $sorted_lead_counts = $lead_counts_by_area;
    } else {
        $sorted_lead_counts = $lead_counts_by_area;
    }

    // Display the report with the total count
    $total_leads = array_sum(array_column($lead_counts_by_area, 'count'));
    echo '<h2>Total Leads: ' . $total_leads . '</h2>';

    // Column headers with sorting links
    $new_order = ($order === 'asc') ? 'desc' : 'asc';
    $leads_url = add_query_arg([
        'order_by' => 'leads',
        'order' => $new_order,
        'lead_date_filter' => $selected_filter,
    ], menu_page_url('leads-by-postcode-report', false));

    echo '<table class="wp-list-table widefat fixed striped">';
    echo '<thead>';
    echo '<tr>';
    echo '<th>Region</th>';
    echo '<th>Postcode</th>';
    echo '<th><a href="' . esc_url($leads_url) . '">Leads Count</a></th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';

    foreach ($sorted_lead_counts as $postcode => $data) {
        echo '<tr>';
        echo '<td>' . esc_html($data['region']) . '</td>';
        echo '<td>' . esc_html($postcode) . '</td>';
        echo '<td>' . esc_html($data['count']) . '</td>';
        echo '</tr>';
    }

    echo '</tbody></table>';

    wp_reset_postdata(); // Always reset the query after a custom WP_Query
    echo '</div>';
}


add_action('admin_menu', function() {
    add_submenu_page(
        'lead-management-dashboard',  // Parent menu slug
        'Leads by Postcode Area',     // Page title
        'Leads by Postcode Area',     // Menu title
        'manage_options',             // Capability
        'leads-by-postcode-report',   // Menu slug
        'render_leads_by_postcode_report' // Callback function to render the page
    );
});
function register_lead_report_menu_page() {
    // Add "Lead Reports" as the first submenu under 'Lead Management Dashboard'
    add_submenu_page(
        'lead-management-dashboard',   // Parent menu slug
        'Lead Reports',                // Page title
        'Lead Reports',                // Menu title
        'manage_options',              // Capability required
        'lead-reports',                // Menu slug
        'render_lead_reports_page',    // Function to render the page
        0                              // Position - 0 makes it the first item
    );
}
add_action('admin_menu', 'register_lead_report_menu_page');
