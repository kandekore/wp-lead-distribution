<?php
// Enqueue necessary scripts and styles for the accordion
function enqueue_custom_admin_scripts($hook) {
    if ('user-edit.php' !== $hook && 'profile.php' !== $hook && 'post.php' !== $hook && 'edit.php' !== $hook) {
        return;
    }

    wp_enqueue_script('jquery-ui-accordion');
    wp_enqueue_style('jquery-ui-style', 'https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css');

    $custom_css = "
        .custom-accordion .ui-accordion-header {
            background-color: #0073aa;
            color: #ffffff;
            font-weight: bold;
            padding: 10px 15px;
            border-top: 1px solid #ffffff;
        }
        .custom-accordion .ui-accordion-header.ui-state-active {
            background-color: #005177;
        }
        .custom-accordion .ui-accordion-header.ui-state-default {
            background-color: #0073aa;
        }
        .custom-accordion .ui-accordion-content {
            background-color: #f1f1f1;
            border: 1px solid #ddd;
            padding: 15px;
            margin-bottom: 10px;
        }";
    wp_add_inline_style('jquery-ui-style', $custom_css);
}
add_action('admin_enqueue_scripts', 'enqueue_custom_admin_scripts');

// Displaying selected postcode areas in order admin
function display_order_postcode_selections_in_admin($order) {
    $selected_postcode_areas = json_decode(get_post_meta($order->get_id(), 'selected_postcode_areas', true), true);
    echo '<div class="admincontainer">';
    echo '<div class="Selected-Postcode-Areas"><h3>' . __('Selected Postcode Areas:') . '</h3><div>';
    if (!empty($selected_postcode_areas)) {
        foreach ($selected_postcode_areas as $region => $codes) {
            echo '<p><strong>' . esc_html($region) . ':</strong> ' . esc_html(implode(', ', $codes)) . '</p>';
        }
    }
    echo '</div></div>';
}
add_action('woocommerce_admin_order_data_after_billing_address', 'display_order_postcode_selections_in_admin', 10, 1);

// Display user's selected postcode areas in profile
function display_user_postcode_selections($user) {
    $selected_postcode_areas = json_decode(get_user_meta($user->ID, 'selected_postcode_areas', true), true);
    echo '<div class="selected-postcodes"><h3>' . __('Selected Postcode Areas') . '</h3><div>';
    if (!empty($selected_postcode_areas)) {
        foreach ($selected_postcode_areas as $region => $codes) {
            echo '<p><strong>' . esc_html($region) . ':</strong> ' . esc_html(implode(', ', $codes)) . '</p>';
        }
    } else {
        echo '<p>' . __('No postcode areas selected.') . '</p>';
    }
    echo '</div></div>';
}
add_action('show_user_profile', 'display_user_postcode_selections', 1);
add_action('edit_user_profile', 'display_user_postcode_selections', 1);

// Admin edit user postcode selections
function admin_edit_user_postcode_selections($user) {
    $all_postcode_areas = json_decode(get_option('custom_postcode_areas'), true);
    $selected_postcode_areas = json_decode(get_user_meta($user->ID, 'selected_postcode_areas', true), true);

    echo '<div class="custom-accordion"><h3>' . __('Edit User Postcode Areas') . '</h3><div>';
    foreach ($all_postcode_areas as $region => $codes) {
        echo '<p><strong>' . esc_html($region) . ':</strong></p>';
        foreach ($codes as $code) {
            $checked = !empty($selected_postcode_areas[$region]) && in_array($code, $selected_postcode_areas[$region]) ? ' checked="checked"' : '';
            echo '<label><input type="checkbox" name="postcode_areas[' . esc_attr($region) . '][]" value="' . esc_attr($code) . '"' . $checked . '> ' . esc_html($code) . '</label><br>';
        }
    }
    echo '</div></div>';
}
add_action('show_user_profile', 'admin_edit_user_postcode_selections', 2);
add_action('edit_user_profile', 'admin_edit_user_postcode_selections', 2);

// Render user credits management section
function render_user_credits_profile_page($user) {
    $current_credits = get_user_meta($user->ID, '_user_credits', true);
    echo '<div class="credit-management"><h3>' . __('User Credits Management') . '</h3><div>';
    echo '<table class="form-table">';
    echo '<tr><th><label for="user_credits">' . __('Credits') . '</label></th><td>';
    echo '<input type="number" id="user_credits" name="user_credits" value="' . esc_attr($current_credits) . '" readonly>';
    echo '<p class="description">' . __('Current credits for the user.') . '</p></td></tr>';
    echo '<tr><th><label for="update_credits">' . __('Update Credits') . '</label></th><td>';
    echo '<button type="button" class="button" onclick="updateCredits(\'+\')">+10 Credits</button>';
    echo '<button type="button" class="button" onclick="updateCredits(\'-\')">-10 Credits</button>';
    echo '<p class="description">' . __('Click the buttons to add or subtract credits.') . '</p></td></tr></table>';
    echo '<script>function updateCredits(action) { var currentCredits = parseInt(document.getElementById(\'user_credits\').value); var updateValue = action === \'+\' ? 10 : -10; var newCredits = currentCredits + updateValue; document.getElementById(\'user_credits\').value = newCredits; }</script>';
    echo '</div></div>';
}
add_action('show_user_profile', 'render_user_credits_profile_page', 0);
add_action('edit_user_profile', 'render_user_credits_profile_page', 0);

// Save admin edited user postcode selections
function save_admin_edited_user_postcode_selections($user_id) {
    if (!current_user_can('edit_user', $user_id)) {
        return false;
    }
    if (isset($_POST['postcode_areas'])) {
        $sanitized_areas = array();
        foreach ($_POST['postcode_areas'] as $region => $codes) {
            $sanitized_areas[$region] = array_map('sanitize_text_field', $codes);
        }
        update_user_meta($user_id, 'selected_postcode_areas', json_encode($sanitized_areas));
    }
}
add_action('personal_options_update', 'save_admin_edited_user_postcode_selections');
add_action('edit_user_profile_update', 'save_admin_edited_user_postcode_selections');

// Initialize the accordion feature
function my_admin_footer_scripts() {
    ?>
    <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('.custom-accordion').accordion({
                collapsible: true,
                heightStyle: 'content'
            });
        });
    </script>
    <?php

    // Check if we're on the user edit page
    global $pagenow;
    if ( ! in_array( $pagenow, [ 'user-edit.php', 'profile.php' ], true ) ) {
        return;
    }

    ?>
    <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Initialize custom accordion sections
            $('.custom-accordion').accordion({
                collapsible: true,
                heightStyle: 'content',
                active: false
            });

            // Target default WordPress sections - Adjust selectors as needed
            var sections = ['Personal Options', 'Name', 'Contact Info', 'About the user', 'Account Management', 'Customer billing address', 'Customer shipping address'];
            sections.forEach(function(section) {
                var header = $('h2:contains("' + section + '")');
                var table = header.next('table');

                // Wrap in div for accordion if not already done
                if (!header.parent().hasClass('wp-default-accordion')) {
                    header.add(table).wrapAll('<div class="wp-default-accordion"></div>');
                }
            });

            // Initialize accordion for the default WordPress sections
            $('.wp-default-accordion').accordion({
                collapsible: true,
                heightStyle: 'content',
                active: false // Start all sections collapsed
            });
        });
    </script>
    <?php
}
add_action('admin_footer', 'my_admin_footer_scripts');
