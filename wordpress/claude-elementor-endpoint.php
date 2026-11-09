<?php
/**
 * Claude – čítanie a zápis Elementor layoutu cez REST API.
 *
 * Inštalácia: plugin Code Snippets → Add New → vložiť kód BEZ prvého riadku `<?php`
 * → „Run snippet everywhere“ → Save and Activate.
 *
 * GET  /wp-json/claude/v1/elementor/<id>   → { id, edit_mode, data }
 * POST /wp-json/claude/v1/elementor/<id>   body JSON: { "data": "<Elementor JSON ako string>" }
 *
 * - Prístup len pre prihláseného používateľa, ktorý smie upravovať daný príspevok (Application Password).
 * - Pred zápisom uloží predošlú verziu do meta `_elementor_data_claude_backup` (1 krok späť).
 * - Po zápise vyčistí Elementor CSS cache, aby sa zmena prejavila.
 */
add_action( 'rest_api_init', function () {
	$can_edit = function ( $request ) {
		return current_user_can( 'edit_post', (int) $request['id'] );
	};

	register_rest_route( 'claude/v1', '/elementor/(?P<id>\d+)', array(
		array(
			'methods'             => 'GET',
			'permission_callback' => $can_edit,
			'callback'            => function ( $request ) {
				$id = (int) $request['id'];
				return array(
					'id'        => $id,
					'edit_mode' => get_post_meta( $id, '_elementor_edit_mode', true ),
					'data'      => get_post_meta( $id, '_elementor_data', true ),
				);
			},
		),
		array(
			'methods'             => 'POST',
			'permission_callback' => $can_edit,
			'callback'            => function ( $request ) {
				$id   = (int) $request['id'];
				$data = $request->get_param( 'data' );

				if ( ! is_string( $data ) || ! is_array( json_decode( $data, true ) ) ) {
					return new WP_Error( 'bad_json', 'data musí byť platný Elementor JSON (string)', array( 'status' => 400 ) );
				}

				$old = get_post_meta( $id, '_elementor_data', true );
				update_post_meta( $id, '_elementor_data_claude_backup', wp_slash( $old ) );
				update_post_meta( $id, '_elementor_data', wp_slash( $data ) );
				update_post_meta( $id, '_elementor_edit_mode', 'builder' );

				if ( class_exists( '\Elementor\Plugin' ) ) {
					\Elementor\Plugin::$instance->files_manager->clear_cache();
				}

				return array( 'id' => $id, 'saved' => true );
			},
		),
	) );
} );
