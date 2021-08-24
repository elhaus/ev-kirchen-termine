<?php
/**
 * Plugin Name:       Ev. Kirchen Termine
 * Plugin URI:        https://github.com/elhaus
 * Description:       Zeige Veranstaltungen aus dem Veranstaltungskalendar der Ev. Kirchen
 * Version:           1.0.0
 * Author:            Jan Elhaus
 * Author URI:        https://github.com/elhaus
**/

function ev_kirchen_termin_load_plugin_css() {
    $plugin_url = plugin_dir_url( __FILE__ );

    wp_enqueue_style( 'events', $plugin_url . 'events.css' );
}

add_action( 'wp_enqueue_scripts', 'ev_kirchen_termin_load_plugin_css' );


include_once plugin_dir_path( __FILE__ ) . 'src/event_posttype.php';
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


include_once plugin_dir_path( __FILE__ ) . 'src/yt_live_check.php';
include_once plugin_dir_path( __FILE__ ) . 'src/show_page_text.php';
