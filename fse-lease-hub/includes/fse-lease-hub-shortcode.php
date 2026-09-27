<?php
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Shortcode: [fse_lease_hub] + alias [fse_lease_hub_leases].
 * Global only: takes NO attributes, always renders the defaults
 * saved in Ajustes > FSE Lease Hub.
 *
 * Example:
 *   [fse_lease_hub]
 */
class FLH_Shortcode {

	public static function init() {
		add_shortcode( 'fse_lease_hub', [ __CLASS__, 'handle' ] );
		add_shortcode( 'fse_lease_hub_leases', [ __CLASS__, 'handle' ] );
	}

	public static function handle( $atts = null, $content = null, $tag = '' ) {
		return FLH_Embed_Renderer::render( FLH_Embed_Renderer::get_defaults() );
	}
}
