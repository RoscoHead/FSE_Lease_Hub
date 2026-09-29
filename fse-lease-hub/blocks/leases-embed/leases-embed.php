<?php
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
     die;
}

class FLH_Lease_Embed {

	// class instance
	static $instance;

	// what GUI control to use for "show" attribute
	private $use_checkbox_show = true;

	// class constructor
	public function __construct() {
		// Depends on WP version.
		if ( version_compare(get_bloginfo( 'version' ),'7.2') >= 0) {
			// Multi-select GUI bug fixed in 7.2 see https://github.com/WordPress/gutenberg/pull/83394
			//$this->use_checkbox_show = false;
		}

		// Register it
		$this->fseleasehub_register_block();
	}

	public function fseleasehub_register_block() {
		if ( ! function_exists( 'register_block_type' ) ) {
			// Block editor is not available.
			return;
		}

		// Setup attributes first the common ones (defaults inherit Settings > FSE Lease Hub)
		$global = class_exists( 'FLH_Embed_Renderer' ) ? FLH_Embed_Renderer::get_defaults() : [];
		$def_fleet  = isset( $global['fleet'] ) ? absint( $global['fleet'] ) : 0;
		$def_theme  = isset( $global['theme'] ) ? $global['theme'] : 'light';
		$def_layout = isset( $global['layout'] ) ? $global['layout'] : 'cards';
		$def_order  = isset( $global['order'] ) ? $global['order'] : 'newest';
		$def_limit  = isset( $global['limit'] ) ? absint( $global['limit'] ) : 20;
		$def_height_raw = isset( $global['height'] ) ? $global['height'] : 'auto';
		$def_height     = ( is_string( $def_height_raw ) && strtolower( trim( (string) $def_height_raw ) ) === 'auto' ) ? 'auto' : absint( $def_height_raw );
		if ( $def_height !== 'auto' && ( $def_height < 100 || $def_height > 3000 ) ) {
			$def_height = 'auto';
		}
		$def_show_header  = isset( $global['show_header'] ) ? (bool) $global['show_header'] : true;
		$def_header_shine = isset( $global['header_shine'] ) ? (bool) $global['header_shine'] : true;
		$def_header_align = isset( $global['header_align'] ) && in_array( $global['header_align'], [ 'left', 'center', 'right' ], true ) ? $global['header_align'] : 'center';
		$def_show_border  = isset( $global['show_border'] ) ? (bool) $global['show_border'] : true;
		$def_border_color = isset( $global['border_color'] ) ? (string) $global['border_color'] : '#c9a03f';
		$def_header_bg    = isset( $global['header_bg'] ) ? (string) $global['header_bg'] : '#101828';
		$def_header_color = isset( $global['header_color'] ) ? (string) $global['header_color'] : '#ffffff';
		$def_border_width = isset( $global['border_width'] ) ? absint( $global['border_width'] ) : 1;
		$def_show_links = isset( $global['show_links'] ) ? (bool) $global['show_links'] : false;
		$def_show_logo  = isset( $global['show_logo'] ) ? (bool) $global['show_logo'] : true;
		$def_show   = isset( $global['show_list'] ) ? $global['show_list'] : [ 'price', 'location', 'description', 'status', 'conditions', 'contact' ];
		$def_flag = function ( $key ) use ( $def_show ) {
			return in_array( $key, $def_show, true );
		};

		$attributes = [
			'fleet' => [
				'label'   => 'Board ID',
				'type'    => 'integer',
				'default' => $def_fleet,
			],
			'theme' => [
				'label'   => 'Theme',
				'type'    => 'string',
				'enum'    => ['light', 'dark'],
				'default' => $def_theme,
			],
			'layout' => [
				'label'   => 'Layout',
				'type'    => 'string',
				'enum'    => ['cards', 'list', 'compact'],
				'default' => $def_layout,
			],
			'order' => [
				'label'   => 'Sort order',
				'type'    => 'string',
				'enum'    => ['newest', 'price_asc', 'price_desc', 'registration'],
				'default' => $def_order,
			],
		];

		// Depending on version, use checkboxes or multi-select
		if ($this->use_checkbox_show === true) {
			$attributes += [
				'price' => [
					'label'   => 'Show price',
					'type'    => 'boolean',
					'default' => $def_flag('price'),
				],
				'location' => [
					'label'   => 'Show location',
					'type'    => 'boolean',
					'default' => $def_flag('location'),
				],
				'description' => [
					'label'   => 'Show description',
					'type'    => 'boolean',
					'default' => $def_flag('description'),
				],
				'status' => [
					'label'   => 'Show status',
					'type'    => 'boolean',
					'default' => $def_flag('status'),
				],
				'conditions' => [
					'label'   => 'Show conditions',
					'type'    => 'boolean',
					'default' => $def_flag('conditions'),
				],
				'contact' => [
					'label'   => 'Show contact',
					'type'    => 'boolean',
					'default' => $def_flag('contact'),
				],
				'showLinks' => [
					'label'   => 'Show links (View full listing)',
					'type'    => 'boolean',
					'default' => $def_show_links,
				],
				'showLogo' => [
					'label'   => 'Show header logo (assets/img/site-logo.png)',
					'type'    => 'boolean',
					'default' => $def_show_logo,
				],
			];
		}
		else {
			$attributes += [
				'show' => [
					'label'   => 'Information to show',
					'type'    => 'array',
					'items'   => [
						'type'  => 'string',
						'enum'  => ['price', 'location', 'description', 'status', 'conditions', 'contact'],
					],
					'default' => $def_show,
					'autoGenerateControl' => true,
				],
			];
		}

		// Header banner + outer border options.
		$attributes += [
			'showHeader' => [
				'label'   => 'Show header banner',
				'type'    => 'boolean',
				'default' => $def_show_header,
			],
			'headerShine' => [
				'label'   => 'Header shine effect',
				'type'    => 'boolean',
				'default' => $def_header_shine,
			],
			'headerAlign' => [
				'label'   => 'Header alignment',
				'type'    => 'string',
				'enum'    => [ 'left', 'center', 'right' ],
				'default' => $def_header_align,
			],
			'headerBg' => [
				'label'   => 'Header background (hex, e.g. #101828)',
				'type'    => 'string',
				'default' => $def_header_bg,
			],
			'headerColor' => [
				'label'   => 'Header text color (hex, e.g. #ffffff)',
				'type'    => 'string',
				'default' => $def_header_color,
			],
			'showBorder' => [
				'label'   => 'Show outer border',
				'type'    => 'boolean',
				'default' => $def_show_border,
			],
			'borderColor' => [
				'label'   => 'Border color (hex, e.g. #e3e8ef)',
				'type'    => 'string',
				'default' => $def_border_color,
			],
			'borderWidth' => [
				'label'   => 'Border width in px (0-12)',
				'type'    => 'integer',
				'default' => $def_border_width,
			],
		];

		// Now add common ones at the bottom
		$attributes += [
			'limit' => [
				'label'   => 'Max aircraft',
				'type'    => 'integer',
				'default' => $def_limit,
			],
			'height' => [
				'label'   => 'Height in px or "auto" (adapt to content)',
				'type'    => 'string',
				'default' => (string) $def_height,
			],
		];

		// Register the block
		$block = register_block_type( 'fse-lease-hub/leases', [
			'api_version' => 3,
			'title' => 'FSE Lease Hub Leases',
			'category' => 'embed',
			'icon' => 'airplane',
			'keywords' => [
				'FSE',
				'leases'
			],
			'render_callback' => [$this, 'fseleasehub_render_callback'],
			'style' => 'file:./leases-embed.css',
			'editor_style' => 'file:./leases-embed.css',
			'supports' => [
				'autoRegister' => true,
			],
			'attributes'      => $attributes,
		] );

		// For debugging
		if ( true === WP_DEBUG ) {
			if ($block === false){
				error_log( 'Error' );
			} else {
				error_log( $block->name . ' Registered' );
			}
		}
	}

	public function fseleasehub_render_callback($attributes, $content, $block) {
		if ( class_exists( 'FLH_Embed_Renderer' ) ) {
			return FLH_Embed_Renderer::render( is_array( $attributes ) ? $attributes : [], $block );
		}
		// Fallback legacy (should never run, renderer is always loaded).
		// Set variables from attributes to use in iframe
		$fleet = esc_html($attributes["fleet"]);
		$theme = esc_html($attributes["theme"]);
		$layout = esc_html($attributes["layout"]);
		$order = esc_html($attributes["order"]);
		$limit = esc_html($attributes["limit"]);
		$height = esc_html($attributes["height"]);

		// Depends on version either get checkboxes or multi-select
		$show = '';
		if ($this->use_checkbox_show === true) {
			$showlist = [];
			foreach (['price','location','description','status','conditions','contact'] as $attr) {
				if ($attributes[$attr] === true) {
					$showlist[] = $attr;
				}
			}
			$show = esc_html(implode(',', $showlist));
		}
		else {
			$show = esc_html(implode(',', $attributes["show"]));
		}

		// Output the HTML using the variables
		ob_start();
?>
<div>
<iframe
	src="https://fseleasehub.com/embed.php?board=<?= $fleet; ?>&theme=<?= $theme; ?>&layout=<?= $layout; ?>&sort=<?= $order; ?>&limit=<?= $limit; ?>&show=<?= $show; ?>"
	width="100%"
	height="<?= $height; ?>"
	style="border:1px solid #e3e8ef;border-radius:12px;"
	loading="lazy"
	title="Aircraft for lease - FSELeaseHub">
</iframe>
</div>
<?php
		return ob_get_clean();
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
