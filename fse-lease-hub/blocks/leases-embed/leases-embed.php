<?php
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
     die;
}

function fseleasehub_register_block() {
	if ( ! function_exists( 'register_block_type' ) ) {
		// Block editor is not available.
		return;
	}
	$block = register_block_type( 'fse-lease-hub/leases', [
		'api_version' => 3,
		'title' => 'FSE Lease Hub Leases',
		'category' => 'embed',
		'icon' => 'airplane',
		'keywords' => [
			'FSE',
			'leases'
		],
		'render_callback' => 'fseleasehub_render_callback',
		'style' => 'file:./leases-embed.css',
		'supports' => [
			'autoRegister' => true,
		],
		'attributes'      => [
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
			/*
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
			*/
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
		],
	] );
	if ( true === WP_DEBUG ) {
		if ($block === false){
			error_log( 'Error' );
		} else {
			error_log( $block->name . ' Registered' );
		}
	}
}

function fseleasehub_render_callback($attributes, $content, $block) {
	$fleet = esc_html($attributes["fleet"]);
	$theme = esc_html($attributes["theme"]);
	$layout = esc_html($attributes["layout"]);
	$order = esc_html($attributes["order"]);
	$limit = esc_html($attributes["limit"]);
	$height = esc_html($attributes["height"]);
	$show = esc_html(implode(',', $attributes["show"]));
	/*
	$showlist = [];
	foreach (['price','location','description','status','conditions','contact'] as $attr) {
		if ($attributes[$attr] === true) {
			$showlist[] = $attr;
		}
	}
	$show = esc_html(implode(',', $showlist));
	*/
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
?>
