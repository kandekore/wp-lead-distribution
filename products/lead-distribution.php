<?php
 if ( ! defined( 'ABSPATH' ) ) exit;    

function get_customers_by_region() {
    $postcode_areas = load_postcode_areas_from_json(); // Load regions and their postcodes
    $users_by_region = [];

    $users = get_users();
    foreach ($users as $user) {
        // Fetch user's selected postcode areas
        $selected_postcode_areas = json_decode(get_user_meta($user->ID, 'selected_postcode_areas', true), true);

        // Check the user's credits
        $user_credits = (int)get_user_meta($user->ID, '_user_credits', true);

        if (!empty($selected_postcode_areas) && $user_credits > 0) { 
            foreach ($selected_postcode_areas as $region => $codes) {
                foreach ($postcode_areas as $available_region => $available_codes) {
                    // If the user-selected region matches available regions and codes
                    if ($region === $available_region) {
                        foreach ($codes as $code) {
                            // Checking for both direct matches and wildcard matches
                            $codePattern = rtrim($code, "*") . ".*"; // Convert to regex pattern
                            foreach ($available_codes as $available_code) {
                                if (preg_match("/^$codePattern/", $available_code)) {
                                    // Add user to the corresponding region
                                    if (!isset($users_by_region[$region])) {
                                        $users_by_region[$region] = [];
                                    }
                                    if (!in_array($user->ID, $users_by_region[$region])) {
                                        $users_by_region[$region][] = $user->ID;
                                    }
                                    break 2; 
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    return $users_by_region;
}
