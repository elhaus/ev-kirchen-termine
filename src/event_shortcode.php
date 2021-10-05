<?php



function create_small_event_list( $atts) {

    $plugin_url = plugin_dir_url( dirname(__FILE__) );
    wp_enqueue_style( 'ev_kirchen_termine_event_widget_css', $plugin_url . 'event-widget.css' );

    $a = shortcode_atts( array (
       'channel' => "",
       'limit' => 5,
	   'highlight' => "none",
	   'event_ids' => "",
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
        'posts_per_page' => $a["limit"],
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

	if($a["highlight"] == "filter") {
		$args['meta_query']["highlight"] = array(
			'key'	 	=> '_ev_kirchen_termine_meta_key_highlight',
			'value'	  	=> '1',
			'compare' 	=> 'LIKE',
		);
	}


    $query = new WP_Query( $args );
    $events = $query->posts;

    $date_format = get_option( 'date_format' );
    $time_format = get_option( 'time_format' );

    $return = '<div class="small_event_list">';
    if(empty($events))
        $return .= "Es stehen aktuell keine Veranstaltungen an.";

    foreach ($events as $event) {

       $title = $event->post_title;
       $link = $event->guid;

       $post_meta = get_post_meta($event->ID);

       if(date("Y-m-d", strtotime($post_meta["_ev_kirchen_termine_meta_key_start"][0])) == date("Y-m-d", strtotime($post_meta["_ev_kirchen_termine_meta_key_end"][0]))) {
            $timespan = date_i18n($date_format, strtotime($post_meta["_ev_kirchen_termine_meta_key_start"][0]))." um ".date_i18n($time_format, strtotime($post_meta["_ev_kirchen_termine_meta_key_start"][0]))." - ".date_i18n($time_format, strtotime($post_meta["_ev_kirchen_termine_meta_key_end"][0]));
       } else {
           $timespan = date_i18n($date_format, strtotime($post_meta["_ev_kirchen_termine_meta_key_start"][0]))." um ".date_i18n($time_format, strtotime($post_meta["_ev_kirchen_termine_meta_key_start"][0]))." - ".date_i18n($date_format, strtotime($post_meta["_ev_kirchen_termine_meta_key_end"][0]))." um ".date_i18n($time_format, strtotime($post_meta["_ev_kirchen_termine_meta_key_end"][0]));
       }

       $day_in_week = date_i18n("D", strtotime($post_meta["_ev_kirchen_termine_meta_key_start"][0]));
       $day = date_i18n("j", strtotime($post_meta["_ev_kirchen_termine_meta_key_start"][0]));

       $highlight = "";
       if($post_meta["_ev_kirchen_termine_meta_key_highlight"][0])
            $highlight = " evkite-event-featured";


       $return .=
           '<div class="type-evkite_events evkite-clearfix'.$highlight.'">
               <div class="evkite-mini-calendar-event event--1 ">
                   <div class="list-date"> <span class="list-dayname"> '.$day_in_week.' </span> <span class="list-daynumber">'.$day.'</span>
                   </div>
                   <div class="list-info">
                       <h2 class="evkite-events-title"> <a href="'.$link.'" rel="bookmark">'.$title.'</a>
                       </h2>
                       <div class="evkite-events-duration"> '.$timespan.'
                       </div>
                   </div>
               </div>
           </div>';

   }

   $return .= "</div>";

   return $return;

}

add_shortcode( 'events_list', 'create_small_event_list' );


add_shortcode( 'events_calendar', 'ev_kirchen_termine_create_calendar' );


function ev_kirchen_termine_create_calendar($atts) {

    $a = shortcode_atts( array (
        'channel' => "",
    ), $atts );

    if(empty($a["channel"]) && !empty($_GET["channel"])) {
        $a["channel"] = $_GET["channel"];
    }

    $args = array(
        'post_type'  => 'event',
        'tag'        => $a["channel"],
        'posts_per_page' => 600,
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

    wp_enqueue_style( 'ev_kirchen_termine_fullcalendar_css', $plugin_url . 'fullcalendar/main.css' );
    wp_enqueue_script( 'ev_kirchen_termine_fullcalendar_js', $plugin_url . 'fullcalendar/main.js' );
    wp_enqueue_script( 'ev_kirchen_termine_fullcalendar_local_js', $plugin_url . 'fullcalendar/locales/de.js' );


    return '
        <br/>

        <script>

            function mobilecheck() {
              return (window.innerWidth < 765);
            };

          document.addEventListener("DOMContentLoaded", function() {

            var calendarEl = document.getElementById("calendar");

            var calendar = new FullCalendar.Calendar(calendarEl, {
              headerToolbar: {
                left: "prev,next today",
                center: "title",
                right: mobilecheck() ? "" : "dayGridMonth,timeGridWeek,timeGridDay,listMonth",
              },
              locale: "de",
              weekNumbers: true,
              height: "auto",
              initialView: mobilecheck() ? "listMonth" : "dayGridMonth",
              navLinks: true, // can click day/week names to navigate views
              dayMaxEvents: true, // allow "more" link when too many events
              eventColor: "#213c6b",
              events: '.json_encode(array_values($show_events)).'
            });

            calendar.render();

          });

        </script>
        <div id="events" class="">
              <div id="calendar"></div>
              <br/><br/>
        </div>';

}
