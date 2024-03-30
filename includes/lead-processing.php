<?php

if ( ! defined( 'ABSPATH' ) ) exit;    
function process_lead_submission(WP_REST_Request $request) {
    // Extract relevant data from the request
    $lead_data = [
        'postcode' => sanitize_text_field($request->get_param('postcode')),
        // Other lead details as needed
    ];
    $postcode_prefix = substr($lead_data['postcode'], 0, 2);
    $eligible_recipients = get_eligible_recipients_for_lead($postcode_prefix);

    // Output for testing: List of eligible recipient IDs
    echo "Eligible Recipients for Postcode Prefix {$postcode_prefix}: " . implode(', ', $eligible_recipients) . "<br>";

    // Process each eligible recipient
    foreach ($eligible_recipients as $recipient_id) {
        // Placeholder: Logic to process the recipient, such as deducting credits, assigning the lead, and sending emails
        echo "Processing recipient ID: {$recipient_id}<br>";
        // Deduct a credit, assign the lead, and send the email
        // For testing, these actions are simply echoed
    }

    // For testing, simply return a success message without actual WP_REST_Response
    return "Lead processed successfully.";
}

function get_eligible_recipients_for_lead($postcode_prefix) {
    $eligible_recipients = [];
    $users = get_users(); // Simplified: Consider adding criteria to filter users if your database is large

    foreach ($users as $user) {
        $user_credits = (int)get_user_meta($user->ID, '_user_credits', true);
        $selected_postcode_areas = json_decode(get_user_meta($user->ID, 'selected_postcode_areas', true), true);

        // Check if user has credits and selected postcode areas
        if ($user_credits > 0 && !empty($selected_postcode_areas)) {
            foreach ($selected_postcode_areas as $region => $codes) {
                foreach ($codes as $code) {
                    if ($code === "*" || strpos($postcode_prefix, $code) === 0) {
                        // User is eligible either by wildcard (*) or matching prefix
                        $eligible_recipients[] = $user->ID;
                        break 2; // Found a match, no need to check further codes or regions for this user
                    }
                }
            }
        }
    }

    return $eligible_recipients;
}

// function process_lead_submission(WP_REST_Request $request) {
//     // Assume validation of required parameters has already happened
    
//     // Extract lead data
//     $lead_data = [
//         'postcode' => sanitize_text_field($request->get_param('postcode')),
//         // Additional lead details...
//     ];
    
//     // Extract the first two characters of the lead's postcode
//     $postcode_prefix = substr($lead_data['postcode'], 0, 2);
    
//     // Get eligible recipients based on the first two characters and their selected postcodes
//     $eligible_recipients = get_eligible_recipients_for_lead($postcode_prefix);
    
//     // No eligible recipients found
//     if (empty($eligible_recipients)) {
//         return new WP_REST_Response(['message' => 'No eligible recipients for this postcode'], 404);
//     }
    
//     // For demonstration, choosing the first eligible recipient
//     // In production, you would use a more complex logic like round-robin
//     $recipient_id = $eligible_recipients[0];
    
//     // Deduct a credit from the chosen recipient and send the lead
//     if (deduct_credit_from_user($recipient_id)) {
//         // assign_lead_to_user($recipient_id, $lead_data);
//         // send_lead_email_to_user($recipient_id, $lead_data);
//         return new WP_REST_Response(['message' => 'Lead sent successfully to ' . $recipient_id], 200);
//     } else {
//         return new WP_REST_Response(['message' => 'Failed to send lead, user out of credits'], 500);
//     }
// }

// function get_eligible_recipients_for_lead($postcode_prefix) {
//     $eligible_recipients = [];
//     // Retrieve users who selected postcodes that start with the same two characters
//     $users = get_users();
//     foreach ($users as $user) {
//         $user_credits = get_user_meta($user->ID, '_user_credits', true);
//         $selected_postcode_areas = json_decode(get_user_meta($user->ID, 'selected_postcode_areas', true), true);
//         if (!is_array($selected_postcode_areas)) {
//             $selected_postcode_areas = []; // Initialize as an empty array if not an array
//         }
//         foreach ($selected_postcode_areas as $region => $codes) {
//             foreach ($codes as $code) {
//                 if (strpos($code, $postcode_prefix) === 0 && $user_credits > 0) {
//                     $eligible_recipients[] = $user->ID;
//                     break 2; // Break both loops as we only need to confirm the user once
//                 }
//             }
//         }
//     }
//     return $eligible_recipients;
// }

// function deduct_credit_from_user($user_id) {
//     $credits = get_user_meta($user_id, '_user_credits', true);
//     $credits = intval($credits);

//     if ($credits > 0) {
//         $credits--; // Deduct one credit
//         update_user_meta($user_id, '_user_credits', $credits);
//         return true; // Successfully deducted credit
//     }

//     return false; // User had no credits to deduct
// }

// function get_eligible_recipients_for_lead($postcode_prefix) {
//     $eligible_recipients = [];
//     $users = get_users(['meta_key' => 'selected_postcode_areas', 'meta_compare' => 'LIKE', 'meta_value' => $postcode_prefix]);

//     foreach ($users as $user) {
//         $user_credits = (int)get_user_meta($user->ID, '_user_credits', true);
//         if ($user_credits > 0) {
//             $eligible_recipients[] = $user->ID;
//         }
//     }

//     return $eligible_recipients;
// }
// function deduct_credit_from_user($user_id) {
//     $credits = (int)get_user_meta($user_id, '_user_credits', true);
//     if ($credits > 0) {
//         update_user_meta($user_id, '_user_credits', --$credits);
//         return true;
//     }
//     return false;
// }
// function assign_lead_to_user($user_id, $lead_data, $lead_id) {
//     // Example of associating a lead post with a user. Adjust according to your storage method.
//     update_post_meta($lead_id, 'assigned_user', $user_id);
//     return true;
// }
// function send_lead_email_to_user($user_id, $lead_data) {
//     $user_info = get_userdata($user_id);
//     $to = $user_info->user_email;
//     $subject = "New Lead: " . $lead_data['registration'];
//     $body = "You have a new lead. Here are the details:\n\n" . print_r($lead_data, true); // Customize this
//     $headers = ['Content-Type: text/html; charset=UTF-8'];

//     return wp_mail($to, $subject, $body, $headers);
// }
// add_action('profile_update', 'update_user_postcode_queues', 10, 2);
// function update_user_postcode_queues($user_id, $old_user_data) {
//     $selected_postcode_areas = json_decode(get_user_meta($user_id, 'selected_postcode_areas', true), true);
//     if (empty($selected_postcode_areas)) return;

//     foreach ($selected_postcode_areas as $region => $codes) {
//         foreach ($codes as $code) {
//             $postcode_prefix = substr($code, 0, 2);
//             $queue_key = "recipients_queue_{$postcode_prefix}";
//             $queue = get_option($queue_key, []);

//             // If not already in queue, add user ID
//             if (!in_array($user_id, $queue)) {
//                 $queue[] = $user_id;
//                 update_option($queue_key, $queue);
//             }
//         }
//     }
// }
// function process_lead_submission_with_lock(WP_REST_Request $request) {
//     $lock_key = 'process_lead_lock';
//     $lock_timeout = 10; // Lock timeout in seconds

//     // Attempt to acquire lock
//     if (get_transient($lock_key)) {
//         return new WP_REST_Response(['message' => 'System is busy, please try again'], 429);
//     }

//     set_transient($lock_key, true, $lock_timeout);

//     // [Process lead submission logic goes here]

//     // Release lock
//     delete_transient($lock_key);

//     return new WP_REST_Response(['message' => 'Lead processed successfully'], 200);
// }
