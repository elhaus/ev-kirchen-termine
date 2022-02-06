<?php


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
      array( 'wp-blocks', 'wp-element', 'wp-components', 'wp-editor' )
  );

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
              'type' => 'string'
          ),
          'event_ids' => array(
              'type' => 'string'
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


  if(!isset($attributes['channel']))
	$attributes['channel'] = "";
  if(!isset($attributes['limit']))
	$attributes['limit'] = "";
  if(!isset($attributes['event_ids']))
	$attributes['event_ids'] = "";
  if(!isset($attributes['vid']))
	$attributes['vid'] = "";

  if(is_array($attributes['vid']))
	$attributes['vid'] = implode(",", $attributes['vid']);


  /** If we are in the editor */
  if ($is_in_edit_mode) {

    /** If the specific attribute exist (it's not new) */
    $content = '<small>Editing: [events_list';
    $content .= ' channel="'.$attributes['channel'].'"';
    $content .= ' limit='.$attributes['limit'].'';
    $content .= ' event_ids="'.$attributes['event_ids'].'"';
	$content .= ' vid="'.$attributes['vid'].'"';
    $content .= ' ]';
	$content .= '</small><br/>'.create_small_event_list($attributes);

    /** If we are in the front end */
  } else {
    $content = '[events_list';
    $content .= ' channel="'.$attributes['channel'].'"';
    $content .= ' limit='.$attributes['limit'].'';
    $content .= ' event_ids="'.$attributes['event_ids'].'"';
	$content .= ' vid="'.$attributes['vid'].'"';
    $content .= ' ]';
  }
  return $content;
}
