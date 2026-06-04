<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

function ev_kirchen_termine_smalleventlist_block_init()
{
  /**
   * Check if Gutemberg is active
   */
  if (!function_exists('register_block_type'))
    return;

  /**
   * Register our block editor script
   */
  wp_register_script(
      'small-event-list',
      plugins_url( 'event_gutenberg_block.js', __FILE__ ),
      array('wp-i18n', 'wp-blocks', 'wp-element', 'wp-components', 'wp-editor'),
      "0.1.2",
      array('in_footer'  => true)
  );

  // Get all IDs of the 'event' post type
  $event_ids = get_posts( array(
      'post_type'      => 'event',
      'posts_per_page' => -1,
      'fields'         => 'ids', // optimized query returning only IDs
  ) );

  $event_channels = array();

  // Fetch the terms (tags) attached to those specific event IDs
  if ( ! empty( $event_ids ) ) {
      $event_channels = wp_get_object_terms( $event_ids, 'post_tag', array(
          'fields'  => 'slugs',
          'orderby' => 'name',
          'order'   => 'ASC',
      ) );
      
      // De-duplicate the results
      $event_channels = array_unique( $event_channels, SORT_REGULAR );
  }

  $inline_js = 'var evkiteChannelSuggestions = ' . wp_json_encode( array( 'channels' => $event_channels ) ) . ';';
  
  // Das Inline-Skript direkt an dein Editor-Skript anheften
  wp_add_inline_script( 'small-event-list', $inline_js, 'before' );

  add_theme_support( 'editor-styles' );
  // Enqueue block editor stylesheet.
  $plugin_url = plugin_dir_url( dirname(__FILE__) );
  add_editor_style( $plugin_url . 'public/event-widget.css' );

  /**
   * Register our block, and explicitly define the attributes we accept
   */
  register_block_type( 'evkirchentermin/small-event-list', array(

    /** Define the attributes used in your block */

      'attributes'  => array(
          'channel' => array(
              'type'    => 'array',
              'default' => array(), // Leeres Array als Standard
              'items'   => array(
                  'type' => 'string'
              )
          ),
          'limit' => array(
              'type' => 'integer'
          ),
          'event_ids' => array(
              'type' => 'string'
          ),
          'show_location' => array(
              'type' => 'boolean'
          ),
          'show_organizer' => array(
              'type' => 'boolean'
          ),
          'show_more_link' => array(
              'type' => 'boolean'
          ),
		      'vid' => array(
              'type' => 'string'
          )
      ),

    /** Define the category for your block */
      'category' => 'widgets',

    /** The script name we gave in the wp_register_script() call */
      'editor_script'   => 'small-event-list',

    /** The callback called by the javascript file to render the block */
      'render_callback' => 'ev_kirchen_termine_smalleventlist_block_render',
  ) );

}
add_action( 'init', 'ev_kirchen_termine_smalleventlist_block_init' );

/**
 * Define the server side callback to render your block in the front end
 *
 * @param $attributes
 * @return string
 * @param array $attributes The attributes that were set on the block or shortcode.
 */
function ev_kirchen_termine_smalleventlist_block_render( $attributes )
{
  
if(is_array($attributes["channel"]))
    $attributes["channel"] = implode(",", $attributes["channel"]);

  $content = ev_kirchen_termine_create_small_event_list($attributes);

  return $content;
}
