<?php
/*
Plugin Name: FSE Lease Hub
Description: Access features of FSE Lease Hub from your WordPress site. Block + shortcode [fse_lease_hub] with global defaults in Settings > FSE Lease Hub.
Author: RoscoHead
Version: 1.10.5
License: MIT
Requires at least: 6.0
*/

 // If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
     die;
}

define( 'FLH_VERSION', '1.10.5' );
define( 'FLH_BASE_PATH', plugin_dir_path( __FILE__ ) );
require_once FLH_BASE_PATH . 'includes/fse-lease-hub-plugin.php';
add_action( 'plugins_loaded', function () {
	// All the initialisation done here
	FLH_Plugin::get_instance();
} );

?>