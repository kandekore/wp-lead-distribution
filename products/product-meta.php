<?php
 if ( ! defined( 'ABSPATH' ) ) exit;    

// Display the credit field
add_action('woocommerce_product_options_general_product_data', function() {
    woocommerce_wp_text_input([
        'id' => '_credits',
        'label' => __('Credits', 'text-domain'),
        'desc_tip' => 'true',
        'description' => __('Number of credits this subscription product represents.', 'text-domain'),
        'type' => 'number',
        'custom_attributes' => array(
            'step' => '1',
            'min' => '1',
        ),
    ]);
});

// Save the credit field
add_action('woocommerce_admin_process_product_object', function($product) {
    $credits = isset($_POST['_credits']) ? intval($_POST['_credits']) : 1;
    $product->update_meta_data('_credits', $credits);
});
add_action('woocommerce_subscription_status_active', function($subscription) {
    $user_id = $subscription->get_user_id();
    $items = $subscription->get_items();
    foreach ($items as $item) {
        $product = $item->get_product();
        if ($product && $product->get_meta('_credits')) {
            $credits = (int) $product->get_meta('_credits');
            update_user_meta($user_id, '_user_credits', $credits); 
        }
    }
});
// Handle credit assignment for subscription activations and renewals.
add_action('woocommerce_order_status_completed', function($order_id) {
    $order = wc_get_order($order_id);
    $user_id = $order->get_user_id();

    // Ensure we have a user ID to work with.
    if (!$user_id) return;

    // Initialize a flag to detect if this order is a subscription renewal.
    $is_renewal = false;

    // Check if this is a subscription renewal.
    if (function_exists('wcs_order_contains_renewal') && wcs_order_contains_renewal($order)) {
        $is_renewal = true;
    }

    foreach ($order->get_items() as $item) {
        $product = $item->get_product();

        if ($product && $product->get_meta('_credits')) {
            $credits = (int) $product->get_meta('_credits');
            $existing_credits = (int) get_user_meta($user_id, '_user_credits', true);

            // For renewals or new orders, add to existing credits.
            if ($is_renewal || !$is_renewal && $existing_credits > 0) {
                $credits += $existing_credits;
            }

            update_user_meta($user_id, '_user_credits', $credits);
        }
    }
}, 10, 1);
add_action('woocommerce_product_options_general_product_data', function() {
    woocommerce_wp_checkbox([
        'id' => '_renew_on_credit_depletion',
        'label' => __('Renew on Credit Depletion', 'text-domain'),
        'desc_tip' => 'true',
        'description' => __('Check this box to renew the subscription when user credits reach 5 or less.', 'text-domain'),
    ]);
});

add_action('woocommerce_admin_process_product_object', function($product) {
    $renew_on_credit_depletion = isset($_POST['_renew_on_credit_depletion']) ? 'yes' : 'no';
    $product->update_meta_data('_renew_on_credit_depletion', $renew_on_credit_depletion);
});
