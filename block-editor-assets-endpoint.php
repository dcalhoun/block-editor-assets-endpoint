<?php
/*
 * Plugin Name: Block Editor Assets Endpoint
 */

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

	// Manually enqueue an admin-only script upon which Jetpack block's depend.
	// TODO: This brittle approach could break if Jetpack depdencies change
	// or a different third-party block requires different dependencies.
	// https://github.com/WordPress/wordpress-develop/blob/4115c99a784db8a787e2245300c83c9c8d336503/src/wp-includes/script-loader.php#L1408
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

	// Restore the original instances.
	$wp_styles  = $current_wp_styles;
	$wp_scripts = $current_wp_scripts;

	return array(
		'styles'  => $styles,
		'scripts' => $scripts,
	);
}

add_action("rest_api_init", function () {
    register_rest_route("beae/v1", "editor-assets", [
        "methods" => "GET",
        "callback" => "_beae_get_editor_assets",
        "permission_callback" => "__return_true",
    ]);
});

function register_custom_block_type() {
    $block_type = [
        "name" => "custom",
        "title" => "Custom Block",
        "editor_script" => "custom-block-script",
        "editor_style" => "custom-block-style",
    ];

    register_block_type("bar/foo", $block_type);
}

add_action("init", "register_custom_block_type");

function enqueue_custom_block_assets() {
    wp_enqueue_script(
        "custom-block-script",
        plugin_dir_url(__FILE__) . "custom-block.js",
    );
    wp_enqueue_style(
        "custom-block-style",
        plugin_dir_url(__FILE__) . "custom-block.css",
    );
}

add_action("enqueue_block_assets", "enqueue_custom_block_assets");
