<?php

function decrement_user_credits($user_id) {
    $credits = (int) get_user_meta($user_id, '_user_credits', true);
    if ($credits > 0) {
        $credits--;
        update_user_meta($user_id, '_user_credits', $credits);
        
        // Check if credits have reached the renewal threshold
        if ($credits <= 5) { // Changed to less than or equal to 5
            renew_subscription_for_user($user_id);
        }
    }
}

function renew_subscription_for_user($user_id) {
    $subscriptions = wcs_get_subscriptions_for_customer($user_id, ['status' => 'active']);
    foreach ($subscriptions as $subscription) {
        $product = $subscription->get_product();
        $renew_on_credit_depletion = $product->get_meta('_renew_on_credit_depletion');
        
        // Check if subscription should renew on credit depletion
        if ($renew_on_credit_depletion === 'yes') {
            // Create a renewal order
            $renewal_order = wcs_create_renewal_order($subscription);

            // Optionally, you might want to immediately process the renewal order
            // This step varies greatly depending on payment methods and workflows
            // For manual payments or development:
            $renewal_order->payment_complete();
            $subscription->update_status('active');

            // Stop after handling the first subscription, remove if renewing all
            break;
        }
    }
}
