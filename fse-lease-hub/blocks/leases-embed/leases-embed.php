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

		// Setup attributes first the common ones
		$attributes = [
			'fleet' => [
				'label'   => 'Fleet',
				'type'    => 'integer',
				'default' => 0,
			],
			'theme' => [
				'label'   => 'Theme',
				'type'    => 'string',
				'enum'    => ['light', 'dark'],
				'default' => 'light',
			],
			'layout' => [
				'label'   => 'Layout',
				'type'    => 'string',
				'enum'    => ['cards', 'list', 'compact'],
				'default' => 'cards',
			],
			'order' => [
				'label'   => 'Sort order',
				'type'    => 'string',
				'enum'    => ['newest', 'price_asc', 'price_desc', 'registration'],
				'default' => 'newest',
			],
		];

		// Depending on version, use checkboxes or multi-select
		if ($this->use_checkbox_show === true) {
			$attributes += [
				'price' => [
					'label'   => 'Show price',
					'type'    => 'boolean',
					'default' => true,
				],
				'location' => [
					'label'   => 'Show location',
					'type'    => 'boolean',
					'default' => true,
				],
				'description' => [
					'label'   => 'Show description',
					'type'    => 'boolean',
					'default' => true,
				],
				'status' => [
					'label'   => 'Show status',
					'type'    => 'boolean',
					'default' => true,
				],
				'conditions' => [
					'label'   => 'Show conditions',
					'type'    => 'boolean',
					'default' => true,
				],
				'contact' => [
					'label'   => 'Show contact',
					'type'    => 'boolean',
					'default' => true,
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
				'default' => 20,
			],
			'height' => [
				'label'   => 'Height',
				'type'    => 'integer',
				'default' => 700,
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
