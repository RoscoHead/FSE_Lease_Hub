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
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'admin_assets' ] );
	}

	public static function admin_assets( $hook ) {
		if ( $hook !== 'settings_page_fse-lease-hub' ) {
			return;
		}
		wp_enqueue_style(
			'flh-settings',
			plugins_url( 'assets/css/flh-settings.css', FLH_BASE_PATH . 'fse-lease-hub.php' ),
			[],
			defined( 'FLH_VERSION' ) ? FLH_VERSION : '1.2.0'
		);
		wp_enqueue_script(
			'flh-settings',
			plugins_url( 'assets/js/flh-settings.js', FLH_BASE_PATH . 'fse-lease-hub.php' ),
			[],
			defined( 'FLH_VERSION' ) ? FLH_VERSION : '1.2.0',
			true
		);
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
				printf(
					'<p>%s <a href="%s" target="_blank" rel="noopener">FSELeaseHub</a> %s</p>',
					esc_html__( 'Configure the settings. Board ID is an identifier you can find in your', 'fse-lease-hub' ),
					esc_url( 'https://fseleasehub.com' ),
					esc_html__( 'Panel > Tools', 'fse-lease-hub' )
				);
			},
			'fse-lease-hub'
		);

		$fields = [
			'fleet'  => __( 'Board ID', 'fse-lease-hub' ),
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
		$ex_basic = '[fse_lease_hub]';
		?>
		<div class="wrap flh-wrap">
			<div class="flh-header">
				<span class="flh-logo" aria-hidden="true">FSE</span>
				<div>
					<h1><?php echo esc_html__( 'FSE Lease Hub', 'fse-lease-hub' ); ?></h1>
					<p class="flh-subtitle"><?php echo esc_html__( 'Global defaults for the block and the shortcode.', 'fse-lease-hub' ); ?></p>
				</div>
			</div>
			<div class="flh-card">
				<form method="post" action="options.php">
					<?php
					settings_fields( self::GROUP );
					do_settings_sections( 'fse-lease-hub' );
					submit_button();
					?>
				</form>
			</div>
			<div class="flh-card">
				<h2><?php echo esc_html__( 'Shortcode usage', 'fse-lease-hub' ); ?></h2>
				<p><?php echo esc_html__( 'Paste this shortcode anywhere. It always shows the defaults saved above.', 'fse-lease-hub' ); ?></p>
				<div class="flh-code-row">
					<code><?php echo esc_html( $ex_basic ); ?></code>
					<button type="button" class="button flh-copy" data-copy="<?php echo esc_attr( $ex_basic ); ?>"><?php echo esc_html__( 'Copy', 'fse-lease-hub' ); ?></button>
				</div>
				<p class="flh-hint"><?php echo esc_html__( 'Alias tag: [fse_lease_hub_leases]. Works in Classic editor, Gutenberg Shortcode block, Text widgets, Elementor/Divi shortcode widgets.', 'fse-lease-hub' ); ?></p>
			</div>
		</div>
		<?php
	}
}
