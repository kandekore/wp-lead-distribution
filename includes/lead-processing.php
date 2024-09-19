<?php 

if ( ! defined( 'ABSPATH' ) ) exit;    

function process_lead_submission(WP_REST_Request $request) {
    // Extract relevant data from the request
    $lead_data = [
        'postcode' => strtoupper(sanitize_text_field($request->get_param('postcode'))),
        'registration' => strtoupper(sanitize_text_field($request->get_param('reg'))),
        'model' => sanitize_text_field($request->get_param('model')),
        'date' => sanitize_text_field($request->get_param('date')),
        'cylinder' => sanitize_text_field($request->get_param('cylinder')),
        'colour' => sanitize_text_field($request->get_param('colour')),
        'keepers' => sanitize_text_field($request->get_param('keepers')),
        'contact' => sanitize_text_field($request->get_param('contact')),
        'email' => sanitize_email($request->get_param('email')),
        'info' => sanitize_textarea_field($request->get_param('info')),
        'fuel' => sanitize_text_field($request->get_param('fuel')),
        'mot' => sanitize_text_field($request->get_param('mot')),
        'trans' => sanitize_text_field($request->get_param('trans')),
        'doors' => intval($request->get_param('doors')),
        'mot_due' => sanitize_text_field($request->get_param('mot_due')),
        'leadid' => sanitize_text_field($request->get_param('leadid')),
        'resend' => sanitize_text_field($request->get_param('resend')),
        'vin' => sanitize_text_field($request->get_param('vin')),
    ];

    $postcode_prefix = substr($lead_data['postcode'], 0, 2);
    $eligible_recipients = get_eligible_recipients_for_lead($postcode_prefix);

    // Deserialize your settings array
    $settings = get_option('master_admin_settings');
    if ($settings) {
        $settings_array = maybe_unserialize($settings);

        $master_admin_function_enabled = $settings_array["master_admin_function_enabled"];
        $minimum_year = $settings_array["minimum_year"];
        $master_admin_email = $settings_array["master_admin_email"];
        $master_admin_user_id = $settings_array["master_admin_user_id"];

        // Assuming $lead_data contains all the necessary fields
        $rootURL = get_site_url();
        $apiEndpoint = "/wp-json/lead-management/v1/submit-lead?";
        $resendParam = "resend=true";

        // Construct the query parameters from $lead_data, excluding 'resend' and adding it at the end
        $queryParams = [];
        foreach ($lead_data as $key => $value) {
            if ($key != 'resend') { // Exclude the resend parameter
                $queryParams[] = $key . "=" . urlencode($value);
            }
        }
        $queryString = implode("&", $queryParams) . "&" . $resendParam;

        // Complete API URL
        $apiURL = $rootURL . $apiEndpoint . $queryString;

        if ($master_admin_function_enabled == "1" && !empty($minimum_year) && intval($lead_data['date']) > intval($minimum_year) &&
            $lead_data['resend'] == "false") {
            // Prepare and send email to Master Admin
            $subject = "New Lead: " . $lead_data['leadid'];

            // Start of the HTML email body
            $body = "<html><body>";
            $body .= "<h3>New Lead Details for Master Admin</h3>";

            // Assuming 'registration' and 'model' are important and should be highlighted
            if (isset($lead_data['registration']) && isset($lead_data['model'])) {
                $body .= "<h4>" . esc_html($lead_data['leadid']) . " - " . esc_html($lead_data['registration']) . " - " . esc_html($lead_data['model']) . "</h4>";
            }

            // Manually display selected meta data
            $meta_keys = [
                'keepers', 'contact', 'email', 'postcode', 'registration', 'model', 'date',
                'cylinder', 'colour', 'doors', 'fuel', 'mot', 'transmission', 'mot_due',
                'vin'
            ];

            $body .= "<ul style='list-style-type:none;'>";
            foreach ($meta_keys as $key) {
                if (!empty($lead_data[$key])) { // Only display if value is not empty
                    $body .= "<li>" . ucfirst($key) . ": " . esc_html($lead_data[$key]) . "</li>";
                }
            }
            $body .= "</ul>";
            $body .= "<p>To resend the lead, click <a href='" . esc_url($apiURL) . "'>here</a>.</p>";
            // End of the HTML email body
            $body .= "</body></html>";

            $headers = ['Content-Type: text/html; charset=UTF-8'];

            wp_mail($master_admin_email, $subject, $body, $headers);

            // Assign lead to Master Admin user ID and store the lead
            $lead_id = store_lead($lead_data, $master_admin_user_id);
            assign_lead_to_user($master_admin_user_id, $lead_data, $lead_id);
            if (!is_wp_error($lead_id)) {
                return new WP_REST_Response(['message' => 'Lead sent successfully to Master Admin'], 200);
            } else {
                return new WP_REST_Response(['message' => 'Failed to store lead for Master Admin'], 500);
            }
        }
    } else {
        error_log('Master Admin settings not found or are incorrect.');
    }

    // Check if there are eligible recipients
    if (empty($eligible_recipients)) {
        $settings = get_option('fallback_settings');
        if ($settings) {
            $settings_array = maybe_unserialize($settings);
            $fallback_user_enabled = !empty($settings_array['fallback_user_enabled']) && $settings_array['fallback_user_enabled'] == "1";
            $fallback_user_email = $settings_array['fallback_user_email'];
            $fallback_user_id = $settings_array['fallback_user_id'];
            $fallback_api_endpoint = $settings_array['fallback_user_api_endpoint'];

            if ($fallback_user_enabled) {
                if ($fallback_api_endpoint) {
                    $api_lead_data = [
                        'postcode' => $lead_data['postcode'],
                        'reg' => $lead_data['registration'],
                        'model' => $lead_data['model'],
                        'date' => $lead_data['date'],
                        'cylinder' => $lead_data['cylinder'],
                        'colour' => $lead_data['colour'],
                        'keepers' => $lead_data['keepers'],
                        'contact' => $lead_data['contact'],
                        'email' => $lead_data['email'],
                        'info' => $lead_data['info'],
                        'fuel' => $lead_data['fuel'],
                        'mot' => $lead_data['mot'],
                        'trans' => $lead_data['trans'],
                        'doors' => $lead_data['doors'],
                        'mot_due' => $lead_data['mot_due'],
                        'leadid' => $lead_data['leadid'],
                        'resend' => $lead_data['resend'],
                        'vin' => $lead_data['vin'],
                    ];

                    // Construct the GET URL with query parameters
                    $fallback_api_url = add_query_arg($api_lead_data, $fallback_api_endpoint);

                    // Send the lead to the fallback user API endpoint using GET
                    $response = wp_remote_get($fallback_api_url);

                    if (is_wp_error($response)) {
                        return new WP_REST_Response(['message' => 'Failed to send lead to Fallback User API'], 500);
                    }

                    $api_response_body = wp_remote_retrieve_body($response);
                    $subject = "API Response for Lead ID: " . $lead_data['leadid'];
                    $body = "API Response: " . $api_response_body;

                    // Send the email to leads@scrapuk.co.uk
                    wp_mail('leads@scrapuk.co.uk', $subject, $body, ['Content-Type: text/plain; charset=UTF-8']);

                    $lead_id = store_lead($lead_data, $fallback_user_id);
                    $result = assign_lead_to_user($fallback_user_id, $lead_data, $lead_id);
                    if (!is_wp_error($lead_id) && $result) {
                        return new WP_REST_Response(['message' => 'Lead sent successfully to Fallback User API and stored'], 200);
                    } else {
                        return new WP_REST_Response(['message' => 'Failed to store lead for Fallback User'], 500);
                    }
                } else {
                    $lead_id = store_lead($lead_data, $fallback_user_id);
                    $result = assign_lead_to_user($fallback_user_id, $lead_data, $lead_id);

                    if (!is_wp_error($lead_id) && $result) {
                        $subject = "New Lead Assignment: " . $lead_data['leadid'];
                        $body = "<html><body><h3>You've received a new lead as a fallback recipient.</h3>";
                        $body .= "<p>Lead ID: " . esc_html($lead_data['leadid']) . "</p>";
                        $body .= "<p>Please log in to view the details.</p></body></html>";

                        $headers = ['Content-Type: text/html; charset=UTF-8'];

                        if (wp_mail($fallback_user_email, $subject, $body, $headers)) {
                            return new WP_REST_Response(['message' => 'Lead sent successfully to Fallback User and email notification sent.'], 200);
                        } else {
                            return new WP_REST_Response(['message' => 'Lead sent to Fallback User but failed to send email notification.'], 500);
                        }
                    } else {
                        return new WP_REST_Response(['message' => 'Failed to store lead for Fallback User'], 500);
                    }
                }
            } else {
                return new WP_REST_Response(['message' => 'No eligible recipients for this postcode and Fallback User is disabled'], 404);
            }
        }
    }

    // Randomly pick an eligible recipient from the array
    $random_key = array_rand($eligible_recipients);
    $recipient_id = $eligible_recipients[$random_key];
    $lead_id = store_lead($lead_data, $recipient_id);

    // Ensure lead was successfully stored
    if (is_wp_error($lead_id)) {
        return new WP_REST_Response(['message' => 'Failed to store lead'], 500);
    }

    // Deduct a credit from the chosen recipient and send the lead
    if (deduct_credit_from_user($recipient_id)) {
        assign_lead_to_user($recipient_id, $lead_data, $lead_id);
        send_lead_email_to_user($recipient_id, $lead_data);
        return new WP_REST_Response(['message' => 'Lead sent successfully to ' . $recipient_id], 200);
    } else {
        return new WP_REST_Response(['message' => 'Failed to send lead, user out of credits'], 500);
    }
}

function get_eligible_recipients_for_lead($postcode_prefix) {
    $eligible_recipients = [];
    $users = get_users(); // Consider refining this query based on your needs

    foreach ($users as $user) {
        $user_credits = (int)get_user_meta($user->ID, '_user_credits', true);
        $selected_postcode_areas = json_decode(get_user_meta($user->ID, 'selected_postcode_areas', true), true);

        if ($user_credits > 0 && !empty($selected_postcode_areas)) {
            foreach ($selected_postcode_areas as $region => $codes) {
                foreach ($codes as $code) {
                    // Replace "#" with regex pattern to match any single digit
                    $codePattern = str_replace("#", "[0-9]", $code);
                    // Check if the lead's postcode prefix matches the customer's postcode pattern
                    if (preg_match("/^$codePattern/", $postcode_prefix)) {
                        $eligible_recipients[] = $user->ID;
                        break 2; // Match found, no need to continue checking
                    }
                }
            }
        }
    }

    return $eligible_recipients;
}
function deduct_credit_from_user($user_id) {
    $credits = get_user_meta($user_id, '_user_credits', true);
    $credits = intval($credits);

    if ($credits > 0) {
        $credits--; // Deduct one credit
        update_user_meta($user_id, '_user_credits', $credits);

        // Check if the credits are now zero or less and handle subscription renewal or cancellation if necessary
        if ($credits <= 0) {
            $renewal_attempted = check_credits_and_renew_subscription($user_id);
            
            
            if (!$renewal_attempted) {
                cancel_user_subscription($user_id);
                send_credit_depletion_email($user_id);
            }
        } elseif ($credits <= 5) {
            check_credits_and_renew_subscription($user_id); 
        }

        return true; // Successfully deducted credit
    }

    return false; // User had no credits to deduct
}



function cancel_user_subscription($user_id) {
    $subscriptions = wcs_get_users_subscriptions($user_id);

    foreach ($subscriptions as $subscription) {
        if ($subscription->has_status('active')) {
            $subscription->update_status('cancelled');
            error_log("Subscription {$subscription->get_id()} for user {$user_id} has been cancelled due to zero credits.");
            break;
        }
    }
}


function send_credit_depletion_email($user_id) {
    $user_info = get_userdata($user_id);
    $to = $user_info->user_email;

    $subject = "Your Credits Have Been Depleted";
    $body = "<html><body>";
    $body .= "<h3>Your Credits Have Been Depleted</h3>";
    $body .= "<p>Dear " . esc_html($user_info->display_name) . ",</p>";
    $body .= "<p>Your account has run out of credits, and your subscription has been cancelled. Please renew your subscription to continue receiving leads.</p>";
    $body .= "<p>Thank you for using our service.</p>";
    $body .= "</body></html>";

    $headers = ['Content-Type: text/html; charset=UTF-8'];

    wp_mail($to, $subject, $body, $headers);
}
add_filter('woocommerce_email_enabled_customer_completed_renewal_order', 'disable_renewal_email_for_automatic_renewals', 10, 2);
function disable_renewal_email_for_automatic_renewals($enabled, $order) {
    if ($order->get_meta('_triggered_by_credits_renewal')) {
        return false;
    }
    return $enabled;
}
function check_credits_and_renew_subscription($user_id) {
    $subscriptions = wcs_get_users_subscriptions($user_id);
    $renewal_attempted = false;

    foreach ($subscriptions as $subscription) {
        if ($subscription->has_status('active') && $subscription->get_meta('_renew_on_credit_depletion') === 'yes') {
            // Check if the early renewal can be initiated
            if ($subscription->can_be_renewed_early()) {
                // Get the renewal order
                $renewal_order = wcs_create_renewal_order($subscription);

                // Set the custom meta to flag it was triggered by credits
                $renewal_order->update_meta_data('_triggered_by_credits_renewal', true);

                // Process the renewal order payment
                $result = WC_Subscriptions_Payment_Gateways::trigger_gateway_renewal_payment_hook($renewal_order);

                if ($result && $renewal_order->get_status() === 'completed') {
                    // Log or notify as needed
                    error_log("Early renewal successful for subscription {$subscription->get_id()} for user {$user_id}.");

                    // Update the subscription dates
                    wcs_update_dates_after_early_renewal($subscription, $renewal_order);

                    $renewal_attempted = true; // Renewal was successful
                    break; // Stop after successfully renewing one subscription
                } else {
                    // Handle failed renewal attempt
                    $renewal_order->update_status('failed');
                    $renewal_order->add_order_note('Early renewal payment failed.');
                    error_log("Early renewal failed for subscription {$subscription->get_id()} for user {$user_id}.");
                }
            }
        }
    }

    return $renewal_attempted; // Return whether a renewal was attempted successfully or not
}
function assign_lead_to_user($user_id, $lead_data, $lead_id) {
    // Example of associating a lead post with a user. Adjust according to your storage method.
    update_post_meta($lead_id, 'assigned_user', $user_id);
    return true;
}

function send_lead_email_to_user($user_id, $lead_data) {
    // Retrieve user's email address
    $user_info = get_userdata($user_id);
    $to = $user_info->user_email;

    // Retrieve user's phone number from user meta data
    $user_phone = get_user_meta($user_id, 'billing_phone', true);

    // Construct the email address from the phone number
    $phone_email = $user_phone . '@txtlocal.co.uk';

    // Set the subject of the email
    $subject = "New Lead: " . $lead_data['leadid'];

    // Start of the HTML email body
    $body = "<html><body>";
    $body .= "<h3>New Lead Details</h3>";

    // Assuming 'registration' and 'model' are important and should be highlighted
    if (isset($lead_data['registration']) && isset($lead_data['model'])) {
        $body .= "<h4>" . esc_html($lead_data['leadid']) . " - " . esc_html($lead_data['registration']) . " - " . esc_html($lead_data['model']) . "</h4>";
    }

    // Manually display selected meta data
    $meta_keys = [
        'keepers', 'contact', 'email', 'postcode', 'registration', 'model', 'date',
        'cylinder', 'colour', 'doors', 'fuel', 'mot', 'transmission', 'mot_due',
        'vin'
    ];

    $body .= "<ul style='list-style-type:none;'>";
    foreach ($meta_keys as $key) {
        if (!empty($lead_data[$key])) { // Only display if value is not empty
            $body .= "<li>" . ucfirst($key) . ": " . esc_html($lead_data[$key]) . "</li>";
        }
    }
    $body .= "</ul>";

    // End of the HTML email body
    $body .= "</body></html>";

    // Set headers for CC recipient
    $headers = array(
        'Content-Type: text/html; charset=UTF-8',
        'Cc: ' . $phone_email // Include the CC recipient directly in the headers
    );

    // Send email using wp_mail(), specifying CC recipient in headers
    return wp_mail($to, $subject, $body, $headers);
}

add_action('profile_update', 'update_user_postcode_queues', 10, 2);
function update_user_postcode_queues($user_id, $old_user_data) {
    $selected_postcode_areas = json_decode(get_user_meta($user_id, 'selected_postcode_areas', true), true);
    if (empty($selected_postcode_areas)) return;

    foreach ($selected_postcode_areas as $region => $codes) {
        foreach ($codes as $code) {
            $postcode_prefix = substr($code, 0, 2);
            $queue_key = "recipients_queue_{$postcode_prefix}";
            $queue = get_option($queue_key, []);

            // If not already in queue, add user ID
            if (!in_array($user_id, $queue)) {
                $queue[] = $user_id;
                update_option($queue_key, $queue);
            }
        }
    }
}

function process_lead_submission_with_lock(WP_REST_Request $request) {
    $lock_key = 'process_lead_lock';
    $lock_timeout = 10; // Lock timeout in seconds

    // Attempt to acquire lock
    if (get_transient($lock_key)) {
        return new WP_REST_Response(['message' => 'System is busy, please try again'], 429);
    }

    set_transient($lock_key, true, $lock_timeout);

    // [Process lead submission logic goes here]

    // Release lock
    delete_transient($lock_key);

    return new WP_REST_Response(['message' => 'Lead processed successfully'], 200);
}