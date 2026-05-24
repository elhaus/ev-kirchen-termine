<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

function evkirchentermin_smalleventlist_block_init()
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

  add_theme_support( 'editor-styles' );
  // Enqueue block editor stylesheet.
  $plugin_url = plugin_dir_url( dirname(__FILE__) );
  add_editor_style( $plugin_url . 'event-widget.css' );

  /**
   * Register our block, and explicitly define the attributes we accept
   */
  register_block_type( 'evkirchentermin/small-event-list', array(

    /** Define the attributes used in your block */

      'attributes'  => array(
          'channel' => array(
              'type' => 'string'
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
      'render_callback' => 'evkirchentermin_smalleventlist_block_render',
  ) );

}
add_action( 'init', 'evkirchentermin_smalleventlist_block_init' );

/**
 * Define the server side callback to render your block in the front end
 *
 * @param $attributes
 * @return string
 * @param array $attributes The attributes that were set on the block or shortcode.
 */
function evkirchentermin_smalleventlist_block_render( $attributes )
{
  /** @var  $is_in_edit_mode  Check if we are in the editor */
  $is_in_edit_mode = strrpos($_SERVER['REQUEST_URI'], "context=edit");


  /** If we are in the editor */
  $content = create_small_event_list($attributes);

  return $content;
}
