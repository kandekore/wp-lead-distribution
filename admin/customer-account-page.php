<?php

function add_areas_endpoint() {
    add_rewrite_endpoint('areas', EP_ROOT | EP_PAGES);
}

add_action('init', 'add_areas_endpoint');

// Ensure WooCommerce knows about the new endpoint to prevent 404 errors
function areas_endpoint_query_vars($vars) {
    $vars[] = 'areas';
    return $vars;
}

add_filter('query_vars', 'areas_endpoint_query_vars', 0);

// Add the new endpoint into the My Account menu
function add_areas_link_my_account($items) {
    // Insert the new tab before the logout tab
    $logout = $items['customer-logout'];
    unset($items['customer-logout']);
    $items['areas'] = __('Areas', 'text-domain');
    $items['customer-logout'] = $logout;
    
    return $items;
}

add_filter('woocommerce_account_menu_items', 'add_areas_link_my_account');

function areas_endpoint_content() {
    $user_id = get_current_user_id();
    $selected_postcode_areas_json = get_user_meta($user_id, 'selected_postcode_areas', true);
    $selected_postcode_areas = json_decode($selected_postcode_areas_json, true);

    echo '<h3>' . __('Your Selected Postcode Areas', 'text-domain') . '</h3>';
    
    // Check if the decoded JSON is an array
    if (is_array($selected_postcode_areas) && !empty($selected_postcode_areas)) {
        foreach ($selected_postcode_areas as $region => $codes) {
            // Ensure $codes is definitely an array to avoid implode error
            if (is_array($codes)) {
                echo '<p><strong>' . esc_html($region) . ':</strong> ' . esc_html(implode(', ', $codes)) . '</p>';
            }
        }
    } else {
        echo '<p>' . __('No postcode areas selected.', 'text-domain') . '</p>';
    }
    
    
}

add_action('woocommerce_account_areas_endpoint', 'areas_endpoint_content');


add_action('woocommerce_account_dashboard', 'display_user_credit_balance_and_renewal_info');

function display_user_credit_balance_and_renewal_info() {
    $user_id = get_current_user_id();
    $credits = (int) get_user_meta($user_id, '_user_credits', true); // Casting to ensure we have an integer value

    // Display the credit balance
    echo '<div class="user-credits-info">';
    echo '<h3>' . __('Your Credits', 'text-domain') . '</h3>';
    printf('<p>' . __('You currently have %s credits.', 'text-domain') . '</p>', esc_html($credits));

    // Explain the renewal condition
    echo '<p>' . __('Your subscription will automatically renew when your credits drop to 5 or below.', 'text-domain') . '</p>';
    echo '</div>';
}

function enqueue_account_page_styles() {
    if (is_account_page()) {
        // Assuming you have a custom CSS file
        wp_enqueue_style('my-account-custom-style', get_template_directory_uri() . '/css/my-account.css');
        
        // Or directly adding inline styles
        $custom_css = "
            .user-credits-info {
                background-color: #f7f7f7;
                padding: 20px;
                margin-bottom: 20px;
            }
            .user-credits-info h3 {
                color: #333;
            }
        ";
        wp_add_inline_style('woocommerce-general', $custom_css);
    }
}
add_action('wp_enqueue_scripts', 'enqueue_account_page_styles');
