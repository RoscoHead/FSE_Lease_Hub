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

require_once __DIR__ . '/blocks/leases-embed/leases-embed.php';

add_action( 'init', 'fseleasehub_register_block' );
?>