# block-editor-assets-endpoint

An exploration defining a new WordPress REST API endpoint returning all block editor assets needed for a block editor embedded within the mobile app. The approach is based on the [`_gutenberg_get_iframed_editor_assets_6_4`](https://github.com/WordPress/gutenberg/blob/ae20515b20d9c9e31408c4aecaffb3991c0fe31a/lib/compat/wordpress-6.4/script-loader.php#L8-L103) utility, but extended to include all assets used by the block editor.

## Testing Instructions

1. Clone this repository.
1. Run `npm start` within the cloned repository to start the development server.
1. Run `npx @wp-now/wp-now start` within the cloned repository to start a WordPress site.
1. Visit the following in your browser: `http://localhost:8881/?rest_route=/beae/v1/editor-assets`[^1].
1. Observe the block editor assets returned, including third-party block assets.

[^1]: The server port may differ depending on your local environment.
