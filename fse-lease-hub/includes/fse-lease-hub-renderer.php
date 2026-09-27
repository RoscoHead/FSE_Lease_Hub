<?php
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Shared renderer for the FSE Lease Hub leases embed.
 *
 * Used by the Gutenberg block render_callback, the shortcode handler
 * and the global defaults (Ajustes > FSE Lease Hub), so all of them
 * output exactly the same <iframe>.
 */
class FLH_Embed_Renderer {

	const ALLOWED_SHOW   = [ 'price', 'location', 'description', 'status', 'conditions', 'contact' ];
	const ALLOWED_THEMES = [ 'light', 'dark' ];
	const ALLOWED_LAYOUTS = [ 'cards', 'list', 'compact' ];
	const ALLOWED_ORDERS = [ 'newest', 'price_asc', 'price_desc', 'registration' ];

	/** Hard-coded fallback defaults. */
	public static function get_hard_defaults() {
		return [
			'fleet'  => 0,
			'theme'  => 'light',
			'layout' => 'cards',
			'order'  => 'newest',
			'limit'  => 20,
			'height' => 700,
			'show'   => implode( ',', self::ALLOWED_SHOW ),
		];
	}

	/**
	 * Effective defaults: hard defaults merged with Ajustes > FSE Lease Hub.
	 *
	 * @return array
	 */
	public static function get_defaults() {
		$hard  = self::get_hard_defaults();
		$saved = get_option( 'flh_defaults', [] );
		if ( ! is_array( $saved ) ) {
			$saved = [];
		}
		$merged = array_merge( $hard, $saved );
		return self::sanitize( $merged );
	}

	/** Interpret 1/0, true/false, yes/no, on/off strings as boolean. */
	private static function to_bool( $value, $default = true ) {
		if ( is_bool( $value ) ) {
			return $value;
		}
		if ( $value === null || $value === '' ) {
			return $default;
		}
		$filtered = filter_var( $value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE );
		return $filtered === null ? $default : $filtered;
	}

	/** Parse a comma-separated show list, keeping only allowed keys. */
	private static function parse_show_string( $raw ) {
		if ( is_array( $raw ) ) {
			$parts = $raw;
		} else {
			$parts = explode( ',', (string) $raw );
		}
		$clean = [];
		foreach ( $parts as $part ) {
			$key = strtolower( trim( (string) $part ) );
			if ( in_array( $key, self::ALLOWED_SHOW, true ) && ! in_array( $key, $clean, true ) ) {
				$clean[] = $key;
			}
		}
		return $clean;
	}

	/**
	 * Normalize any input (block attributes, shortcode atts, option array).
	 * Accepts aliases: board => fleet, sort => order, show="a,b" or show_x="1/0" / x="1/0".
	 *
	 * @param array $atts
	 * @return array Sanitized: fleet, theme, layout, order, limit, height, show (+show_list).
	 */
	public static function sanitize( $atts ) {
		if ( ! is_array( $atts ) ) {
			$atts = [];
		}
		// Normalize keys to lowercase (shortcodes arrive lowercased, blocks keep case).
		$normalized = [];
		foreach ( $atts as $k => $v ) {
			$normalized[ strtolower( (string) $k ) ] = $v;
		}
		$atts = $normalized;

		$defaults = self::get_hard_defaults();
		$saved    = get_option( 'flh_defaults', [] );
		if ( is_array( $saved ) ) {
			// Only merge known keys from the saved option to avoid recursion issues.
			foreach ( [ 'fleet', 'theme', 'layout', 'order', 'limit', 'height', 'show' ] as $k ) {
				if ( array_key_exists( $k, $saved ) ) {
					$defaults[ $k ] = $saved[ $k ];
				}
			}
		}

		// Aliases.
		if ( isset( $atts['board'] ) && ! isset( $atts['fleet'] ) ) {
			$atts['fleet'] = $atts['board'];
		}
		if ( isset( $atts['sort'] ) && ! isset( $atts['order'] ) ) {
			$atts['order'] = $atts['sort'];
		}

		$fleet = isset( $atts['fleet'] ) ? absint( $atts['fleet'] ) : absint( $defaults['fleet'] );

		$theme = isset( $atts['theme'] ) ? sanitize_key( (string) $atts['theme'] ) : $defaults['theme'];
		if ( ! in_array( $theme, self::ALLOWED_THEMES, true ) ) {
			$theme = $defaults['theme'];
		}

		$layout = isset( $atts['layout'] ) ? sanitize_key( (string) $atts['layout'] ) : $defaults['layout'];
		if ( ! in_array( $layout, self::ALLOWED_LAYOUTS, true ) ) {
			$layout = $defaults['layout'];
		}

		$order = isset( $atts['order'] ) ? sanitize_key( (string) $atts['order'] ) : $defaults['order'];
		if ( ! in_array( $order, self::ALLOWED_ORDERS, true ) ) {
			$order = $defaults['order'];
		}

		$limit = isset( $atts['limit'] ) ? absint( $atts['limit'] ) : absint( $defaults['limit'] );
		if ( $limit < 1 || $limit > 100 ) {
			$limit = absint( $defaults['limit'] ) > 0 ? absint( $defaults['limit'] ) : 20;
		}

		$height = isset( $atts['height'] ) ? absint( $atts['height'] ) : absint( $defaults['height'] );
		if ( $height < 100 || $height > 3000 ) {
			$height = absint( $defaults['height'] ) > 0 ? absint( $defaults['height'] ) : 700;
		}

		// Show list: explicit "show" wins; otherwise rebuild from per-field flags if present.
		$default_show_list = self::parse_show_string( $defaults['show'] );
		if ( array_key_exists( 'show', $atts ) && $atts['show'] !== null && $atts['show'] !== '' ) {
			$show_list = self::parse_show_string( $atts['show'] );
		} else {
			$has_flag = false;
			foreach ( self::ALLOWED_SHOW as $key ) {
				if ( array_key_exists( $key, $atts ) || array_key_exists( 'show_' . $key, $atts ) ) {
					$has_flag = true;
					break;
				}
			}
			if ( ! $has_flag ) {
				$show_list = $default_show_list;
			} else {
				// Detect block-style (real booleans present) vs shortcode-style (only overrides).
				$block_style = false;
				foreach ( self::ALLOWED_SHOW as $key ) {
					if ( array_key_exists( $key, $atts ) && is_bool( $atts[ $key ] ) ) {
						$block_style = true;
						break;
					}
				}
				if ( $block_style ) {
					$show_list = [];
					foreach ( self::ALLOWED_SHOW as $key ) {
						$val = $atts[ $key ] ?? $atts[ 'show_' . $key ] ?? null;
						if ( $val === null ) {
							// Block always sends all flags; missing means keep default membership.
							if ( in_array( $key, $default_show_list, true ) ) {
								$show_list[] = $key;
							}
						} elseif ( self::to_bool( $val, true ) ) {
							$show_list[] = $key;
						}
					}
				} else {
					$show_list = $default_show_list;
					foreach ( self::ALLOWED_SHOW as $key ) {
						if ( array_key_exists( 'show_' . $key, $atts ) ) {
							$on = self::to_bool( $atts[ 'show_' . $key ], true );
							$show_list = $on
								? array_values( array_unique( array_merge( $show_list, [ $key ] ) ) )
								: array_values( array_diff( $show_list, [ $key ] ) );
						} elseif ( array_key_exists( $key, $atts ) ) {
							$on = self::to_bool( $atts[ $key ], true );
							$show_list = $on
								? array_values( array_unique( array_merge( $show_list, [ $key ] ) ) )
								: array_values( array_diff( $show_list, [ $key ] ) );
						}
					}
				}
			}
		}

		return [
			'fleet'     => $fleet,
			'theme'     => $theme,
			'layout'    => $layout,
			'order'     => $order,
			'limit'     => $limit,
			'height'    => $height,
			'show'      => implode( ',', $show_list ),
			'show_list' => $show_list,
		];
	}

	/**
	 * Render the embed HTML.
	 *
	 * @param array         $atts  Fleet/theme/layout/order/limit/height/show...
	 * @param mixed|null    $block Optional WP_Block for wrapper attributes.
	 * @return string
	 */
	public static function render( $atts, $block = null ) {
		$a = self::sanitize( $atts );

		if ( $a['fleet'] <= 0 ) {
			if ( function_exists( 'current_user_can' ) && current_user_can( 'edit_posts' ) ) {
				return '<p class="flh-leases-notice">' . esc_html__( 'FSE Lease Hub: set your Fleet ID in Ajustes > FSE Lease Hub or in the block/shortcode attributes.', 'fse-lease-hub' ) . '</p>';
			}
			return '<!-- FSE Lease Hub: missing fleet ID -->';
		}

		$src = add_query_arg(
			[
				'board'  => $a['fleet'],
				'theme'  => $a['theme'],
				'layout' => $a['layout'],
				'sort'   => $a['order'],
				'limit'  => $a['limit'],
				'show'   => $a['show'],
			],
			'https://fseleasehub.com/embed.php'
		);

		$wrapper = 'class="flh-leases-embed"';
		if ( $block instanceof WP_Block && function_exists( 'get_block_wrapper_attributes' ) ) {
			$wrapper = get_block_wrapper_attributes( [ 'class' => 'flh-leases-embed' ] );
		}

		return sprintf(
			'<div %s><iframe src="%s" width="100%%" height="%d" style="border:1px solid #e3e8ef;border-radius:12px;" loading="lazy" title="%s"></iframe></div>',
			$wrapper,
			esc_url( $src ),
			absint( $a['height'] ),
			esc_attr__( 'Aircraft for lease - FSELeaseHub', 'fse-lease-hub' )
		);
	}
}
