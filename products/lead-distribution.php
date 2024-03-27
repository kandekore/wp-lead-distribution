<?php

function send_lead_to_user($user_id) {
    $credits = (int) get_user_meta($user_id, '_user_credits', true);

    if ($credits > 0) {
        // Proceed with sending the lead
        // ...

        // Decrement the user's credits
        $credits--;
        update_user_meta($user_id, '_user_credits', $credits);

        // Optional: Handle when credits run out, such as suspending the subscription
        if ($credits <= 0) {
            // Code to suspend the subscription or notify the user
        }
    } else {
        // Insufficient credits, handle accordingly
    }
}
