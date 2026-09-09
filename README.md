# block-editor-assets-endpoint

> [!NOTE]
> This repository is now archived. Its explorations landed as a [REST API endpoint](https://github.com/Automattic/jetpack/blob/0cb1cc25de0354f060dd649aaec345be437c7fb9/projects/plugins/jetpack/_inc/lib/core-api/wpcom-endpoints/class-wpcom-rest-api-v2-endpoint-block-editor-assets.php) in the [Automattic/Jetpack](https://github.com/Automattic/jetpack) repository.

An exploration defining a new WordPress REST API endpoint returning all block editor assets needed for a block editor embedded within the mobile app. The approach is based on the [`_gutenberg_get_iframed_editor_assets_6_4`](https://github.com/WordPress/gutenberg/blob/ae20515b20d9c9e31408c4aecaffb3991c0fe31a/lib/compat/wordpress-6.4/script-loader.php#L8-L103) utility, but extended to include all assets used by the block editor.

