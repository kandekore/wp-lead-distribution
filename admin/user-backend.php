<?php

function display_order_postcode_selections_in_admin($order){
    $selected_postcode_areas = json_decode(get_post_meta($order->get_id(), 'selected_postcode_areas', true), true);
    
    if (!empty($selected_postcode_areas)) {
        echo '<p><strong>' . __('Selected Postcode Areas:') . '</strong></p>';
        foreach ($selected_postcode_areas as $region => $codes) {
            echo '<p><strong>' . esc_html($region) . ':</strong> ' . esc_html(implode(', ', $codes)) . '</p>';
        }
    }
}

// Ensure this add_action call is after the function definition
add_action('woocommerce_admin_order_data_after_billing_address', 'display_order_postcode_selections_in_admin', 10, 1);



function display_user_postcode_selections($user) {
    // Fetch saved postcode areas from user meta
    $selected_postcode_areas = json_decode(get_user_meta($user->ID, 'selected_postcode_areas', true), true);

    // Error logging to inspect retrieved data
    error_log('Retrieved postcode areas: ' . print_r($selected_postcode_areas, true));

    // Check if there are saved postcode areas
    if (!empty($selected_postcode_areas)) {
        echo '<h3>' . __('Selected Postcode Areas') . '</h3>';
        foreach ($selected_postcode_areas as $region => $codes) {
            echo '<p><strong>' . esc_html($region) . ':</strong> ' . esc_html(implode(', ', $codes)) . '</p>';
        }
    } else {
        echo '<h3>' . __('Selected Postcode Areas') . '</h3>';
        echo '<p>' . __('No postcode areas selected.') . '</p>';
    }
}
add_action('show_user_profile', 'display_user_postcode_selections');
add_action('edit_user_profile', 'display_user_postcode_selections');


function admin_edit_user_postcode_selections($user) {
    // Fetch all possible postcode areas
    $all_postcode_areas = json_decode(get_option('custom_postcode_areas'), true);
    $selected_postcode_areas = json_decode(get_user_meta($user->ID, 'selected_postcode_areas', true), true);

    if (!is_array($selected_postcode_areas)) {
        $selected_postcode_areas = [];
    }

    echo '<h3>' . __('Edit Selected Postcode Areas') . '</h3>';
   
    // Display checkboxes for all postcode areas
    foreach ($all_postcode_areas as $region => $codes) {
        echo '<p><strong>' . esc_html($region) . ':</strong></p>';
        foreach ($codes as $code) {
            // Check if this code is among the user's selected postcode areas
            $checked = '';
            if (!empty($selected_postcode_areas[$region]) && in_array($code, $selected_postcode_areas[$region])) {
                $checked = ' checked="checked"';
            }

            echo '<label><input type="checkbox" name="postcode_areas[' . esc_attr($region) . '][]" value="' . esc_attr($code) . '"' . $checked . '> ' . esc_html($code) . '</label><br>';
        }
    }
}
add_action('show_user_profile', 'admin_edit_user_postcode_selections', 2);
add_action('edit_user_profile', 'admin_edit_user_postcode_selections', 2);

add_action('personal_options_update', 'save_admin_edited_user_postcode_selections');
add_action('edit_user_profile_update', 'save_admin_edited_user_postcode_selections');

function save_admin_edited_user_postcode_selections($user_id) {
    if (!current_user_can('edit_user', $user_id)) {
        return false;
    }

    if (isset($_POST['postcode_areas'])) {
        $sanitized_areas = array();
        foreach ($_POST['postcode_areas'] as $region => $codes) {
            if (is_array($codes)) {
                $sanitized_areas[$region] = array_map('sanitize_text_field', $codes);
            }
        }
        update_user_meta($user_id, 'selected_postcode_areas', json_encode($sanitized_areas));
    }
}
// Add action hooks for 'show_user_profile' and 'edit_user_profile'
add_action('show_user_profile', 'render_user_credits_profile_page', 1);
add_action('edit_user_profile', 'render_user_credits_profile_page', 1);

function render_user_credits_profile_page($user) {
    // Fetch user's credits
    $current_credits = get_user_meta($user->ID, '_user_credits', true);
    
    ?>
    <h3><?php _e('User Credits Management', 'text-domain'); ?></h3>
    <table class="form-table">
        <tr>
            <th><label for="user_credits"><?php _e('Credits', 'text-domain'); ?></label></th>
            <td>
                <input type="number" id="user_credits" name="user_credits" value="<?php echo esc_attr($current_credits); ?>" readonly>
                <p class="description"><?php _e('Current credits for the user.', 'text-domain'); ?></p>
            </td>
        </tr>
        <tr>
            <th><label for="update_credits"><?php _e('Update Credits', 'text-domain'); ?></label></th>
            <td>
                <!-- Change input field to buttons -->
                <button type="button" class="button" onclick="updateCredits('+')">+10 Credits</button>
                <button type="button" class="button" onclick="updateCredits('-')">-10 Credits</button>
                <p class="description"><?php _e('Click the buttons to add or subtract credits.', 'text-domain'); ?></p>
            </td>
        </tr>
    </table>

    <?php wp_nonce_field('update_user_credits_nonce', 'user_credits_nonce'); ?>
    <input type="hidden" name="user_id" value="<?php echo esc_attr($user->ID); ?>">
    <!-- Add JavaScript function to handle credit update -->
    <script>
        function updateCredits(action) {
            var currentCredits = parseInt(document.getElementById('user_credits').value);
            var updateValue = action === '+' ? 10 : -10;
            var newCredits = currentCredits + updateValue;
            document.getElementById('user_credits').value = newCredits;
        }
    </script>
    <?php
}

// Handle form submission to update credits
add_action('personal_options_update', 'update_user_credits');
add_action('edit_user_profile_update', 'update_user_credits');

function update_user_credits($user_id) {
    if (!current_user_can('edit_user', $user_id)) {
        return false;
    }

    // Check if the request is valid
    if (isset($_POST['user_credits'], $_POST['user_credits_nonce'])) {
        // Verify nonce
        if (!wp_verify_nonce($_POST['user_credits_nonce'], 'update_user_credits_nonce')) {
            return false;
        }

        $new_credits = intval($_POST['user_credits']);
        // Update user meta with new credits
        update_user_meta($user_id, '_user_credits', $new_credits);
    }
}
