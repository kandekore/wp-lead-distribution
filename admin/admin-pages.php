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
