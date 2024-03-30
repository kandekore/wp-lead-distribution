<?php
include_once plugin_dir_path(__FILE__) . '../includes/load-postcodes.php';

add_action('admin_menu', 'register_custom_admin_page');
function register_custom_admin_page() {
    add_menu_page(
        'Manage Postcode Areas',
        'Postcode Areas',
        'manage_options',
        'manage-postcode-areas',
        'render_custom_admin_page'
    );
}

/**
 * Render a custom admin page for managing postcode areas.
 */
function render_custom_admin_page() {
    // Nonce fields for security
    $nonce_action = 'save_postcode_areas_nonce_action';
    $nonce_name = 'save_postcode_areas_nonce';

    // Load or set default postcode areas
    $postcode_areas = get_option('custom_postcode_areas');
    if (!$postcode_areas) {
        $postcode_areas = load_postcode_areas_from_json(); // Ensure this function returns an array
        update_option('custom_postcode_areas', wp_json_encode($postcode_areas));
    } else {
        $postcode_areas = json_decode($postcode_areas, true);
    }

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

function register_user_credits_admin_page() {
    add_menu_page(
        'User Credits Management', // Page title
        'User Credits', // Menu title
        'manage_options', // Capability
        'user-credits-management', // Menu slug
        'render_user_credits_admin_page', // Function to display the page content
        'dashicons-money', // Icon URL (Dashicon)
        6 // Position
    );
}
add_action('admin_menu', 'register_user_credits_admin_page');

function render_user_credits_admin_page() {
    // Handle form submission for updating credits (if any)
    if (isset($_POST['update_credits']) && check_admin_referer('update_user_credits_nonce')) {
        // Assuming you have fields named 'user_id[]' and 'new_credits[]' in your form
        $user_ids = $_POST['user_id'] ?? [];
        $new_credits = $_POST['new_credits'] ?? [];
        
        foreach ($user_ids as $index => $user_id) {
            if (isset($new_credits[$index])) {
                update_user_meta($user_id, '_user_credits', sanitize_text_field($new_credits[$index]));
            }
        }
        
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
    
    // Start the form
    echo '<form method="post" action="">';
    wp_nonce_field('update_user_credits_nonce');
    
    // Table headers
    echo '<table class="wp-list-table widefat fixed striped"><thead><tr><th>User</th><th>Credits</th><th>New Credits</th></tr></thead><tbody>';
    
    foreach ($users_with_credits as $user) {
        $current_credits = get_user_meta($user->ID, '_user_credits', true);
        echo "<tr><td>{$user->display_name} ({$user->user_email})</td><td>{$current_credits}</td>";
        echo '<td><input type="hidden" name="user_id[]" value="' . esc_attr($user->ID) . '"><input type="number" name="new_credits[]" min="0" step="1" value="' . esc_attr($current_credits) . '"></td></tr>';
    }
    
    echo '</tbody></table>';
    echo '<input type="submit" name="update_credits" value="Update Credits" class="button button-primary">';
    echo '</form></div>';
}

function register_regions_and_users_admin_page() {
    add_menu_page(
        'Regions and Users with Credits', // Page Title
        'Regions & Users', // Menu Title
        'manage_options', // Capability required
        'regions-and-users-credits', // Menu slug
        'render_regions_and_users_admin_page', // Callback function
        'dashicons-admin-site-alt3', // Icon URL
        6 // Position
    );
}
add_action('admin_menu', 'register_regions_and_users_admin_page');


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
