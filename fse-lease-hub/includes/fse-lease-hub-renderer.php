<?php
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Shared renderer for the FSE Lease Hub leases embed.
 *
 * Used by the Gutenberg block render_callback, the shortcode handler
 * and the global defaults (Settings > FSE Lease Hub), so all of them
 * output exactly the same <iframe>.
 */
class FLH_Embed_Renderer {

	const ALLOWED_SHOW   = [ 'price', 'location', 'description', 'status', 'conditions', 'contact' ];
	const ALLOWED_THEMES = [ 'light', 'dark' ];
	const ALLOWED_LAYOUTS = [ 'cards', 'list', 'compact' ];
	const ALLOWED_ORDERS = [ 'newest', 'price_asc', 'price_desc', 'registration' ];
	const ALLOWED_ALIGN = [ 'left', 'center', 'right' ];

	/** Hard-coded fallback defaults. */
	public static function get_hard_defaults() {
		return [
			'fleet'        => 0,
			'theme'        => 'light',
			'layout'       => 'cards',
			'order'        => 'newest',
			'limit'        => 20,
			'height'       => 'auto',
			'show'         => implode( ',', self::ALLOWED_SHOW ),
			'show_links'   => false,
			'show_logo'    => true,
			'show_header'  => true,
			'header_shine' => true,
			'header_align' => 'center',
			'header_bg'    => '#101828',
			'header_color' => '#ffffff',
			'show_border'  => true,
			'border_color' => '#c9a03f',
			'border_width' => 1,
		];
	}

	/**
	 * Effective defaults: hard defaults merged with Settings > FSE Lease Hub.
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

	/** Sanitize a hex color (#rgb or #rrggbb). Returns lowercase value or fallback. */
	private static function sanitize_hex_color_fallback( $color, $fallback = '#c9a03f' ) {
		$color = is_string( $color ) ? trim( $color ) : '';
		if ( function_exists( 'sanitize_hex_color' ) ) {
			$sanitized = sanitize_hex_color( $color );
			return $sanitized ? strtolower( $sanitized ) : strtolower( $fallback );
		}
		if ( preg_match( '/^#([a-fA-F0-9]{3}|[a-fA-F0-9]{6})$/', $color ) ) {
			if ( strlen( $color ) === 4 ) {
				$color = '#' . $color[1] . $color[1] . $color[2] . $color[2] . $color[3] . $color[3];
			}
			return strtolower( $color );
		}
		return strtolower( $fallback );
	}

	/** Enqueue the shared frontend stylesheet + auto-height resizer (block and shortcode). */
	private static function ensure_frontend_assets() {
		$ver = 					defined( 'FLH_VERSION' ) ? FLH_VERSION : '1.10.5';
		if ( function_exists( 'wp_register_style' ) ) {
			$handle = 'flh-frontend';
			if ( ! wp_style_is( $handle, 'registered' ) && ! wp_style_is( $handle, 'enqueued' ) ) {
				$src = function_exists( 'plugins_url' ) && defined( 'FLH_BASE_PATH' )
					? plugins_url( 'assets/css/flh-frontend.css', FLH_BASE_PATH . 'fse-lease-hub.php' )
					: false;
				if ( $src ) {
					wp_register_style( $handle, $src, [], $ver );
				}
			}
			if ( function_exists( 'wp_enqueue_style' ) && wp_style_is( $handle, 'registered' ) ) {
				wp_enqueue_style( $handle );
			}
		}
		if ( function_exists( 'wp_register_script' ) ) {
			$js = 'flh-frontend';
			if ( ! wp_script_is( $js, 'registered' ) && ! wp_script_is( $js, 'enqueued' ) ) {
				$src = function_exists( 'plugins_url' ) && defined( 'FLH_BASE_PATH' )
					? plugins_url( 'assets/js/flh-frontend.js', FLH_BASE_PATH . 'fse-lease-hub.php' )
					: false;
				if ( $src ) {
					wp_register_script( $js, $src, [], $ver, true );
				}
			}
			if ( function_exists( 'wp_enqueue_script' ) && wp_script_is( $js, 'registered' ) ) {
				wp_enqueue_script( $js );
			}
		}
	}

	/** Filesystem path of the bundled header logo (assets/img/site-logo.png). */
	public static function get_logo_path() {
		return defined( 'FLH_BASE_PATH' ) ? FLH_BASE_PATH . 'assets/img/site-logo.png' : '';
	}

	/**
	 * Public URL of the bundled header logo, or '' when the site owner
	 * has not placed site-logo.png in assets/img/ yet.
	 */
	public static function get_logo_url() {
		$path = self::get_logo_path();
		if ( $path === '' || ! file_exists( $path ) ) {
			return '';
		}
		if ( ! function_exists( 'plugins_url' ) || ! defined( 'FLH_BASE_PATH' ) ) {
			return '';
		}
		return plugins_url( 'assets/img/site-logo.png', FLH_BASE_PATH . 'fse-lease-hub.php' );
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
		$saved    = function_exists( 'get_option' ) ? get_option( 'flh_defaults', [] ) : [];
		if ( is_array( $saved ) ) {
			// Only merge known keys from the saved option to avoid recursion issues.
			foreach ( [ 'fleet', 'theme', 'layout', 'order', 'limit', 'height', 'show', 'show_links', 'show_logo', 'show_header', 'header_shine', 'header_align', 'header_bg', 'header_color', 'show_border', 'border_color', 'border_width' ] as $k ) {
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
		if ( isset( $atts['link'] ) && ! isset( $atts['show_links'] ) ) {
			$atts['show_links'] = $atts['link'];
		}
		// Block camelCase aliases (lowercased by normalization above).
		$camel_aliases = [
			'showlinks'         => 'show_links',
			'showlogo'          => 'show_logo',
			'showheader'        => 'show_header',
			'headershine'       => 'header_shine',
			'headeralign'       => 'header_align',
			'headerbg'          => 'header_bg',
			'headerbackground'  => 'header_bg',
			'headercolor'       => 'header_color',
			'headertextcolor'   => 'header_color',
			'showborder'        => 'show_border',
			'bordercolor'       => 'border_color',
			'borderwidth'       => 'border_width',
		];
		foreach ( $camel_aliases as $from => $to ) {
			if ( isset( $atts[ $from ] ) && ! isset( $atts[ $to ] ) ) {
				$atts[ $to ] = $atts[ $from ];
			}
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

		// Height accepts a fixed px value (100-3000) or "auto" (adapt to content).
		$height = 'auto';
		$candidate = array_key_exists( 'height', $atts ) ? $atts['height'] : $defaults['height'];
		if ( is_string( $candidate ) && strtolower( trim( $candidate ) ) === 'auto' ) {
			$height = 'auto';
		} else {
			$num = absint( $candidate );
			if ( $num >= 100 && $num <= 3000 ) {
				$height = $num;
			} else {
				$def_h = $defaults['height'];
				if ( is_string( $def_h ) && strtolower( trim( (string) $def_h ) ) === 'auto' ) {
					$height = 'auto';
				} else {
					$num_def = absint( $def_h );
					$height  = ( $num_def >= 100 && $num_def <= 3000 ) ? $num_def : 550;
				}
			}
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

		$hard = self::get_hard_defaults();

		$show_links = array_key_exists( 'show_links', $atts )
			? self::to_bool( $atts['show_links'], (bool) $defaults['show_links'] )
			: (bool) $defaults['show_links'];

		$show_logo = array_key_exists( 'show_logo', $atts )
			? self::to_bool( $atts['show_logo'], (bool) $defaults['show_logo'] )
			: (bool) $defaults['show_logo'];

		$show_header = array_key_exists( 'show_header', $atts )
			? self::to_bool( $atts['show_header'], (bool) $defaults['show_header'] )
			: (bool) $defaults['show_header'];

		$header_shine = array_key_exists( 'header_shine', $atts )
			? self::to_bool( $atts['header_shine'], (bool) $defaults['header_shine'] )
			: (bool) $defaults['header_shine'];

		$header_align = isset( $atts['header_align'] ) ? sanitize_key( (string) $atts['header_align'] ) : $defaults['header_align'];
		if ( ! in_array( $header_align, self::ALLOWED_ALIGN, true ) ) {
			$header_align = in_array( $defaults['header_align'], self::ALLOWED_ALIGN, true ) ? $defaults['header_align'] : 'center';
		}

		$show_border = array_key_exists( 'show_border', $atts )
			? self::to_bool( $atts['show_border'], (bool) $defaults['show_border'] )
			: (bool) $defaults['show_border'];

		$border_color = array_key_exists( 'border_color', $atts )
			? self::sanitize_hex_color_fallback( $atts['border_color'], (string) $defaults['border_color'] )
			: self::sanitize_hex_color_fallback( $defaults['border_color'], (string) $hard['border_color'] );

		$border_width = array_key_exists( 'border_width', $atts ) ? absint( $atts['border_width'] ) : absint( $defaults['border_width'] );
		if ( $border_width < 0 || $border_width > 12 ) {
			$border_width = absint( $defaults['border_width'] ) <= 12 ? absint( $defaults['border_width'] ) : 1;
		}

		$header_bg = array_key_exists( 'header_bg', $atts ) || array_key_exists( 'header_background', $atts )
			? self::sanitize_hex_color_fallback( $atts['header_bg'] ?? $atts['header_background'] ?? '', (string) $defaults['header_bg'] )
			: self::sanitize_hex_color_fallback( $defaults['header_bg'], (string) $hard['header_bg'] );

		$header_color = array_key_exists( 'header_color', $atts ) || array_key_exists( 'header_text_color', $atts )
			? self::sanitize_hex_color_fallback( $atts['header_color'] ?? $atts['header_text_color'] ?? '', (string) $defaults['header_color'] )
			: self::sanitize_hex_color_fallback( $defaults['header_color'], (string) $hard['header_color'] );

		return [
			'fleet'        => $fleet,
			'theme'        => $theme,
			'layout'       => $layout,
			'order'        => $order,
			'limit'        => $limit,
			'height'       => $height,
			'show'         => implode( ',', $show_list ),
			'show_list'    => $show_list,
			'show_links'   => $show_links,
			'show_logo'    => $show_logo,
			'show_header'  => $show_header,
			'header_shine' => $header_shine,
			'header_align' => $header_align,
			'header_bg'    => $header_bg,
			'header_color' => $header_color,
			'show_border'  => $show_border,
			'border_color' => $border_color,
			'border_width' => $border_width,
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
				return '<p class="flh-leases-notice">' . esc_html__( 'FSE Lease Hub: set your Fleet ID in Settings > FSE Lease Hub or in the block/shortcode attributes.', 'fse-lease-hub' ) . '</p>';
			}
			return '<!-- FSE Lease Hub: missing fleet ID -->';
		}

		self::ensure_frontend_assets();

		$src = add_query_arg(
			[
				'board'  => $a['fleet'],
				'theme'  => $a['theme'],
				'layout' => $a['layout'],
				'sort'   => $a['order'],
				'limit'  => $a['limit'],
				'show'   => $a['show'],
				'link'   => $a['show_links'] ? 1 : 0,
			],
			'https://fseleasehub.com/embed.php'
		);

		$is_auto = ( isset( $a['height'] ) && $a['height'] === 'auto' );

		$wrapper_classes = [ 'flh-leases-embed' ];
		if ( $is_auto ) {
			$wrapper_classes[] = 'flh-auto-height';
		}
		if ( ! empty( $a['show_header'] ) ) {
			$wrapper_classes[] = 'flh-has-header';
		}
		if ( ! empty( $a['show_border'] ) ) {
			$wrapper_classes[] = 'flh-has-border';
		}
		$wrapper_class_str = implode( ' ', $wrapper_classes );

		$wrapper = sprintf(
			'class="%s" style="--flh-border-color:%s;--flh-border-width:%dpx;--flh-header-bg:%s;--flh-header-color:%s;"',
			esc_attr( $wrapper_class_str ),
			esc_attr( $a['border_color'] ),
			absint( $a['border_width'] ),
			esc_attr( $a['header_bg'] ),
			esc_attr( $a['header_color'] )
		);
		if ( $block instanceof WP_Block && function_exists( 'get_block_wrapper_attributes' ) ) {
			$wrapper = get_block_wrapper_attributes(
				[
					'class' => $wrapper_class_str,
					'style' => '--flh-border-color:' . $a['border_color'] . ';--flh-border-width:' . absint( $a['border_width'] ) . 'px;--flh-header-bg:' . $a['header_bg'] . ';--flh-header-color:' . $a['header_color'] . ';',
				]
			);
		}

		$header_html = '';
		if ( ! empty( $a['show_header'] ) ) {
			$header_class = 'flh-leases-header flh-align-' . $a['header_align'];
			if ( ! empty( $a['header_shine'] ) ) {
				$header_class .= ' flh-has-shine';
			}
			$logo_html = '<span class="flh-leases-logo" aria-hidden="true">FSE</span>';
			if ( ! empty( $a['show_logo'] ) ) {
				$logo_url = self::get_logo_url();
				if ( $logo_url !== '' ) {
					$logo_html = sprintf(
						'<img class="flh-leases-logo-img" src="%s" alt="%s" loading="lazy" />',
						esc_url( $logo_url ),
						esc_attr__( 'FSELeaseHub logo', 'fse-lease-hub' )
					);
				}
			}
			// Header links to the configured board/group over HTTPS, not the homepage.
			$board_url = sprintf( 'https://fseleasehub.com/list/%d', absint( $a['fleet'] ) );
			$header_html = sprintf(
				'<div class="%s"><a class="flh-leases-brand" href="%s" target="_blank" rel="noopener">%s<span class="flh-leases-tagline">%s</span></a></div>',
				esc_attr( $header_class ),
				esc_url( $board_url ),
				$logo_html,
				esc_html__( 'My Aircraft Fleet', 'fse-lease-hub' )
			);
		}

		$footer_html = sprintf(
			'<div class="flh-leases-footer"><p class="flh-community">%s <a href="%s" target="_blank" rel="noopener">%s</a>. %s <a href="%s" target="_blank" rel="noopener">%s</a></p></div>',
			esc_html__( 'This plugin has been created by the community, Author:', 'fse-lease-hub' ),
			esc_url( 'https://github.com/RoscoHead' ),
			esc_html__( 'RoscoHead', 'fse-lease-hub' ),
			esc_html__( 'You can download the code and contribute new features at', 'fse-lease-hub' ),
			esc_url( 'https://github.com/RoscoHead/FSE_Lease_Hub' ),
			esc_html( 'https://github.com/RoscoHead/FSE_Lease_Hub' )
		);

		$frame_class = 'flh-leases-frame';
		$frame_extra = '';
		$frame_height = 550;
		if ( $is_auto ) {
			$frame_class .= ' flh-auto-frame';
			$frame_extra = sprintf(
				' data-flh-auto="1" data-flh-board="%d"',
				absint( $a['fleet'] )
			);
		} else {
			$frame_height = absint( $a['height'] );
		}

		ob_start();
?>
<div <?= $wrapper; ?>>
	<?= $header_html; ?>
	<iframe
		src="<?= esc_url($src); ?>"
		width="100%"
		height="<?= $frame_height; ?>"
		class="<?= esc_attr( $frame_class ); ?>"<?= $frame_extra; ?>
		loading="lazy"
		title="<?= esc_attr__( 'Aircraft for lease - FSELeaseHub', 'fse-lease-hub' ) ?>">
	</iframe>
	<?= $footer_html; ?>
</div>
<?php
		return ob_get_clean();
	}
}
