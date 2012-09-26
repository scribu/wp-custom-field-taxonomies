<?php

class CFT_Admin {

	protected static $errors = array();

	function init() {
		add_action( 'admin_notices', array( __CLASS__, 'handler' ) );
		add_action( 'tool_box', array( __CLASS__, 'display' ) );

		add_filter( 'plugin_action_links_' . CFT_PLUGIN_BASENAME, array( __CLASS__, '_action_link' ) );
	}

	function handler() {
		if ( !isset( $_POST['cf_to_term'] ) )
			return;

		$cf_key = stripslashes( $_POST['cf_key'] );
		$taxonomy = stripslashes( $_POST['taxonomy'] );

		if ( empty( $cf_key ) ) {
			echo html( 'div class="error"', html( 'p', 'Invalid custom field key taxonomy.' ) );
			return;
		}

		if ( !taxonomy_exists( $taxonomy ) ) {
			echo html( 'div class="error"', html( 'p', 'Invalid taxonomy.' ) );
			return;
		}

		$r = self::convert( $cf_key, $taxonomy );

		echo '<div class="updated">';

		echo
		html( 'p',
			sprintf( _n(
				'Converted <em>%d</em> custom field.',
				'Converted <em>%d</em> custom fields.',
				$r,
				'custom-field-taxonomies'
			), number_format_i18n( $r ) )
		);

		if ( !empty( self::$errors ) ) {
			echo html( 'p', __( 'There were some errors:', 'custom-field-taxonomies' ) );
			foreach ( self::$errors as $cf_value => $message )
				echo html( 'p', $cf_value . ': ' . $message );
		}
		echo '</div>';
	}

	function convert( $cf_key, $taxonomy ) {
		global $wpdb;

		$rows = $wpdb->get_results( $wpdb->prepare( "
			SELECT post_id, GROUP_CONCAT( meta_value ) as terms
			FROM $wpdb->postmeta
			WHERE meta_key = %s
			GROUP BY post_id
		", $cf_key ) );

		foreach ( $rows as $row ) {
			$post_id = $row->post_id;
			$terms = explode( ',', $row->terms );
			$terms = (array) apply_filters( 'cft_terms_pre', $terms, $post_id );

			// Convert raw values to term ids
			foreach ( $terms as $i => $term_name ) {
				$term_name = trim( $term_name );

				if ( empty( $term_name ) )
					continue;

				$term = get_term_by( 'name', $term_name, $taxonomy, ARRAY_A );

				if ( !$term ) {
					$term = wp_insert_term( $term_name, $taxonomy );

					if ( is_wp_error( $term ) ) {
						self::$errors[$term_name] = $term->get_error_message();
						continue;
					}
				}

				$terms[ $i ] = (int) $term['term_id'];
			}

			wp_set_post_terms( $row->post_id, $terms, $taxonomy, true );
		}

		return $wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->postmeta WHERE meta_key = %s", $cf_key ) );
	}

	function display() {
		$cf_input = scbForms::input( array(
			'type' => 'select',
			'name' => 'cf_key',
			'values' => self::get_cf_key_list()
		) );

		$tax_input = scbForms::input( array(
			'type' => 'select',
			'name' => 'taxonomy',
			'values' => self::get_tax_list()
		) );

		$go_input = scbForms::input( array(
			'type' => 'submit',
			'name' => 'cf_to_term',
			'value' => __( 'Go', 'custom-field-taxonomies' ),
			'extra' => sprintf( 'class="button" onclick="return confirm( \'%s\' )"', __( 'Are you sure? This operation can not be undone.', 'custom-field-taxonomies' ) )
		) );

		echo
		html( 'div id="cf-to-tax" class="tool-box"',
			 html( 'h3 class="title"', __( 'Custom Field Taxonomies', 'custom-field-taxonomies', 'custom-field-taxonomies' ) )
			.scbForms::form_wrap( html( 'p',
				 sprintf( __( 'Convert %s custom fields to terms in the %s taxonomy.', 'custom-field-taxonomies' ), $cf_input, $tax_input )
				. ' ' . $go_input
			), false )
		);
	}

	protected function get_cf_key_list() {
		global $wpdb;

		return $wpdb->get_col( "
			SELECT meta_key
			FROM $wpdb->postmeta
			GROUP BY meta_key
			HAVING meta_key NOT LIKE '\_%'
			ORDER BY meta_key ASC
		" );
	}

	protected function get_tax_list() {
		$taxonomies = array();
		foreach ( get_taxonomies( array( 'show_ui' => true ), 'objects' ) as $tax_name => $tax_obj )
			$taxonomies[$tax_name] = $tax_obj->label ? $tax_obj->label : $tax_name;

		return $taxonomies;
	}

	function _action_link( $links ) {
		$links[] = html_link( admin_url( 'tools.php#cf-to-tax' ), __( 'Use', 'custom-field-taxonomies' ) );

		return $links;
	}
}

