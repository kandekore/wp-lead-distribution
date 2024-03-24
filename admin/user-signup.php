<?php

add_action('woocommerce_after_order_notes', 'add_custom_postcode_selection_to_checkout');
function add_custom_postcode_selection_to_checkout($checkout) {
    // Fetch saved postcode areas
    $saved_postcode_areas = json_decode(get_option('custom_postcode_areas'), true);

    if (!is_array($saved_postcode_areas) || empty($saved_postcode_areas)) {
        echo '<p>No postcode areas available for selection.</p>';
        return;
    }

    echo '<div id="custom_postcode_selection"><h3>' . __('Select Postcode Areas') . '</h3>';

    foreach ($saved_postcode_areas as $region => $codes) {
        echo "<h4>" . esc_html($region) . "</h4>";
        echo "<label><input type='checkbox' class='select-all' data-region='" . esc_attr($region) . "'> " . __('Select All in ') . esc_html($region) . "</label><br>";
    
        foreach ($codes as $code) {
            $field_id = 'postcode_area_' . sanitize_title($code); // ID for individual checkboxes
            $field_name = 'postcode_areas[' . esc_attr($region) . '][]'; // Name attribute structured for array input
    
            echo '<label for="' . esc_attr($field_id) . '" class="checkbox ' . esc_attr($region) . '">';
            // Add data-region attribute to each checkbox for accurate JS targeting
            echo '<input type="checkbox" id="' . esc_attr($field_id) . '" name="' . $field_name . '" value="' . esc_attr($code) . '" class="postcode-area-checkbox ' . esc_attr($region) . '" data-region="' . esc_attr($region) . '"> ';
            echo esc_html($code);
            echo '</label><br>';
        }
    }
    echo '</div>';
}

add_action('woocommerce_checkout_update_order_meta', 'save_custom_postcode_selection');
function save_custom_postcode_selection($order_id) {
    $postcode_areas = get_option('custom_postcode_areas', true);
    $postcode_areas = $postcode_areas ? json_decode($postcode_areas, true) : [];
    $selected_areas = [];

    foreach ($postcode_areas as $region => $codes) {
        foreach ($codes as $code) {
            if (!empty($_POST['postcode_area_' . sanitize_title($code)])) {
                $selected_areas[] = $code;
            }
        }
    }

    if (!empty($selected_areas)) {
        update_post_meta($order_id, 'selected_postcode_areas', json_encode($selected_areas));
    }
}

add_action('wp_footer', 'postcode_selection_scripts');
function postcode_selection_scripts() {
    if (is_checkout()) {
?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.select-all').forEach(function(selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            let region = this.getAttribute('data-region');
            let checkboxes = document.querySelectorAll('.postcode-area-checkbox[data-region="' + region + '"]');
            checkboxes.forEach(function(checkbox) {
                checkbox.checked = selectAllCheckbox.checked;
            });
        });
    });
});
</script>
<?php
    }
}
