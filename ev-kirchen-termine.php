<?php
/**
 * Plugin Name:       Ev. Kirchen Termine
 * Plugin URI:        https://github.com/elhaus/ev-kirchen-termine
 * Description:       Zeige Veranstaltungen aus dem Veranstaltungskalendar der Ev. Kirchen
 * Version:           0.1.2
 * Author:            Jan Elhaus
 * Author URI:        https://github.com/elhaus
 * License:           GPLv2
**/


if (!defined('ABSPATH')) die('No direct access allowed');/**
* Load plugin textdomain.
*/
function ev_kirchen_termine_load_textdomain() {
    load_plugin_textdomain( 'ev-kirchen-termine', false, basename( dirname( __FILE__ ) ) . '/languages/' );
}
add_action( 'init', 'ev_kirchen_termine_load_textdomain' );


function ev_kirchen_termin_load_plugin_css_js() {
    $plugin_url = plugin_dir_url( __FILE__ );

    wp_enqueue_style( 'ev_kirchen_events_css', $plugin_url . 'events.css' );

    wp_enqueue_script('jquery');
    wp_register_script( 'ev_kirchen_events_js', $plugin_url . 'events.js', array('jquery') , "1.0.1" );
    wp_localize_script( 'ev_kirchen_events_js', 'ev_kirchen_events_js_data', array(
        'ajaxurl' => $plugin_url,
    ));
    wp_enqueue_script( 'ev_kirchen_events_js' );

}

add_action( 'wp_enqueue_scripts', 'ev_kirchen_termin_load_plugin_css_js' );


include_once plugin_dir_path( __FILE__ ) . 'src/event_posttype.php';
include_once plugin_dir_path( __FILE__ ) . 'src/event_share_button.php';
include_once plugin_dir_path( __FILE__ ) . 'src/event_page_meta_data.php';
include_once plugin_dir_path( __FILE__ ) . 'src/event_import.php';
include_once plugin_dir_path( __FILE__ ) . 'src/event_dashboard_widget.php';




add_filter('post_class', function($classes){

    global $post;
    $post_type = $post->post_type;
    if($post_type == "event")
        $classes[] = "single-evkite_events";

    return $classes;

});



add_filter('single_template', function($original) {

    global $post;
    $post_type = $post->post_type;
    if($post_type == "event")
        $template = locate_template(get_option("ev_kirchen_termine_event_template"));
    if(!empty($template))
        return $template;
    return $original;

});




include_once plugin_dir_path( __FILE__ ) . 'src/event_shortcode.php';
include_once plugin_dir_path( __FILE__ ) . 'src/event_widget.php';
include_once plugin_dir_path( __FILE__ ) . 'src/event_archive.php';


add_action( 'pre_get_posts', function ( $q )
{
    if (  !is_admin() // Only target front end queries
          && $q->is_main_query() // Only target the main query
          && $q->is_tag()        // Only target tag archives [comment out if not needed]
    ) {
        $post_types = $q->get( 'post_type', array());
        if(!is_array($post_types)) $post_types = array($post_types);
        $post_types[] = "event";
        $post_types[] = "post";
        $q->set( 'post_type', $post_types ); // Change 'custom_post_type' to YOUR Custom Post Type
                                                              // You can add multiple CPT's separated by comma's
    }
});


include_once plugin_dir_path( __FILE__ ) . 'src/event_gutenberg_block.php';
