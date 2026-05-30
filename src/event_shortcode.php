<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

function create_small_event_list( $atts) {

    $plugin_url = plugin_dir_url( dirname(__FILE__) );
    wp_enqueue_style(
        'ev_kirchen_termine_event_widget_css',
        $plugin_url . 'public/event-widget.css',
        array(),
        "0.1.2"
    );

    $a = shortcode_atts( array (
       'channel' => "",
       'limit' => 5,
	   'highlight' => "none",
	   'event_ids' => "",
       'vid' => "",
       'show_location' => true,
       'show_organizer' => false,
       'show_more_link' => true,
    ), $atts );

    $args = array(
        'post_type'  => 'event',
        'meta_query' => array(
			'relation' => 'AND',
			'start' => array(
				'key' => '_ev_kirchen_termine_meta_key_start',
				'compare' => 'EXISTS',
			),
        ),
        'orderby'    => array(
            "start" => 'ASC'
        ),
        'posts_per_page' => (int) $a["limit"],
    );


	if(!empty($a["event_ids"])) {
		$args['meta_query']["event_id"] = array(
			'key'	 	=> '_ev_kirchen_termine_meta_key_id',
			'value'	  	=> explode(',', $a["event_ids"]),
			'compare' 	=> 'IN',
		);
	} else {
		$args['tag'] = $a["channel"];
        $args['meta_query']['end_date'] = array(
                'key'     => '_ev_kirchen_termine_meta_key_end',
                'value'   => date("Y-m-d H:i"),
                'compare' => '>',
                'type'    => 'DATETIME',
            );
	}

    if(!empty($a["vid"]))
		$args['meta_query']["vid"] = array(
			'key'	 	=> '_ev_kirchen_termine_meta_key_vid',
			'value'	  	=> explode(',', $a["vid"]),
			'compare' 	=> 'IN',
		);

	if($a["highlight"] == "filter") {
		$args['meta_query']["highlight"] = array(
			'key'	 	=> '_ev_kirchen_termine_meta_key_highlight',
			'value'	  	=> '1',
			'compare' 	=> '=',
		);
	}


    $query = new WP_Query( $args );
    $events = $query->posts;

    $date_format = get_option( 'date_format' );
    $time_format = get_option( 'time_format' );

    $return = '<div class="small_event_list">';
    if(empty($events))
        $return .= "Es stehen aktuell keine Veranstaltungen an.<br/>";

    foreach ($events as $event) {

       $title = $event->post_title;
       $link = $event->guid;

       $post_meta = get_post_meta($event->ID);

        if(date("Y-m-d", strtotime($post_meta["_ev_kirchen_termine_meta_key_start"][0])) == date("Y-m-d", strtotime($post_meta["_ev_kirchen_termine_meta_key_end"][0]))) {
            $timespan = wp_sprintf('%s um %s - %s Uhr',
                date_i18n($date_format, strtotime($post_meta["_ev_kirchen_termine_meta_key_start"][0])),
                date_i18n($time_format, strtotime($post_meta["_ev_kirchen_termine_meta_key_start"][0])),
                date_i18n($time_format, strtotime($post_meta["_ev_kirchen_termine_meta_key_end"][0]))
            );
        } else {
            $timespan = wp_sprintf('%s um %s - %s um %s',
                date_i18n($date_format, strtotime($post_meta["_ev_kirchen_termine_meta_key_start"][0])),
                date_i18n($time_format, strtotime($post_meta["_ev_kirchen_termine_meta_key_start"][0])),
                date_i18n($date_format, strtotime($post_meta["_ev_kirchen_termine_meta_key_end"][0])),
                date_i18n($time_format, strtotime($post_meta["_ev_kirchen_termine_meta_key_end"][0]))
            );
        }

       $day_in_week = date_i18n("D", strtotime($post_meta["_ev_kirchen_termine_meta_key_start"][0]));
       $day = date_i18n("j", strtotime($post_meta["_ev_kirchen_termine_meta_key_start"][0]));

       $highlight = "";
       if($post_meta["_ev_kirchen_termine_meta_key_highlight"][0])
            $highlight = " evkite-event-featured";

       $location = "";
       if($a["show_location"]) {
            $location = maybe_unserialize($post_meta["_ev_kirchen_termine_meta_key_location_json"][0])["name"];
            if(!empty($location))
                $location = '<div class="evkite-events-location">Ort: '.$location.'</div>';
       }


       $organizer = "";
       if($a["show_organizer"]) {
            $organizer = maybe_unserialize($post_meta["_ev_kirchen_termine_meta_key_user_data"][0])["name"];
            if(!empty($organizer))
                $organizer = '<div class="evkite-events-location">'.$organizer.'</div>';
       }

        $return .= wp_sprintf(
                '<div class="type-evkite_events evkite-clearfix %s">
                    <div class="evkite-mini-calendar-event event--1 ">
                        <div class="list-date">
                            <span class="list-dayname">%s</span>
                            <span class="list-daynumber">%s</span>
                        </div>
                        <div class="list-info">
                            <h2 class="evkite-events-title">
                                <a href="%s" rel="bookmark">%s</a>
                            </h2>
                            %s
                            %s
                            <div class="evkite-events-duration">%s</div>
                        </div>
                    </div>
                </div>',
                $highlight,
                $day_in_week,
                $day,
                $link,
                $title,
                $location,
                $organizer,
                $timespan
            );

   }

   if($a["show_more_link"]) {
        $return .= wp_sprintf(
                '<a href="%s/events/?channel=%s&vid=%s">mehr Veranstaltungen...</a>',
                get_site_url(),
                $a["channel"],
                $a["vid"]
            );
   }

   $return .= "</div>";

   return $return;

}

add_shortcode( 'events_list', 'create_small_event_list' );


add_shortcode( 'events_calendar', 'ev_kirchen_termine_create_calendar' );


function ev_kirchen_termine_create_calendar($atts) {

    $a = shortcode_atts( array (
        'channel' => "",
        'vid' => "",
    ), $atts );

    if(empty($a["channel"]) && !empty($_GET["channel"])) {
        $a["channel"] = sanitize_text_field( wp_unslash( $_GET["channel"] ) );
    }
    if(empty($a["vid"]) && !empty($_GET["vid"])) {
        $a["vid"] = sanitize_text_field( wp_unslash( $_GET["vid"] ) );
    }

    $args = array(
        'post_type'  => 'event',
        'tag'        => $a["channel"],
        'posts_per_page' => 600,
    );

    if(!empty($a["vid"]))
		$args['meta_query']["vid"] = array(
			'key'	 	=> '_ev_kirchen_termine_meta_key_vid',
			'value'	  	=> explode(',', $a["vid"]),
			'compare' 	=> 'IN',
		);

    $query = new WP_Query( $args );
    $events = $query->posts;

    $date_format = get_option( 'date_format' );
    $time_format = get_option( 'time_format' );

    $show_events = array();

    foreach ($events as $event) {

        $post_meta = get_post_meta($event->ID);

        $show_events[$event->ID] = array(
            "start" => $post_meta["_ev_kirchen_termine_meta_key_start"][0],
            "end" => $post_meta["_ev_kirchen_termine_meta_key_end"][0],
            "title" => $event->post_title,
            "url" => $event->guid
        );

        if($post_meta["_ev_kirchen_termine_meta_key_highlight"][0])
            $show_events[$event->ID]["color"] = "#c81d26";

    }


    $plugin_url = plugin_dir_url( dirname(__FILE__) );

    wp_enqueue_script(
        'ev_kirchen_termine_fullcalendar_js',
        $plugin_url . 'public/fullcalendar/index.global.js',
        array(),
        "0.1.2",
        array('in_footer'  => true)
    );
    wp_enqueue_script(
        'ev_kirchen_termine_fullcalendar_local_js',
        $plugin_url . 'public/fullcalendar/de.global.js',
        array(),
        "0.1.2",
        array('in_footer'  => true)
    );

    $data_json = wp_json_encode( array( 'events' => array_values($show_events) ) );
    $inline_script = "var evkiteCalendarData = {$data_json};";

    wp_add_inline_script('ev_kirchen_events_js', $inline_script, 'before');


    return '
        <br>
        <div id="events" class="">
              <div id="evkitecalendar"></div>
              <br>
              <br>
        </div>';

}
