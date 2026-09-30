<?php
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Global defaults: Settings > FSE Lease Hub.
 * Used when the block/shortcode omits attributes.
 */
class FLH_Settings {

	const OPTION = 'flh_defaults';
	const GROUP  = 'flh_options_group';

	public static function init() {
		add_action( 'admin_menu', [ __CLASS__, 'admin_menu' ] );
		add_action( 'admin_init', [ __CLASS__, 'admin_init' ] );
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'admin_assets' ] );
		add_action( 'admin_post_flh_reset_defaults', [ __CLASS__, 'handle_reset' ] );
	}

	/** Reset all global defaults to the plugin hard defaults. */
	public static function handle_reset() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to do this.', 'fse-lease-hub' ) );
		}
		check_admin_referer( 'flh_reset_defaults', 'flh_reset_nonce' );
		delete_option( self::OPTION );
		wp_safe_redirect(
			add_query_arg( 'flh-reset', '1', admin_url( 'options-general.php?page=fse-lease-hub' ) )
		);
		exit;
	}

	public static function admin_assets( $hook ) {
		if ( $hook !== 'settings_page_fse-lease-hub' ) {
			return;
		}
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_style(
			'flh-settings',
			plugins_url( 'assets/css/flh-settings.css', FLH_BASE_PATH . 'fse-lease-hub.php' ),
			[],
			defined( 'FLH_VERSION' ) ? FLH_VERSION : '1.10.5'
		);
		wp_enqueue_script(
			'flh-settings',
			plugins_url( 'assets/js/flh-settings.js', FLH_BASE_PATH . 'fse-lease-hub.php' ),
			[ 'wp-color-picker' ],
			defined( 'FLH_VERSION' ) ? FLH_VERSION : '1.10.5',
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
			'fleet'        => __( 'Board ID', 'fse-lease-hub' ),
			'theme'        => __( 'Theme', 'fse-lease-hub' ),
			'layout'       => __( 'Layout', 'fse-lease-hub' ),
			'order'        => __( 'Sort order', 'fse-lease-hub' ),
			'limit'        => __( 'Max aircraft (1-100)', 'fse-lease-hub' ),
			'height'       => __( 'Height', 'fse-lease-hub' ),
			'show'         => __( 'Information to show', 'fse-lease-hub' ),
			'show_links'   => __( 'Show links (View full listing)', 'fse-lease-hub' ),
			'show_logo'    => __( 'Show header logo', 'fse-lease-hub' ),
			'show_header'  => __( 'Show header banner', 'fse-lease-hub' ),
			'header_shine' => __( 'Header shine effect', 'fse-lease-hub' ),
			'header_align' => __( 'Header alignment', 'fse-lease-hub' ),
			'header_bg'    => __( 'Header background (hex)', 'fse-lease-hub' ),
			'header_color' => __( 'Header text color (hex)', 'fse-lease-hub' ),
			'show_border'  => __( 'Show outer border', 'fse-lease-hub' ),
			'border_color' => __( 'Border color (hex)', 'fse-lease-hub' ),
			'border_width' => __( 'Border width in px (0-12)', 'fse-lease-hub' ),
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
		foreach ( [ 'show_links', 'show_logo', 'show_header', 'header_shine', 'show_border' ] as $flag ) {
			if ( ! array_key_exists( $flag, $input ) ) {
				$input[ $flag ] = '0';
			}
		}
		// Height mode selector (Auto / Fixed) maps to a single height value.
		if ( isset( $input['height_mode'] ) ) {
			if ( $input['height_mode'] === 'auto' ) {
				$input['height'] = 'auto';
			}
			unset( $input['height_mode'] );
		}
		// Renderer already whitelists theme/layout/order, accepts height in
		// px or "auto", and sanitizes the hex colors and width.
		$clean = FLH_Embed_Renderer::sanitize( $input );
		return [
			'fleet'        => $clean['fleet'],
			'theme'        => $clean['theme'],
			'layout'       => $clean['layout'],
			'order'        => $clean['order'],
			'limit'        => $clean['limit'],
			'height'       => $clean['height'],
			'show'         => $clean['show'],
			'show_links'   => $clean['show_links'],
			'show_logo'    => $clean['show_logo'],
			'show_header'  => $clean['show_header'],
			'header_shine' => $clean['header_shine'],
			'header_align' => $clean['header_align'],
			'header_bg'    => $clean['header_bg'],
			'header_color' => $clean['header_color'],
			'show_border'  => $clean['show_border'],
			'border_color' => $clean['border_color'],
			'border_width' => $clean['border_width'],
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
				$is_auto = ( is_string( $value['height'] ?? null ) && strtolower( trim( (string) $value['height'] ) ) === 'auto' );
				$fixed   = $is_auto ? 550 : absint( $value['height'] ?? 550 );
				if ( $fixed < 100 || $fixed > 3000 ) {
					$fixed = 550;
				}
				printf(
					'<select name="%s[height_mode]" class="flh-height-mode"><option value="auto"%s>%s</option><option value="fixed"%s>%s</option></select> ',
					esc_attr( self::OPTION ),
					selected( $is_auto, true, false ),
					esc_html__( 'Auto (adapt to content)', 'fse-lease-hub' ),
					selected( $is_auto, false, false ),
					esc_html__( 'Fixed height in px', 'fse-lease-hub' )
				);
				printf(
					'<input type="number" min="100" max="3000" step="10" name="%s[height]" value="%d" class="small-text flh-height-fixed"%s />',
					esc_attr( self::OPTION ),
					$fixed,
					$is_auto ? ' disabled="disabled"' : ''
				);
				echo '<p class="description">' . esc_html__( 'Auto adapts to the content when the FSELeaseHub page announces its height; otherwise the fixed value is used.', 'fse-lease-hub' ) . '</p>';
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
			case 'show_links':
				printf(
					'<label><input type="checkbox" name="%s[show_links]" value="1" %s /> %s</label><p class="description">%s</p>',
					esc_attr( self::OPTION ),
					checked( ! empty( $value['show_links'] ), true, false ),
					esc_html__( 'Show the "View full listing" links on each card.', 'fse-lease-hub' ),
					esc_html__( 'When off, the embed is loaded with link=0.', 'fse-lease-hub' )
				);
				break;
			case 'show_logo':
				printf(
					'<label><input type="checkbox" name="%s[show_logo]" value="1" %s /> %s</label><p class="description">%s</p>',
					esc_attr( self::OPTION ),
					checked( ! empty( $value['show_logo'] ), true, false ),
					esc_html__( 'Show the site logo in the banner header.', 'fse-lease-hub' ),
					esc_html__( 'Uses assets/img/site-logo.png when the file exists; otherwise the default badge is shown.', 'fse-lease-hub' )
				);
				break;
			case 'show_header':
				printf(
					'<label><input type="checkbox" name="%s[show_header]" value="1" %s /> %s</label><p class="description">%s</p>',
					esc_attr( self::OPTION ),
					checked( ! empty( $value['show_header'] ), true, false ),
					esc_html__( 'Display the My Aircraft Fleet banner above the list.', 'fse-lease-hub' ),
					esc_html__( 'Banner shows the FSE logo with the fixed tagline.', 'fse-lease-hub' )
				);
				break;
			case 'header_shine':
				printf(
					'<label><input type="checkbox" name="%s[header_shine]" value="1" %s /> %s</label><p class="description">%s</p>',
					esc_attr( self::OPTION ),
					checked( ! empty( $value['header_shine'] ), true, false ),
					esc_html__( 'Enable the animated shine effect on the banner tagline.', 'fse-lease-hub' ),
					esc_html__( 'When off, the tagline is shown without transition.', 'fse-lease-hub' )
				);
				break;
			case 'header_align':
				printf( '<select name="%s[header_align]">', esc_attr( self::OPTION ) );
				foreach ( FLH_Embed_Renderer::ALLOWED_ALIGN as $opt ) {
					printf(
						'<option value="%s" %s>%s</option>',
						esc_attr( $opt ),
						selected( $value['header_align'] ?? 'center', $opt, false ),
						esc_html( ucfirst( $opt ) )
					);
				}
				echo '</select>';
				echo '<p class="description">' . esc_html__( 'Logo and tagline position inside the banner. Default is centered.', 'fse-lease-hub' ) . '</p>';
				break;
			case 'show_border':
				printf(
					'<label><input type="checkbox" name="%s[show_border]" value="1" %s /> %s</label>',
					esc_attr( self::OPTION ),
					checked( ! empty( $value['show_border'] ), true, false ),
					esc_html__( 'Display a border around the whole embed.', 'fse-lease-hub' )
				);
				break;
			case 'header_bg':
				printf(
					'<input type="text" name="%s[header_bg]" value="%s" class="flh-color-field" data-default-color="#101828" />',
					esc_attr( self::OPTION ),
					esc_attr( $value['header_bg'] ?? '#101828' )
				);
				echo '<p class="description">' . esc_html__( 'Banner background color, e.g. #101828.', 'fse-lease-hub' ) . '</p>';
				break;
			case 'header_color':
				printf(
					'<input type="text" name="%s[header_color]" value="%s" class="flh-color-field" data-default-color="#ffffff" />',
					esc_attr( self::OPTION ),
					esc_attr( $value['header_color'] ?? '#ffffff' )
				);
				echo '<p class="description">' . esc_html__( 'Banner text color, e.g. #ffffff.', 'fse-lease-hub' ) . '</p>';
				break;
			case 'border_color':
				printf(
					'<input type="text" name="%s[border_color]" value="%s" class="flh-color-field" data-default-color="#c9a03f" />',
					esc_attr( self::OPTION ),
					esc_attr( $value['border_color'] ?? '#c9a03f' )
				);
				echo '<p class="description">' . esc_html__( 'Pick a hexadecimal color, e.g. #2271b1.', 'fse-lease-hub' ) . '</p>';
				break;
			case 'border_width':
				printf(
					'<input type="number" min="0" max="12" step="1" name="%s[border_width]" value="%d" class="small-text" />',
					esc_attr( self::OPTION ),
					absint( $value['border_width'] ?? 1 )
				);
				echo '<p class="description">' . esc_html__( 'Border thickness in pixels applied to the whole embed.', 'fse-lease-hub' ) . '</p>';
				break;
		}
	}

	public static function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$ex_basic = '[fse_lease_hub]';
		$ex_full  = '[fse_lease_hub height="auto" show_header="1" header_bg="#101828" show_border="1" border_color="#c9a03f" border_width="1"]';
		$was_reset = isset( $_GET['flh-reset'] ) && sanitize_key( wp_unslash( $_GET['flh-reset'] ) ) === '1';
		?>
		<div class="wrap flh-wrap">
			<?php if ( $was_reset ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php echo esc_html__( 'Default settings restored.', 'fse-lease-hub' ); ?></p></div>
			<?php endif; ?>
			<div class="flh-header">
				<?php $flh_logo_url = class_exists( 'FLH_Embed_Renderer' ) ? FLH_Embed_Renderer::get_logo_url() : ''; ?>
				<?php if ( $flh_logo_url !== '' ) : ?>
					<img class="flh-logo-img" src="<?php echo esc_url( $flh_logo_url ); ?>" alt="FSELeaseHub logo" />
				<?php else : ?>
					<span class="flh-logo" aria-hidden="true">FSE</span>
				<?php endif; ?>
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
				<p><?php echo esc_html__( 'Paste this shortcode anywhere. Without attributes it shows the defaults saved above.', 'fse-lease-hub' ); ?></p>
				<div class="flh-code-row">
					<code><?php echo esc_html( $ex_basic ); ?></code>
					<button type="button" class="button flh-copy" data-copy="<?php echo esc_attr( $ex_basic ); ?>"><?php echo esc_html__( 'Copy', 'fse-lease-hub' ); ?></button>
				</div>
				<p><?php echo esc_html__( 'Override the header and border per shortcode:', 'fse-lease-hub' ); ?></p>
				<div class="flh-code-row">
					<code><?php echo esc_html( $ex_full ); ?></code>
					<button type="button" class="button flh-copy" data-copy="<?php echo esc_attr( $ex_full ); ?>"><?php echo esc_html__( 'Copy', 'fse-lease-hub' ); ?></button>
				</div>
				<p class="flh-hint"><?php echo esc_html__( 'Alias tag: [fse_lease_hub_leases]. Works in Classic editor, Gutenberg Shortcode block, Text widgets, Elementor/Divi shortcode widgets.', 'fse-lease-hub' ); ?></p>
			</div>
			<div class="flh-card flh-credit">
				<p>
					<?php echo esc_html__( 'Powered by', 'fse-lease-hub' ); ?>
					<a href="<?php echo esc_url( 'https://fseleasehub.com' ); ?>" target="_blank" rel="noopener">FSELeaseHub</a>
				</p>
				<p class="flh-hint">
					<?php echo esc_html__( 'Plugin created by', 'fse-lease-hub' ); ?>
					<a href="<?php echo esc_url( 'https://github.com/RoscoHead' ); ?>" target="_blank" rel="noopener">RoscoHead</a>
					&middot;
					<a href="<?php echo esc_url( 'https://github.com/RoscoHead/FSE_Lease_Hub' ); ?>" target="_blank" rel="noopener"><?php echo esc_html( 'https://github.com/RoscoHead/FSE_Lease_Hub' ); ?></a>
				</p>
			</div>
			<div class="flh-card">
				<h2><?php echo esc_html__( 'Restore default settings', 'fse-lease-hub' ); ?></h2>
				<p><?php echo esc_html__( 'Reset all options above to the plugin defaults (centered header, auto height, gold border).', 'fse-lease-hub' ); ?></p>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" onsubmit="return confirm('<?php echo esc_js( __( 'Reset all FSE Lease Hub settings to defaults?', 'fse-lease-hub' ) ); ?>');">
					<?php wp_nonce_field( 'flh_reset_defaults', 'flh_reset_nonce' ); ?>
					<input type="hidden" name="action" value="flh_reset_defaults" />
					<?php submit_button( __( 'Reset to defaults', 'fse-lease-hub' ), 'secondary', 'flh-reset', false ); ?>
				</form>
			</div>
		</div>
		<?php
	}
}
