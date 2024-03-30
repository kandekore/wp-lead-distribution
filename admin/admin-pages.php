<?php
include_once plugin_dir_path(__FILE__) . '../includes/load-postcodes.php';
function register_my_plugin_menu_pages() {
    // Add the main menu page
    add_menu_page(
        'Lead Management', // Page title
        'Lead Management', // Menu title
        'manage_options', // Capability
        'lead-management-dashboard', // Menu slug
        'render_lead_management_dashboard', // Function to display the dashboard page content
        'dashicons-admin-site', // Icon URL
        6 // Position
    );

    // Add submenu for Managing Postcode Areas
    add_submenu_page(
        'lead-management-dashboard', // Parent slug
        'Manage Postcode Areas', // Page title
        'Postcode Areas', // Menu title
        'manage_options', // Capability
        'manage-postcode-areas', // Menu slug
        'render_custom_admin_page' // Function to display the page content
    );

    // Add submenu for User Credits Management
    add_submenu_page(
        'lead-management-dashboard', // Parent slug
        'User Credits Management', // Page title
        'User Credits', // Menu title
        'manage_options', // Capability
        'user-credits-management', // Menu slug
        'render_user_credits_admin_page' // Function to display the page content
    );

    // Add submenu for Regions and Users with Credits
    add_submenu_page(
        'lead-management-dashboard', // Parent slug
        'Regions and Users with Credits', // Page title
        'Regions & Users', // Menu title
        'manage_options', // Capability
        'regions-and-users-credits', // Menu slug
        'render_regions_and_users_admin_page' // Function to display the page content
    );

    // Remove the duplicate menu item for the main menu page.
    remove_submenu_page('lead-management-dashboard', 'lead-management-dashboard');
}

// Hook into the 'admin_menu' action to register the menu pages
add_action('admin_menu', 'register_my_plugin_menu_pages');


// Define your page rendering functions below
function render_lead_management_dashboard() {
    echo '<h1>Lead Management Dashboard</h1>';
    // Dashboard content here
}
/**
 * Render a custom admin page for managing postcode areas.
 */function render_custom_admin_page() {
    
    // Enqueue jQuery UI for accordion if not already included
    wp_enqueue_script('jquery-ui-accordion');

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
    $saved_postcode_areas = json_decode(get_option('custom_postcode_areas'), true) ?: [];

    // Begin the form output
    echo '<div class="wrap"><h1>Manage Postcode Areas</h1><form method="post" action="">';
    wp_nonce_field($nonce_action, $nonce_name);

    echo '<div id="postcode-accordion">'; // Start of accordion container

    // Iterate through regions and codes, rendering checkboxes within accordion sections
    foreach ($saved_postcode_areas as $region => $codes) {
        echo '<h3>' . esc_html($region) . '</h3>';
        echo '<div>'; // Start of accordion content
        echo "<label><input type='checkbox' class='region-select-all' data-region='" . esc_attr($region) . "'> Select All in " . esc_html($region) . "</label><br>";
        foreach ($codes as $code) {
            $is_checked = in_array($code, $codes, true) ? 'checked="checked"' : '';
            echo "<label><input type='checkbox' class='region " . esc_attr($region) . "' name='postcode_areas[" . esc_attr($region) . "][]' value='" . esc_attr($code) . "' $is_checked> " . esc_html($code) . "</label><br>";
        }
        echo '</div>'; // End of accordion content
    }

    echo '</div>'; // End of accordion container

    echo '<input type="submit" name="save_postcode_areas" value="Save Postcode Areas" class="button button-primary">';
    echo '</form></div>';

    // Initialize the accordion feature
    echo "<script>
    jQuery(document).ready(function($) {
        $('#postcode-accordion').accordion({
            collapsible: true,
            heightStyle: 'content'
        });
    });
    </script>";

    echo "<style>
    #postcode-accordion .ui-accordion-header {
        background-color: #0073aa;
        color: #ffffff;
        font-weight: bold;
        padding: 10px 15px;
        border-top: 1px solid #ffffff;
    }
    #postcode-accordion .ui-accordion-header.ui-state-active {
        background-color: #005177;
    }
    #postcode-accordion .ui-accordion-header.ui-state-default {
        background-color: #0073aa;
    }
    #postcode-accordion .ui-accordion-content {
        background-color: #f1f1f1;
        border: 1px solid #ddd;
        padding: 15px;
        margin-bottom: 10px;
    }
    .region-select-all {
        margin-bottom: 10px;
        display: inline-block;
        font-weight: normal;
    }
    .region {
        margin-left: 20px;
        display: block;
    }
</style>";
    
}



function render_user_credits_admin_page() {
    // Handle addition and subtraction of credits
    if (isset($_POST['action'], $_POST['user_id']) && in_array($_POST['action'], ['add', 'subtract']) && check_admin_referer('update_user_credits_nonce')) {
        $user_id = intval($_POST['user_id']);
        $current_credits = intval(get_user_meta($user_id, '_user_credits', true));
        $new_credits = $_POST['action'] === 'add' ? $current_credits + 10 : max($current_credits - 10, 0);
        
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
        echo '<input type="submit" value="+10 Credits" class="button button-primary">';
        echo '</form>';

        echo '<form method="post" action="" style="display: inline-block; margin-left: 10px;">';
        wp_nonce_field('update_user_credits_nonce');
        echo '<input type="hidden" name="user_id" value="' . esc_attr($user->ID) . '">';
        echo '<input type="hidden" name="action" value="subtract">';
        echo '<input type="submit" value="-10 Credits" class="button">';
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
