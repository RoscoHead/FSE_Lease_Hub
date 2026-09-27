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

		// Setup attributes first the common ones (defaults inherit Ajustes > FSE Lease Hub)
		$global = function_exists( 'FLH_Embed_Renderer' ) ? FLH_Embed_Renderer::get_defaults() : [];
		$def_fleet  = isset( $global['fleet'] ) ? absint( $global['fleet'] ) : 0;
		$def_theme  = isset( $global['theme'] ) ? $global['theme'] : 'light';
		$def_layout = isset( $global['layout'] ) ? $global['layout'] : 'cards';
		$def_order  = isset( $global['order'] ) ? $global['order'] : 'newest';
		$def_limit  = isset( $global['limit'] ) ? absint( $global['limit'] ) : 20;
		$def_height = isset( $global['height'] ) ? absint( $global['height'] ) : 700;
		$def_show   = isset( $global['show_list'] ) ? $global['show_list'] : [ 'price', 'location', 'description', 'status', 'conditions', 'contact' ];
		$def_flag = function ( $key ) use ( $def_show ) {
			return in_array( $key, $def_show, true );
		};

		$attributes = [
			'fleet' => [
				'label'       => 'Fleet',
				'type'        => 'integer',
				'default'     => $def_fleet,
				'description' => __( 'Put your Board ID here to display your list. You can find this ID in your FSELeaseHub Panel > Tools, where you can copy and paste your board ID here.', 'fse-lease-hub' ),
				'help'        => __( 'Put your Board ID here to display your list. You can find this ID in your FSELeaseHub Panel > Tools, where you can copy and paste your board ID here.', 'fse-lease-hub' ),
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
			];
		}
		else {
			$attributes += [
				'show' => [
					'label'   => 'Information to show',
					'type'    => 'array',
					'items'   => [
						'type'  => 'string',
						'enum'  => ['Price', 'Location', 'Description', 'Status', 'Conditions', 'Contact'],
					],
					'default' => ['Price', 'Location', 'Description', 'Status', 'Conditions', 'Contact'],
					'autoGenerateControl' => true,
				],
			];
		}

		// Now add common ones at the bottom
		$attributes += [
			'limit' => [
				'label'   => 'Max aircraft',
				'type'    => 'integer',
				'default' => $def_limit,
			],
			'height' => [
				'label'   => 'Height',
				'type'    => 'integer',
				'default' => $def_height,
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
