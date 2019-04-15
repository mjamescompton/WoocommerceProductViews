<?php
/**
 * Plugin Name: CH Google Anylytics Data API
 * Plugin URI: https://crowdyhouse.com
 * Description: Connect to the google anylytic api and display relvent data to front end.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Define WC_PLUGIN_FILE.
if ( ! defined( 'CH_GA_DATA_API_FILE' ) ) {
    define( 'CH_GA_DATA_API_FILE', __FILE__ );
}

// Include the main WooCommerce class.
if ( ! class_exists( 'CH_GA_Data_API' ) ) {
    include_once dirname( __FILE__ ) . '/includes/class-ch-ga-data-api.php';
}

// Global for backwards compatibility.
// $GLOBALS['ch_custom_export'] = new CH_Custom_Export;
$ch_ga_data_api = new CH_GA_Data_API();