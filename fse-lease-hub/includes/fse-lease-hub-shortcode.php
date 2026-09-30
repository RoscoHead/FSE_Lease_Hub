<?php
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Shortcode: [fse_lease_hub] + alias [fse_lease_hub_leases].
 * Bare tag renders the defaults saved in Settings > FSE Lease Hub.
 * Any attribute overrides the corresponding default.
 *
 * Height accepts a fixed value in px or "auto" (default). With "auto"
 * the iframe adapts to the content height announced by the FSELeaseHub
 * page via postMessage, starting from a 550px fallback.
 *
 * Examples:
 *   [fse_lease_hub]
 *   [fse_lease_hub fleet="123" height="auto" show_header="1" header_shine="1" show_border="1" border_color="#c9a03f" border_width="1"]
 *   [fse_lease_hub fleet="123" height="550"]
 *   [fse_lease_hub link="0"] (hides the repeated "View full listing" links)
 */
class FLH_Shortcode {

	public static function init() {
		add_shortcode( 'fse_lease_hub', [ __CLASS__, 'handle' ] );
		add_shortcode( 'fse_lease_hub_leases', [ __CLASS__, 'handle' ] );
	}

	public static function handle( $atts = null, $content = null, $tag = '' ) {
		$defaults = FLH_Embed_Renderer::get_defaults();
		if ( ! is_array( $atts ) ) {
			$atts = [];
		}
		// Map aliases before shortcode_atts fills every key with defaults
		// (otherwise the renderer would never see the alias).
		if ( isset( $atts['link'] ) && ! isset( $atts['show_links'] ) ) {
			$atts['show_links'] = $atts['link'];
		}
		if ( isset( $atts['board'] ) && ! isset( $atts['fleet'] ) ) {
			$atts['fleet'] = $atts['board'];
		}
		if ( isset( $atts['sort'] ) && ! isset( $atts['order'] ) ) {
			$atts['order'] = $atts['sort'];
		}
		if ( function_exists( 'shortcode_atts' ) ) {
			$atts = shortcode_atts(
				[
					'fleet'        => $defaults['fleet'],
					'board'        => $defaults['fleet'],
					'theme'        => $defaults['theme'],
					'layout'       => $defaults['layout'],
					'order'        => $defaults['order'],
					'sort'         => $defaults['order'],
					'limit'        => $defaults['limit'],
					'height'       => $defaults['height'],
					'show'         => $defaults['show'],
					'show_links'   => $defaults['show_links'] ? '1' : '0',
					'link'         => $defaults['show_links'] ? '1' : '0',
					'show_logo'    => $defaults['show_logo'] ? '1' : '0',
					'show_header'  => $defaults['show_header'] ? '1' : '0',
					'header_shine' => $defaults['header_shine'] ? '1' : '0',
					'header_align' => $defaults['header_align'],
					'header_bg'    => $defaults['header_bg'],
					'header_color' => $defaults['header_color'],
					'show_border'  => $defaults['show_border'] ? '1' : '0',
					'border_color' => $defaults['border_color'],
					'border_width' => $defaults['border_width'],
				],
				$atts,
				$tag
			);
		} else {
			$atts = array_merge( $defaults, $atts );
		}
		// Per-field show flags (show_price="0", price="0", ...) are passed
		// through untouched; the renderer merges them over the defaults.
		// render() already sanitizes internally, so no double sanitize needed
		// (main's sanitize($a) call is subsumed here).
		return FLH_Embed_Renderer::render( $atts );
	}
}
