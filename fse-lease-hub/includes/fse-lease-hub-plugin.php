<?php
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
     die;
}

require_once FLH_BASE_PATH . 'includes/fse-lease-hub-renderer.php';
require_once FLH_BASE_PATH . 'includes/fse-lease-hub-settings.php';
require_once FLH_BASE_PATH . 'includes/fse-lease-hub-shortcode.php';
require_once FLH_BASE_PATH . '/blocks/leases-embed/leases-embed.php';

class FLH_Plugin {

	// class instance
	static $instance;

	// class constructor
	public function __construct() {
		// Global defaults (Settings > FSE Lease Hub)
		FLH_Settings::init();
		// Shortcodes: [fse_lease_hub] + alias [fse_lease_hub_leases]
		FLH_Shortcode::init();
		// Register blocks (currently only 1)
		FLH_Lease_Embed::get_instance();
	}
	
	/** Singleton instance */
	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}
?>