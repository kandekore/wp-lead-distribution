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
add_action('edit_user_profile', 'admin_edit_user_postcode_selections');

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
