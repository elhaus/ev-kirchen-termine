<?php


add_action( 'wp_dashboard_setup', 'ev_kirchen_termine_add_dashboard_widget' );

function ev_kirchen_termine_add_dashboard_widget() {
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

    // basic checks and save the widget settings here
	if( 'POST' == $_SERVER['REQUEST_METHOD']
	 && isset( $_POST['refresh_ev_events'] ) ) {
		ev_kirchen_termine_import_events(true);
        echo __('events refreshed', 'ev-kirchen-termine')."!</br>";
	}

    echo
        '<form method="post">
            <input type="submit" name="refresh_ev_events" id="save-post" class="button button-primary" value="'.__('Refresh events', 'ev-kirchen-termine').'">
        </form></br></br>';

}
