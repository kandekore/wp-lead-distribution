<?php

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
            update_user_meta($user_id, '_user_credits', $credits); // Assuming you store credits in '_user_credits' user meta
        }
    }
});
