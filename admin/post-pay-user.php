<?php

add_action('init', function() {
    // Check if post_pay role already exists
    if (!get_role('post_pay')) {
        // Add post pay role with WooCommerce customer capabilities
        add_role('post_pay', 'Post Pay', [
            'read'         => true,  // Required for WooCommerce to function properly
            'edit_posts'   => false, // Disallow editing posts
            'manage_woocommerce' => true, // Allow WooCommerce capabilities
            'view_woocommerce_reports' => true,
            'edit_shop_orders' => true,
            'edit_products' => true,
            'list_users' => true,
            // Add any additional capabilities required
        ]);
    }
});

add_action('woocommerce_account_menu_items', function($items) {
    $user = wp_get_current_user();
    if (in_array('post_pay', $user->roles)) {
        // Ensure Post Pay users can access the account page
        return $items;
    }
    return $items;
});
// Add a lead reception toggle option in the user profile page
add_action('show_user_profile', 'add_lead_reception_option');
add_action('edit_user_profile', 'add_lead_reception_option');

function add_lead_reception_option($user) {
    // Only show this option for post-pay users
    if (in_array('post_pay', $user->roles)) {
        ?>
        <h3><?php _e("Post-Pay Lead Reception", "text-domain"); ?></h3>
        <table class="form-table">
            <tr>
                <th><label for="enable_lead_reception"><?php _e("Enable Lead Reception", "text-domain"); ?></label></th>
                <td>
                    <input type="checkbox" name="enable_lead_reception" id="enable_lead_reception" value="1" <?php checked(get_user_meta($user->ID, 'enable_lead_reception', true), '1'); ?> />
                    <label for="enable_lead_reception"><?php _e("Enable this user to receive leads", "text-domain"); ?></label>
                </td>
            </tr>
        </table>
        <?php
    }
}

add_action('personal_options_update', 'save_lead_reception_option');
add_action('edit_user_profile_update', 'save_lead_reception_option');

function save_lead_reception_option($user_id) {
    // Only save for post-pay users
    $user = get_userdata($user_id);
    if (in_array('post_pay', $user->roles)) {
        update_user_meta($user_id, 'enable_lead_reception', isset($_POST['enable_lead_reception']) ? '1' : '0');
    }
}
