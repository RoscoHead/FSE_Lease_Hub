<?php
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
     die;
}

require_once FLH_BASE_PATH . '/blocks/leases-embed/leases-embed.php';

class FLH_Plugin {

	// class instance
	static $instance;

	// class constructor
	public function __construct() {
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