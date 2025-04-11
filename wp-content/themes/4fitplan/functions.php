<?php
/**
 * Theme functions and definitions
 *
 * @package HelloElementorChild
 */

/**
 * Load child theme css and optional scripts
 *
 * @return void
 */
function hello_elementor_child_enqueue_scripts() {
	wp_enqueue_style(
		'hello-elementor-child-style',
		get_stylesheet_directory_uri() . '/style.css',
		[
			'hello-elementor-theme-style',
		],
		'1.0.0'
	);
}
add_action( 'wp_enqueue_scripts', 'hello_elementor_child_enqueue_scripts', 20 );

add_filter( 'frm_where_filter', 'filter_by_linked_id', 10, 2 );
function filter_by_linked_id($where, $args){
	if ( $args['display']->ID == 3 && $args['where_opt'] == 25 ) { //change 3 to the ID of your View and change 25 to the ID of your Dynamic field
		global $wpdb, $user_ID;
		$entry_id = $wpdb->get_col( $wpdb->prepare( "Select id from {$wpdb->prefix}frm_items where user_id=%d and form_id=%d", $user_ID, 5 ) );
		//Change 5 to the id of the form linked through your data from entries field
		if ( is_array( $entry_id ) && count( $entry_id ) == 1 ) {
			$entry_id = reset( $entry_id );
		}

		if ( ! $entry_id ) {
			$where = "meta_value=1 and meta_value=0 and fi.id='" . $args['where_opt'] . "'";
		} elseif ( is_array( $entry_id ) ) {
			$where = "meta_value in (" . implode( ',', $entry_id ) . ") and fi.id='" . absint( $args['where_opt'] ) . "'";
		} else {
			$where = '(meta_value = ' . (int) $entry_id . " OR meta_value LIKE '%\"" . (int) $entry_id . "\"%') and fi.id='" . $args['where_opt'] . "'";
		}
	}
	return $where;
}

add_action( 'init', 'blockusers_init' ); function blockusers_init() { if ( is_admin() && ! current_user_can( 'administrator' ) && ! ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) { wp_redirect( home_url() ); exit; } }
function my_files_only( $wp_query ) {
    if ( false !== strpos( $_SERVER[ 'REQUEST_URI' ], '/wp-admin/upload.php' ) ) {
if ($wp_query->query_vars['post_type']== 'attachment'){
            global $current_user;
            $wp_query->set( 'author', $current_user->id );
        }
    }
}
 
add_filter('parse_query', 'my_files_only' );