<?php
/*
Plugin Name: FSE Lease Hub
Description: FSE Lease Hub Wordpress plugin
Author: RoscoHead
Version: 0.1.0
License: MIT
Requires at least: 6.0
*/

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
     die;
}

define( 'FLH_BASE_PATH', plugin_dir_path( __FILE__ ) );
require_once FLH_BASE_PATH . 'includes/fse-lease-hub-plugin.php';
add_action( 'plugins_loaded', function () {
	FLH_Plugin::get_instance();
} );

?>