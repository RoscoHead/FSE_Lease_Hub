<?php
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Shortcode: [fse_lease_hub] + alias [fse_lease_hub_leases].
 * No attributes = global defaults from Ajustes > FSE Lease Hub.
 * With attributes = override only those.
 *
 * Examples:
 *   [fse_lease_hub]
 *   [fse_lease_hub fleet="123"]
 *   [fse_lease_hub fleet="123" layout="list" theme="dark" limit="10" height="600" show="price,location,status"]
 */
class FLH_Shortcode {

	public static function init() {
		add_shortcode( 'fse_lease_hub', [ __CLASS__, 'handle' ] );
		add_shortcode( 'fse_lease_hub_leases', [ __CLASS__, 'handle' ] );
	}

	public static function handle( $atts, $content = null, $tag = '' ) {
		$atts = is_array( $atts ) ? $atts : [];
		// Normalize keys (WP already lowercases, but be safe for board/sort aliases).
		$clean_atts = [];
		foreach ( $atts as $k => $v ) {
			$clean_atts[ strtolower( (string) $k ) ] = $v;
		}
		if ( isset( $clean_atts['board'] ) && ! isset( $clean_atts['fleet'] ) ) {
			$clean_atts['fleet'] = $clean_atts['board'];
		}
		if ( isset( $clean_atts['sort'] ) && ! isset( $clean_atts['order'] ) ) {
			$clean_atts['order'] = $clean_atts['sort'];
		}

		$defaults = FLH_Embed_Renderer::get_defaults();
		// shortcode_atts filters unknown keys out; merge manually to keep show_x flags.
		$merged = shortcode_atts(
			[
				'fleet'  => $defaults['fleet'],
				'theme'  => $defaults['theme'],
				'layout' => $defaults['layout'],
				'order'  => $defaults['order'],
				'sort'   => $defaults['order'],
				'board'  => $defaults['fleet'],
				'limit'  => $defaults['limit'],
				'height' => $defaults['height'],
				'show'   => $defaults['show'],
			],
			$clean_atts,
			$tag
		);
		// Preserve per-field show_x / price / location ... overrides.
		foreach ( $clean_atts as $k => $v ) {
			if ( ! array_key_exists( $k, $merged ) ) {
				$merged[ $k ] = $v;
			}
		}

		return FLH_Embed_Renderer::render( $merged );
	}
}
