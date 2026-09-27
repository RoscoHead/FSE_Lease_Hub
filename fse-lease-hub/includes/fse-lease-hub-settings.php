<?php
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Global defaults: Ajustes > FSE Lease Hub.
 * Used when the block/shortcode omits attributes (fleet = 0).
 */
class FLH_Settings {

	const OPTION = 'flh_defaults';
	const GROUP  = 'flh_options_group';

	public static function init() {
		add_action( 'admin_menu', [ __CLASS__, 'admin_menu' ] );
		add_action( 'admin_init', [ __CLASS__, 'admin_init' ] );
	}

	public static function admin_menu() {
		add_options_page(
			__( 'FSE Lease Hub', 'fse-lease-hub' ),
			__( 'FSE Lease Hub', 'fse-lease-hub' ),
			'manage_options',
			'fse-lease-hub',
			[ __CLASS__, 'render_page' ]
		);
	}

	public static function admin_init() {
		register_setting(
			self::GROUP,
			self::OPTION,
			[ 'sanitize_callback' => [ __CLASS__, 'sanitize_option' ] ]
		);

		add_settings_section(
			'flh_main',
			__( 'Default embed values', 'fse-lease-hub' ),
			function () {
				echo '<p>' . esc_html__( 'These values are used when the block or shortcode does not set its own. Fleet is your board ID from the FSE Lease Hub tools page.', 'fse-lease-hub' ) . '</p>';
			},
			'fse-lease-hub'
		);

		$fields = [
			'fleet'  => __( 'Fleet (board ID)', 'fse-lease-hub' ),
			'theme'  => __( 'Theme', 'fse-lease-hub' ),
			'layout' => __( 'Layout', 'fse-lease-hub' ),
			'order'  => __( 'Sort order', 'fse-lease-hub' ),
			'limit'  => __( 'Max aircraft (1-100)', 'fse-lease-hub' ),
			'height' => __( 'Height in px (100-3000)', 'fse-lease-hub' ),
			'show'   => __( 'Information to show', 'fse-lease-hub' ),
		];
		foreach ( $fields as $key => $label ) {
			add_settings_field(
				'flh_' . $key,
				$label,
				[ __CLASS__, 'render_field' ],
				'fse-lease-hub',
				'flh_main',
				[ 'key' => $key ]
			);
		}
	}

	public static function get_saved() {
		$saved = get_option( self::OPTION, [] );
		return is_array( $saved ) ? $saved : [];
	}

	public static function sanitize_option( $input ) {
		if ( ! is_array( $input ) ) {
			$input = [];
		}
		// Checkboxes: unchecked boxes are not sent, so missing means "off" here
		// (unlike shortcode/block where missing means "keep default").
		if ( ! array_key_exists( 'show', $input ) ) {
			$list = [];
			foreach ( FLH_Embed_Renderer::ALLOWED_SHOW as $key ) {
				$raw = $input[ 'show_' . $key ] ?? $input[ $key ] ?? null;
				if ( ! empty( $raw ) && filter_var( $raw, FILTER_VALIDATE_BOOLEAN ) ) {
					$list[] = $key;
				}
			}
			$input['show'] = implode( ',', $list );
		}
		// Renderer already whitelists theme/layout/order and clamps limit/height.
		$clean = FLH_Embed_Renderer::sanitize( $input );
		return [
			'fleet'  => $clean['fleet'],
			'theme'  => $clean['theme'],
			'layout' => $clean['layout'],
			'order'  => $clean['order'],
			'limit'  => $clean['limit'],
			'height' => $clean['height'],
			'show'   => $clean['show'],
		];
	}

	public static function render_field( $args ) {
		$key   = $args['key'];
		$value = array_merge( FLH_Embed_Renderer::get_hard_defaults(), self::get_saved() );

		switch ( $key ) {
			case 'fleet':
				printf(
					'<input type="number" min="0" step="1" name="%s[fleet]" value="%d" class="small-text" />',
					esc_attr( self::OPTION ),
					absint( $value['fleet'] )
				);
				echo '<p class="description">' . esc_html__( 'Example: embed.php?board=123 -> put 123.', 'fse-lease-hub' ) . '</p>';
				break;
			case 'theme':
				printf( '<select name="%s[theme]">', esc_attr( self::OPTION ) );
				foreach ( FLH_Embed_Renderer::ALLOWED_THEMES as $opt ) {
					printf(
						'<option value="%s" %s>%s</option>',
						esc_attr( $opt ),
						selected( $value['theme'], $opt, false ),
						esc_html( $opt )
					);
				}
				echo '</select>';
				break;
			case 'layout':
				printf( '<select name="%s[layout]">', esc_attr( self::OPTION ) );
				foreach ( FLH_Embed_Renderer::ALLOWED_LAYOUTS as $opt ) {
					printf(
						'<option value="%s" %s>%s</option>',
						esc_attr( $opt ),
						selected( $value['layout'], $opt, false ),
						esc_html( $opt )
					);
				}
				echo '</select>';
				break;
			case 'order':
				printf( '<select name="%s[order]">', esc_attr( self::OPTION ) );
				foreach ( FLH_Embed_Renderer::ALLOWED_ORDERS as $opt ) {
					printf(
						'<option value="%s" %s>%s</option>',
						esc_attr( $opt ),
						selected( $value['order'], $opt, false ),
						esc_html( $opt )
					);
				}
				echo '</select>';
				break;
			case 'limit':
				printf(
					'<input type="number" min="1" max="100" step="1" name="%s[limit]" value="%d" class="small-text" />',
					esc_attr( self::OPTION ),
					absint( $value['limit'] )
				);
				break;
			case 'height':
				printf(
					'<input type="number" min="100" max="3000" step="10" name="%s[height]" value="%d" class="small-text" />',
					esc_attr( self::OPTION ),
					absint( $value['height'] )
				);
				break;
			case 'show':
				$active = explode( ',', (string) ( $value['show'] ?? '' ) );
				foreach ( FLH_Embed_Renderer::ALLOWED_SHOW as $opt ) {
					printf(
						'<label style="display:block;margin:2px 0;"><input type="checkbox" name="%s[show_%s]" value="1" %s /> %s</label>',
						esc_attr( self::OPTION ),
						esc_attr( $opt ),
						checked( in_array( $opt, $active, true ), true, false ),
						esc_html( ucfirst( $opt ) )
					);
				}
				echo '<p class="description">' . esc_html__( 'If the form sends unchecked boxes, they are removed on save.', 'fse-lease-hub' ) . '</p>';
				break;
		}
	}

	public static function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'FSE Lease Hub', 'fse-lease-hub' ); ?></h1>
			<form method="post" action="options.php">
				<?php
				settings_fields( self::GROUP );
				do_settings_sections( 'fse-lease-hub' );
				submit_button();
				?>
			</form>
			<hr />
			<h2><?php echo esc_html__( 'Shortcode usage', 'fse-lease-hub' ); ?></h2>
			<p><code>[fse_lease_hub]</code> — <?php echo esc_html__( 'uses everything saved above.', 'fse-lease-hub' ); ?></p>
			<p><code>[fse_lease_hub fleet="123" layout="list" theme="dark" limit="10" height="600" show="price,location,status"]</code></p>
			<p class="description"><?php echo esc_html__( 'Aliases: board=fleet, sort=order, show_x=1/0 per field. Alias tag: [fse_lease_hub_leases]. Works in Classic editor, Gutenberg Shortcode block, Text widgets, Elementor/Divi shortcode widgets.', 'fse-lease-hub' ); ?></p>
		</div>
		<?php
	}
}
