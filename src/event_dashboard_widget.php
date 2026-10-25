<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

add_action( 'wp_dashboard_setup', 'ev_kirchen_termine_add_dashboard_widget' );

function ev_kirchen_termine_add_dashboard_widget() {
	// the import is expensive (downloads images, deletes posts), so only admins may trigger it
	if ( ! current_user_can( 'manage_options' ) )
		return;

	wp_add_dashboard_widget(
		'ev_kirchen_termine_dashboard_widget', // widget ID
		__('Refresh events', 'ev-kirchen-termine'), // widget title
		'ev_kirchen_termine_dashboard_widget' // callback #1 to display it
	);
}
/*
 * Callback #1 function
 * Displays widget content
 */
function ev_kirchen_termine_dashboard_widget() {

	if ( isset( $_POST['refresh_ev_events'] ) && current_user_can( 'manage_options' ) ) {
		check_admin_referer( 'ev_kirchen_termine_refresh_events' );
		ev_kirchen_termine_import_events(true);
        esc_html_e('events refreshed', 'ev-kirchen-termine');
		echo "!</br>";
	}

    echo '<form method="post">';
    wp_nonce_field( 'ev_kirchen_termine_refresh_events' );
    echo '<input type="submit" name="refresh_ev_events" id="save-post" class="button button-primary" value="'.esc_attr__('Refresh events', 'ev-kirchen-termine').'">
        </form></br></br>';

}
