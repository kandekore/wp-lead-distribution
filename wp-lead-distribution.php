<?php
/**
 * Plugin Name: WordPress Lead Distribution
 * Description: Collects and distributes leads to users on a subscription basis.
 * Version: 1.0
 * Author: D.Kandekore
 */

 if ( ! defined( 'ABSPATH' ) ) exit;    

// Define plugin directory path constant
define('LMP_PLUGIN_DIR', plugin_dir_path(__FILE__));

// Include the files

include_once plugin_dir_path(__FILE__) . 'includes/api-endpoints.php';
include_once plugin_dir_path(__FILE__) . 'includes/lead-processing.php';
include_once plugin_dir_path(__FILE__) . 'includes/utility-functions.php';
include_once plugin_dir_path(__FILE__) . 'admin/admin-pages.php';
include_once plugin_dir_path(__FILE__) . 'includes/load-postcodes.php';
include_once plugin_dir_path(__FILE__) . 'admin/user-signup.php';


register_deactivation_hook(__FILE__, 'clear_saved_postcode_data');

function clear_saved_postcode_data() {
    delete_option('custom_postcode_areas');
}
