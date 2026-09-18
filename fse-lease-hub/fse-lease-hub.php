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

/**
 * Register the block.
 */
function fseleasehub_register_block() {
	//register_block_type( __DIR__ . '/build' );
}

add_action( 'init', 'fseleasehub_register_block' );

?>