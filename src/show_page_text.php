<?php


//////////////////
// Shortcode
// show_page_text
///////////////////

function show_page_text( $atts ) {
    $a = shortcode_atts( array (
        'page_id' => 222,
        'length' => 800,
		'do_shortcodes' => "true",
    ), $atts );

	$request_post = get_post($a['page_id']);

	$post_text = $request_post->post_content;

	if($a['do_shortcodes'] == "false")
		$post_text = preg_replace( '#\[.+\]#U', '', $post_text );

	$post_text = apply_filters('the_content', $post_text);
	if(intval($a['length']) !== -1) {
		$post_text = preg_replace("/[^ ]*$/", '', substr($post_text, 0, $a['length']));
		$post_text .= '<br> <div class="read_more_link">Lesen Sie weiter auf der Seite "<a href="/?p='.  $request_post->ID .'">' .$request_post->post_title .'</a>"</div>';
	}

	return $post_text;
}

add_shortcode( 'show_page_text', 'show_page_text' );
