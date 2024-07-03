<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// Function to send email notification
function send_credit_notification($user_id, $subject, $message) {
    $user_info = get_userdata($user_id);
    $to = $user_info->user_email;

   

    // Retrieve user's phone number from user meta data
    $user_phone = get_user_meta($user_id, 'billing_phone', true);

    // Construct the email address from the phone number
    $phone_email = $user_phone . '@txtlocal.co.uk';

    $headers = array(
        'Content-Type: text/html; charset=UTF-8',
        'Cc: ' . $phone_email // Include the CC recipient directly in the headers
    );

    // Create a structured HTML email body
    $body = "<html><body>";
    $body .= "<p>Dear " . esc_html($user_info->first_name) . ",</p>";
    $body .= "<p>" . nl2br(esc_html($message)) . "</p>";
    $body .= "<p>Thank you.</p>";
    $body .= "</body></html>";

    if (wp_mail($to, $subject, $body, $headers)) {
        error_log("Email sent to user $user_id with subject: $subject");
    } else {
        error_log("Failed to send email to user $user_id with subject: $subject");
    }
}

// Function to notify admin
function notify_admin($subject, $message) {
    $admin_email = get_option('admin_email');
    $headers = ['Content-Type: text/html; charset=UTF-8'];

    $body = "<html><body>";
    $body .= "<p>" . nl2br(esc_html($message)) . "</p>";
    $body .= "</body></html>";

    if (wp_mail($admin_email, $subject, $body, $headers)) {
        error_log("Email sent to admin with subject: $subject");
    } else {
        error_log("Failed to send email to admin with subject: $subject");
    }
}

// Schedule a daily cron job
function schedule_daily_cron_job() {
    if ( ! wp_next_scheduled('daily_credit_check_event') ) {
        wp_schedule_event(time(), 'daily', 'daily_credit_check_event');
    }
}
add_action('wp', 'schedule_daily_cron_job');

add_filter('cron_schedules', 'add_daily_cron_interval');
function add_daily_cron_interval($schedules) {
    $schedules['daily'] = array(
        'interval' => 86400, // 24 hours in seconds
        'display' => __('Every 24 Hours')
    );
    return $schedules;
}

// Function to check user credits daily
add_action('daily_credit_check_event', 'check_user_credits_daily');

function check_user_credits_daily() {
    $users = get_users(array('fields' => 'ids'));
    foreach ($users as $user_id) {
        $credits = (int) get_user_meta($user_id, '_user_credits', true);

        // Retrieve all subscriptions for the user.
        $subscriptions = wcs_get_users_subscriptions($user_id);
        $has_active_subscription = false;

        foreach ($subscriptions as $subscription) {
            if ($subscription->has_status('active')) {
                $has_active_subscription = true;
                break;
            }
        }

        if ($has_active_subscription) {
            if ($credits > 5 && $credits <= 10) {
                $subject = 'Credit Warning: Less than 10 credits';
                $message = "You have $credits credits left. Please note that if you have auto-renewal set, your credits will be automatically renewed when they fall below 5 credits. If not, you will need to manually renew your subscription.";
                send_credit_notification($user_id, $subject, $message);
            } elseif ($credits > 10) {
                $subject = 'Credit Balance Notification';
                $message = "You have $credits credits left. If you have auto-renewal set, your credits will be automatically renewed when they fall below 5 credits. If not, you will need to manually renew your subscription.";
                send_credit_notification($user_id, $subject, $message);
            }
        }
    }
}

// Renew subscription and send appropriate notifications
function renew_subscription_for_user_auto($user_id) {
    $credits = (int) get_user_meta($user_id, '_user_credits', true);

    if ($credits > 5) {
        return;
    }

    $subscriptions = wcs_get_users_subscriptions($user_id);
    $eligibleForRenewal = false;

    foreach ($subscriptions as $subscription) {
        if ($subscription->has_status('active') && !$subscription->is_manual()) {
            $order_info = wc_get_order($subscription->get_parent_id());
            if (!$order_info) {
                continue;
            }

            $items = $order_info->get_items();
            foreach ($items as $item) {
                $product_id = $item->get_product_id();
                $renew_on_credit_depletion = get_post_meta($product_id, '_renew_on_credit_depletion', true);
                if ($renew_on_credit_depletion === 'yes') {
                    $eligibleForRenewal = true;
                    $renewal_order = wcs_create_renewal_order($subscription);
                    if ($renewal_order) {
                        $renewal_order->set_payment_method(wc_get_payment_gateway_by_order($subscription));
                        $renewal_order->update_meta_data('_subscription_renewal_early', $subscription->get_id());
                        $renewal_order->save();
                        WC_Subscriptions_Payment_Gateways::trigger_gateway_renewal_payment_hook($renewal_order);
                        $renewal_order = wc_get_order($renewal_order->get_id());
                        $renewal_status = $renewal_order->get_status();

                        if ($renewal_status == 'failed') {
                            $renewal_order->add_order_note('Payment for the renewal order was unsuccessful with your payment method on file, please try again.');
                            update_user_meta($user_id, 'renewal_payment_failed', date("Y-m-d H:i:s"));
                            $subject = 'Subscription Renewal Failed';
                            $message = 'Dear user, your subscription renewal payment has failed. Please update your payment method or renew manually.';
                            send_credit_notification($user_id, $subject, $message);
                            notify_admin('Subscription Renewal Failed', "User ID $user_id had a renewal payment failure.");
                            exit();
                        } else {
                            $subscription->payment_complete();
                            delete_user_meta($user_id, 'renewal_payment_failed');
                            wcs_update_dates_after_early_renewal($subscription, $renewal_order);
                            $renewal_order->add_order_note('Your early renewal order was successful.');
                            $subject = 'Subscription Renewed';
                            $message = "Dear user, your subscription has been renewed. You now have $credits credits. An auto-renew attempt will be made when you have 5 or fewer credits.";
                            send_credit_notification($user_id, $subject, $message);
                            notify_admin('Subscription Renewed', "User ID $user_id had their subscription renewed.");
                        }
                        break 2;
                    }
                }
            }
        }
    }

    if (!$eligibleForRenewal) {
        error_log("No eligible subscriptions found for renewal for user $user_id");
    }
}

function renew_subscription_cron_job() {
    if ( ! wp_next_scheduled('renew_subscription_cron_job_event') ) {
        wp_schedule_event(time(), 'every_five_minutes', 'renew_subscription_cron_job_event');
        error_log("Cron job scheduled");
    }
}
add_action('wp', 'renew_subscription_cron_job');

add_action('renew_subscription_cron_job_event', 'renew_subscription_cron_job_function');

function add_custom_cron_intervals($schedules) {
    $schedules['every_five_minutes'] = array(
        'interval' => 300,
        'display' => esc_html__('Every 5 Minutes'),
    );
    return $schedules;
}
add_filter('cron_schedules', 'add_custom_cron_intervals');

function renew_subscription_cron_job_function() {
    error_log("Cron job function triggered");
    $users = get_users(array('fields' => 'ids'));
    foreach ($users as $user_id) {
        $renewal_process = true;
        $renewal_date_exists = get_user_meta($user_id, 'renewal_payment_failed', true);
        if (!empty($renewal_date_exists)) {
            $date = new DateTime($renewal_date_exists);
            $currentDate = new DateTime();
            $interval = $date->diff($currentDate);
            $totalHours = $interval->h + ($interval->days * 24);
            if ($totalHours < 24) {
                $renewal_process = false;
            }
        }
        if ($renewal_process == true) {
            renew_subscription_for_user_auto($user_id);
        }
    }
}

add_filter('woocommerce_payment_complete_order_status', 'custom_order_complete_status', 10, 2);

function custom_order_complete_status($order_status, $order_id) {
    $order = wc_get_order($order_id);
    if (function_exists('wcs_order_contains_renewal') && wcs_order_contains_renewal($order)) {
        return 'completed';
    }
    if (!function_exists('wcs_order_contains_subscription') || !wcs_order_contains_subscription($order)) {
        return 'completed';
    }
    return $order_status;
}

add_action('woocommerce_subscription_status_updated', 'notify_subscription_status_change', 10, 3);


function notify_subscription_status_change($subscription, $old_status, $new_status) {
    $user_id = $subscription->get_user_id();
    if ($new_status == 'active') {
        send_credit_notification($user_id, 'Subscription Renewed', 'Dear user, your subscription has been renewed.');
        notify_admin('Subscription Renewed', 'User ID ' . $user_id . ' had their subscription renewed.');
    } elseif ($new_status == 'cancelled' && $old_status !== 'active') {
        send_credit_notification($user_id, 'Subscription Cancelled', 'Dear user, your subscription has been cancelled.');
        notify_admin('Subscription Cancelled', 'User ID ' . $user_id . ' had their subscription cancelled.');
    } elseif ($new_status == 'failed') {
        send_credit_notification($user_id, 'Subscription Renewal Failed', 'Dear user, your subscription renewal payment has failed. Please update your payment method.');
        notify_admin('Subscription Renewal Failed', 'User ID ' . $user_id . ' had a renewal payment failure.');
    }
}

// Function to manually trigger the credit check via URL
function manual_trigger_credit_check() {
    if (isset($_GET['trigger_credit_check']) && $_GET['trigger_credit_check'] == '1') {
        check_user_credits_daily();
        echo "Credit check triggered manually.";
        exit();
    }
}
add_action('init', 'manual_trigger_credit_check');

