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

    register_setting('my-custom-plugin-settings', 'master_admin_settings');
    add_settings_section('master_admin_section', 'Master Admin Settings', 'master_admin_section_cb', 'master-admin-settings');
    
    add_settings_field('master_admin_function_enabled', 'Enable Master Admin Function', 'master_admin_function_enabled_cb', 'master-admin-settings', 'master_admin_section');
    add_settings_field('master_admin_email', 'Master Admin Email', 'master_admin_email_cb', 'master-admin-settings', 'master_admin_section');
    add_settings_field('master_admin_mobile', 'Master Admin Mobile', 'master_admin_mobile_cb', 'master-admin-settings', 'master_admin_section');
    add_settings_field('master_admin_user_id', 'Master Admin User ID', 'master_admin_user_id_cb', 'master-admin-settings', 'master_admin_section');
    add_settings_field('minimum_year', 'Minimum Year', 'minimum_year_cb', 'master-admin-settings', 'master_admin_section');
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
    // Handle addition and subtraction of credits
    if (isset($_POST['action'], $_POST['user_id']) && in_array($_POST['action'], ['add', 'subtract']) && check_admin_referer('update_user_credits_nonce')) {
        $user_id = intval($_POST['user_id']);
        $current_credits = intval(get_user_meta($user_id, '_user_credits', true));
        $new_credits = $_POST['action'] === 'add' ? $current_credits + 1 : max($current_credits - 1, 0);
        
        update_user_meta($user_id, '_user_credits', $new_credits);
        echo "<div class='notice notice-success'><p>User credits updated successfully.</p></div>";
    }

    // Fetch users with credits
    $args = [
        'meta_key' => '_user_credits',
        'meta_value' => '0',
        'meta_compare' => '>',
        'fields' => 'all_with_meta',
    ];
    $users_with_credits = get_users($args);

    echo '<div class="wrap"><h1>User Credits Management</h1>';
    wp_nonce_field('update_user_credits_nonce');

    // Table headers
    echo '<table class="wp-list-table widefat fixed striped"><thead><tr><th>User</th><th>Credits</th><th>Actions</th></tr></thead><tbody>';

    foreach ($users_with_credits as $user) {
        $current_credits = get_user_meta($user->ID, '_user_credits', true);
        $edit_user_link = get_edit_user_link($user->ID);
        
        // User row with Edit link
        echo "<tr><td><a href='{$edit_user_link}'>{$user->display_name}</a> ({$user->user_email})</td><td>{$current_credits}</td>";

        // Action buttons
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
    echo '<div class="wrap"><h1>Regions and Users with Credits</h1>';
    
    $users_by_region = get_customers_by_region();
    $all_users = get_users();

    foreach ($users_by_region as $region => $user_ids) {
        echo "<h2>" . esc_html($region) . "</h2>";

        // Display users and their credits for the region
        foreach ($user_ids as $user_id) {
            $user_info = get_userdata($user_id);
            $user_credits = get_user_meta($user_id, '_user_credits', true);
            $selected_postcode_areas = json_decode(get_user_meta($user_id, 'selected_postcode_areas', true), true);

            // Generate the edit user link
            $edit_user_link = get_edit_user_link($user_id);

            // Display the user's name as a link to their edit page
            echo "<p><strong><a href='" . esc_url($edit_user_link) . "'>" . esc_html($user_info->display_name) . "</a> (" . esc_html($user_info->user_email) . ")</strong>: ";
            echo "Credits: " . esc_html($user_credits) . "; ";

            // Show postcodes covered by the user in this region
            echo "Postcodes: ";
            if (!empty($selected_postcode_areas[$region])) {
                echo implode(', ', $selected_postcode_areas[$region]);
            } else {
                echo "None";
            }

            echo "</p>";
        }
    }

    echo '</div>';
}
