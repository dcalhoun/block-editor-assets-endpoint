<?php
/*
 * Plugin Name: Block Editor Assets Endpoint
 */

/**
 * Current screen retrieval may occur within third-party code, which may presume
 * it is running within the context of the admin. This endpoint is not run
 * within the admin context, so we need to ensure the function is available.
 */
if ( !function_exists( 'get_current_screen' ) ) {
	require_once ABSPATH . '/wp-admin/includes/screen.php';
}

/**
 * Collect the block editor assets that need to be loaded into the mobile app's embedded editor.
 *
 * @access private
 *
 * @global WP_Styles $wp_styles
 * @global WP_Scripts $wp_scripts
 *
 * @return array {
 *     The block editor assets.
 *
 *     @type string|false $styles  String containing the HTML for styles.
 *     @type string|false $scripts String containing the HTML for scripts.
 * }
 */
function _beae_get_editor_assets() {
	global $wp_styles, $wp_scripts;

	// Keep track of the styles and scripts instance to restore later.
	$current_wp_styles  = $wp_styles;
	$current_wp_scripts = $wp_scripts;

	// Create new instances to collect the assets.
	$wp_styles  = new WP_Styles();
	$wp_scripts = new WP_Scripts();

	// Register all currently registered styles and scripts. The actions that
	// follow enqueue assets, but don't necessarily register them.
	$wp_styles->registered  = isset($current_wp_styles->registered) ? $current_wp_styles->registered : array();
	$wp_scripts->registered = isset($current_wp_scripts->registered) ? $current_wp_scripts->registered : array();

	// We generally do not need reset styles for the iframed editor.
	// However, if it's a classic theme, margins will be added to every block,
	// which is reset specifically for list items, so classic themes rely on
	// these reset styles.
	$wp_styles->done =
		wp_theme_has_theme_json() ? array( 'wp-reset-editor-styles' ) : array();

	wp_enqueue_script( 'wp-polyfill' );
	// Enqueue the `editorStyle` handles for all core block, and dependencies.
	wp_enqueue_style( 'wp-edit-blocks' );

	if ( current_theme_supports( 'wp-block-styles' ) ) {
		wp_enqueue_style( 'wp-block-library-theme' );
	}

	// Enqueue frequent dependent, admin-only `postbox` asset
	$suffix = wp_scripts_get_suffix();
	wp_enqueue_script( 'postbox', "/wp-admin/js/postbox$suffix.js", array( 'jquery-ui-sortable', 'wp-a11y' ), false, 1 );

	// Enqueue both block and block editor assets.
	add_filter( 'should_load_block_editor_scripts_and_styles', '__return_true' );
	do_action( 'enqueue_block_assets' );
	do_action( 'enqueue_block_editor_assets' );
	remove_filter( 'should_load_block_editor_scripts_and_styles', '__return_true' );

	$block_registry = WP_Block_Type_Registry::get_instance();

	// Additionally, do enqueue `editorStyle` and `editorScript` assets for all
	// blocks, which contains editor-only styling for blocks (editor content).
	foreach ( $block_registry->get_all_registered() as $block_type ) {
		if ( isset( $block_type->editor_style_handles ) && is_array( $block_type->editor_style_handles ) ) {
			foreach ( $block_type->editor_style_handles as $style_handle ) {
				wp_enqueue_style( $style_handle );
			}
		}
    if ( isset( $block_type->editor_script_handles ) && is_array( $block_type->editor_script_handles ) ) {
			foreach ( $block_type->editor_script_handles as $script_handle ) {
				wp_enqueue_script( $script_handle );
			}
		}
	}

	/**
	 * Remove the deprecated `print_emoji_styles` handler.
	 * It avoids breaking style generation with a deprecation message.
	 */
	$has_emoji_styles = has_action( 'wp_print_styles', 'print_emoji_styles' );
	if ( $has_emoji_styles ) {
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
	}

	ob_start();
	wp_print_styles();
	$styles = ob_get_clean();

	if ( $has_emoji_styles ) {
		add_action( 'wp_print_styles', 'print_emoji_styles' );
	}

	ob_start();
	wp_print_head_scripts();
	wp_print_footer_scripts();
	$scripts = ob_get_clean();

	$styles = set_asset_protocol($styles);
	$scripts = set_asset_protocol($scripts);

	// Restore the original instances.
	$wp_styles  = $current_wp_styles;
	$wp_scripts = $current_wp_scripts;

	return array(
		'styles'  => $styles,
		'scripts' => $scripts,
	);
}

function _beae_get_editor_assets_permissions_check() {
	return current_user_can( 'edit_posts' );
}

add_action("rest_api_init", function () {
    register_rest_route("beae/v1", "editor-assets", [
        "methods" => "GET",
        "callback" => "_beae_get_editor_assets",
        "permission_callback" => "_beae_get_editor_assets_permissions_check",
    ]);
});

/**
 * Ensure all asset URLs include correct protocol.
 *
 * @param string $assets The HTML assets.
 *
 * @return string The modified HTML assets.
 */
function set_asset_protocol($assets) {
	$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
	return preg_replace('/(src|href=["\'])(\/\/)/', '$1' . $protocol, $assets);
}

/**
 * Registers the block using the metadata loaded from the `block.json` file.
 * Behind the scenes, it registers also all assets so they can be enqueued
 * through the block editor in the corresponding context.
 *
 * @see https://developer.wordpress.org/reference/functions/register_block_type/
 */
function create_block_custom_block_block_init() {
	register_block_type( __DIR__ . '/custom-block/build' );
}
add_action( 'init', 'create_block_custom_block_block_init' );
