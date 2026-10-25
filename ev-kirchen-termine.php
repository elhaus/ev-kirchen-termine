<?php
/**
 * Plugin Name:       Ev. Kirchen Termine
 * Plugin URI:        https://github.com/elhaus/ev-kirchen-termine
 * Description:       Shows events from the event calendar of the Protestant churches.
 * Version:           0.2.0
 * Requires at least: 6.3
 * Requires PHP:      7.4
 * Author:            Jan Elhaus
 * Author URI:        https://github.com/elhaus
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Text Domain:       ev-kirchen-termine
 * Domain Path:       /languages/
**/


if (!defined('ABSPATH')) die('No direct access allowed');

const EV_KIRCHEN_TERMINE_VERSION = '0.2.0';


function ev_kirchen_termin_load_plugin_css_js() {
    $plugin_url = plugin_dir_url( __FILE__ );

    wp_enqueue_style(
        'ev_kirchen_events_css',
        $plugin_url . 'public/events.css',
        array(),
        EV_KIRCHEN_TERMINE_VERSION
    );

}

add_action( 'wp_enqueue_scripts', 'ev_kirchen_termin_load_plugin_css_js' );


// Scripts are only registered here, the calendar shortcode enqueues them when needed.
// Registered on init because block themes render the content before wp_enqueue_scripts.
function ev_kirchen_termine_register_scripts() {
    $plugin_url = plugin_dir_url( __FILE__ );

    wp_register_script(
        'ev_kirchen_termine_fullcalendar_js',
        $plugin_url . 'public/fullcalendar/index.global.js',
        array(),
        EV_KIRCHEN_TERMINE_VERSION,
        array('in_footer'  => true)
    );
    wp_register_script(
        'ev_kirchen_termine_fullcalendar_local_js',
        $plugin_url . 'public/fullcalendar/de.global.js',
        array('ev_kirchen_termine_fullcalendar_js'),
        EV_KIRCHEN_TERMINE_VERSION,
        array('in_footer'  => true)
    );
    wp_register_script(
        'ev_kirchen_events_js',
        $plugin_url . 'public/events.js',
        array('ev_kirchen_termine_fullcalendar_local_js'),
        EV_KIRCHEN_TERMINE_VERSION,
        array('in_footer'  => true)
    );

}

add_action( 'init', 'ev_kirchen_termine_register_scripts' );


include_once plugin_dir_path( __FILE__ ) . 'src/event_posttype.php';
include_once plugin_dir_path( __FILE__ ) . 'src/event_share_button.php';
include_once plugin_dir_path( __FILE__ ) . 'src/event_page_meta_data.php';
include_once plugin_dir_path( __FILE__ ) . 'src/event_osm_map_image.php';
include_once plugin_dir_path( __FILE__ ) . 'src/event_import.php';
include_once plugin_dir_path( __FILE__ ) . 'src/event_dashboard_widget.php';

# activation hook to register cron job
register_activation_hook(__FILE__, 'ev_kirchen_termine_import_events_task_plugin_activate');
register_deactivation_hook(__FILE__, 'ev_kirchen_termine_import_events_task_plugin_deactivation');



add_filter('post_class', function($classes){

    global $post;
    $post_type = $post->post_type;
    if($post_type == "evkite_event")
        $classes[] = "single-evkite_events";

    return $classes;

});



add_filter('single_template', function($original) {

    global $post;
    $post_type = $post->post_type;
    if($post_type == "evkite_event")
        $template = locate_template(get_option("ev_kirchen_termine_event_template"));
    if(!empty($template))
        return $template;
    return $original;

});


include_once plugin_dir_path( __FILE__ ) . 'src/event_shortcode.php';


add_action( 'pre_get_posts', function ( $q )
{
    if (  !is_admin() // Only target front end queries
          && $q->is_main_query() // Only target the main query
          && $q->is_tag()        // Only target tag archives [comment out if not needed]
    ) {
        $post_types = $q->get( 'post_type', array());
        if(!is_array($post_types)) $post_types = array($post_types);
        $post_types[] = "evkite_event";
        $post_types[] = "post";
        $q->set( 'post_type', $post_types ); // Change 'custom_post_type' to YOUR Custom Post Type
                                                              // You can add multiple CPT's separated by comma's
    }
});


include_once plugin_dir_path( __FILE__ ) . 'src/event_gutenberg_block.php';
