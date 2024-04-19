<?php
 if ( ! defined( 'ABSPATH' ) ) exit;    

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

    // Immediately exit if the user has more than 5 credits.
    if ($credits > 5) {
        return;
    }

    // Retrieve all subscriptions for the user.
    $subscriptions = wcs_get_users_subscriptions($user_id);
    $eligibleForRenewal = false;

    foreach ($subscriptions as $subscription) {
        // Check if the subscription is active.
        if ($subscription->has_status('active') && !$subscription->is_manual()) {
            $order_info = wc_get_order($subscription->get_parent_id());
            if (!$order_info) {
                continue; // Skip to the next subscription if the order info couldn't be fetched.
            }

            $items = $order_info->get_items();
            foreach ($items as $item) {
                $product_id = $item->get_product_id();
                $renew_on_credit_depletion = get_post_meta($product_id, '_renew_on_credit_depletion', true);
                // Check if the product associated with the subscription is marked for auto-renewal.
                if ($renew_on_credit_depletion === 'yes') {
                    $eligibleForRenewal = true;
                    // Renew the subscription since both conditions are met.
                    $renewal_order = wcs_create_renewal_order($subscription);
                    if ($renewal_order) {
                        $renewal_order->set_payment_method( wc_get_payment_gateway_by_order( $subscription ) );
                        $renewal_order->update_meta_data( '_subscription_renewal_early', $subscription->get_id() );
                        $renewal_order->save();
                
                        // Attempt to collect payment with the subscription's current payment method.
                        WC_Subscriptions_Payment_Gateways::trigger_gateway_renewal_payment_hook( $renewal_order );
                
                        // Now that we've attempted to process the payment, refresh the order.
                        $renewal_order = wc_get_order( $renewal_order->get_id() );
                        
                        $renewal_status=$renewal_order->get_status();

                   
                            if ( $renewal_status == 'failed' ) {
                            
                                //$renewal_order->delete( true );
                            
                                $renewal_order->add_order_note('Payment for the renewal order was unsuccessful with your payment method on file, please try again.');

                            
                            update_user_meta($user_id,'renewal_payment_failed',date("Y-m-d H:i:s"));


                            exit();
                        } else {
                            // Trigger the subscription payment complete hooks and reset suspension counts and user roles.
                            $subscription->payment_complete();
                            
                            delete_user_meta($user_id,'renewal_payment_failed');

                            wcs_update_dates_after_early_renewal( $subscription, $renewal_order );

                            $renewal_order->add_order_note('Your early renewal order was successful.');

                        }
                        break 2; // Exit both the inner and outer loops.
                    }
                }
            }
        }
    }

    if (!$eligibleForRenewal) {
        $renewal_order->add_order_note('No Active Subscriptiopns Found.');
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

    $users = get_users(array(
        'fields'  => 'ids', 
    ));

    if(count($users)>0){
        foreach($users as $user_id){

            $renewal_process=true;
            $renewal_date_exists=get_user_meta($user_id,'renewal_payment_failed',true);
            if(!empty($renewal_date_exists)){
                $date = new DateTime($renewal_date_exists);
                $currentDate = new DateTime();
                $interval = $date->diff($currentDate);
                

                $totalHours = $interval->h + ($interval->days * 24);

                if ($totalHours < 24) { //24 set hours here 
                    $renewal_process=false;
                }
            }

            if($renewal_process==true){
                renew_subscription_for_user_auto($user_id);
            }

        }
    }
}

////anuj

add_filter('woocommerce_payment_complete_order_status', 'custom_order_complete_status', 10, 2);

function custom_order_complete_status($order_status, $order_id) {
    $order = wc_get_order($order_id);
    
    // Check if the order is a "renewal" order
    if (function_exists('wcs_order_contains_renewal') && wcs_order_contains_renewal($order)) {
        // Set subscription renewal orders to "completed"
        return 'completed';
    }
    if (!function_exists('wcs_order_contains_subscription') || !wcs_order_contains_subscription($order)) {
        return 'completed';
    }
    
    // Return the default status for any other cases
    return $order_status;
}
