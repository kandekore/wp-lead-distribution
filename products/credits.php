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

// Hook this function to an action that fires after credits are decremented.
add_action('your_custom_decrement_credits_hook', 'check_credits_and_renew_subscription', 10, 1);

function check_credits_and_renew_subscription($user_id) {
    $subscriptions = wcs_get_users_subscriptions($user_id);

    foreach ($subscriptions as $subscription) {
        if ($subscription->has_status('active') && $subscription->get_meta('_renew_on_credit_depletion') === 'yes') {
            // Check if the early renewal can be initiated
            if ($subscription->can_be_renewed_early()) {
                // Get the renewal order
                $renewal_order = wcs_create_renewal_order($subscription);

                // Redirect to the payment process for the renewal order, or handle payment programmatically
                // This step depends on your specific requirements and setup

                // Log or notify as needed
                error_log("Early renewal initiated for subscription {$subscription->get_id()} for user {$user_id}.");

                // Break if only renewing one subscription
                break;
            }
        }
    }
}

//anuj
function renew_subscription_for_user_auto($user_id) {

    $credits = (int) get_user_meta($user_id, '_user_credits', true);

    if ($credits <= 5) {

        $subscriptions = wcs_get_users_subscriptions($user_id, ['status' => 'active']);
        foreach ($subscriptions as $subscription) {
            $orderinfo=wc_get_order($subscription->get_parent_id());
            $items = $orderinfo->get_items();
            $product_id=0;
            if ( ! empty( $items ) ) {
                foreach ( $items as $item ) {
                    $product_id = $item->get_product_id();
                }
            }
            if($product_id>0){
                $renew_on_credit_depletion = get_post_meta($product_id,'_renew_on_credit_depletion',true);
                if ($renew_on_credit_depletion === 'yes') {
                    $renewal_order = wcs_create_renewal_order($subscription);
                    if ($renewal_order) {
                        $renewal_order->payment_complete();
                        $subscription->update_status('active');
                        // Log or notify about the renewal if needed.
                        break; // Remove or adjust based on whether you want to renew multiple subscriptions.
                    }

                }
                
            }

        }
    }

}

function renew_subscription_cron_job() {
    if ( ! wp_next_scheduled( 'renew_subscription_cron_job_event' ) ) {
        wp_schedule_event( time(), 'every_five_minutes', 'renew_subscription_cron_job_event' );
    }
}
add_action( 'wp', 'renew_subscription_cron_job' );

add_action( 'renew_subscription_cron_job_event', 'renew_subscription_cron_job_function' );

function add_custom_cron_intervals( $schedules ) {
    $schedules['every_five_minutes'] = array(
        'interval' => 300, 
        'display'  => esc_html__( 'Every 5 Minutes' ),
    );
    return $schedules;
}
add_filter( 'cron_schedules', 'add_custom_cron_intervals' );


function renew_subscription_cron_job_function(){

    //$users[] =  get_current_user_id();
    $users = get_users(array(
        'fields'  => 'ids', 
    ));

    if(count($users)>0){
        foreach($users as $user_id){
            renew_subscription_for_user_auto($user_id);
        }
    }
}

////anuj


// //no need this one use auto one
// function renew_subscription_for_user($user_id) {
//     $subscriptions = wcs_get_subscriptions_for_customer($user_id, ['status' => 'active']);

//     foreach ($subscriptions as $subscription) {
//         // Assuming the renewal setting is on the parent product
//         $product_id = $subscription->get_parent_id();
//         $product = wc_get_product($product_id);
//         $renew_on_credit_depletion = $product ? $product->get_meta('_renew_on_credit_depletion') : '';

//         if ($renew_on_credit_depletion === 'yes') {
//             $renewal_order = wcs_create_renewal_order($subscription);
//             if ($renewal_order) {
//                 $renewal_order->payment_complete();
//                 $subscription->update_status('active');
//                 // Log or notify about the renewal if needed.
//                 break; // Remove or adjust based on whether you want to renew multiple subscriptions.
//             }
//         }
//     }
// }
// //no need this one use auto one

// add_filter('woocommerce_payment_complete_order_status', 'custom_order_complete_status', 10, 2);

function custom_order_complete_status($order_status, $order_id) {
    $order = wc_get_order($order_id);
    
    // Check if the order is a "renewal" order
    if (function_exists('wcs_order_contains_renewal') && wcs_order_contains_renewal($order)) {
        // Set subscription renewal orders to "completed"
        return 'completed';
    }

    // For regular orders, check if you want to auto-complete them
    // This example checks if it's not a subscription order (to avoid duplicating logic)
    // You might want to include additional logic here depending on your requirements
    if (!function_exists('wcs_order_contains_subscription') || !wcs_order_contains_subscription($order)) {
        return 'completed';
    }
    
    // Return the default status for any other cases
    return $order_status;
}
