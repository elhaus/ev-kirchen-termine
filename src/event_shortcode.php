<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

function ev_kirchen_termine_create_small_event_list( $atts) {

    $plugin_url = plugin_dir_url( dirname(__FILE__) );
    wp_enqueue_style(
        'ev_kirchen_termine_event_widget_css',
        $plugin_url . 'public/event-widget.css',
        array(),
        EV_KIRCHEN_TERMINE_VERSION
    );

    $a = shortcode_atts( array (
       'channel' => "",
       'limit' => 5,
	   'highlight' => "none",
	   'event_ids' => "",
       'vid' => "",
       'show_location' => true,
       'show_organizer' => false,
       'show_contact_person' => false,
       'show_more_link' => true,
       'title_level' => 2,
    ), $atts );

    // shortcode attributes are strings, e.g. show_more_link="false"
    foreach (array('show_location', 'show_organizer', 'show_contact_person', 'show_more_link') as $bool_attribute)
        $a[$bool_attribute] = filter_var($a[$bool_attribute], FILTER_VALIDATE_BOOLEAN);

    // heading level of the event titles, so the list fits into the heading structure of the page
    $title_tag = 'h' . min(6, max(2, (int) $a["title_level"]));

    $args = array(
        'post_type'  => 'evkite_event',
        // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- needed to sort and filter by event date, only on the plugin's own post type
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
		$args['tag'] = sanitize_text_field($a["channel"]);
        $args['meta_query']['end_date'] = array(
                'key'     => '_ev_kirchen_termine_meta_key_end',
                'value'   => wp_date("Y-m-d H:i"),
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
        $return .= esc_html__( 'There are currently no upcoming events.', 'ev-kirchen-termine' ) . '<br/>';

    foreach ($events as $event) {

        $title = $event->post_title;
        $link  = esc_url( get_permalink( $event->ID ) );

        $post_meta = get_post_meta($event->ID);

        $meta_start = isset( $post_meta["_ev_kirchen_termine_meta_key_start"][0] ) ? $post_meta["_ev_kirchen_termine_meta_key_start"][0] : '';
        $meta_end   = isset( $post_meta["_ev_kirchen_termine_meta_key_end"][0] ) ? $post_meta["_ev_kirchen_termine_meta_key_end"][0] : '';

        if ( wp_date( "Y-m-d", strtotime( $meta_start ) ) === wp_date( "Y-m-d", strtotime( $meta_end ) ) ) {
            /* translators: 1: date, 2: start time, 3: end time */
            $timespan = wp_sprintf( __( '%1$s at %2$s - %3$s', 'ev-kirchen-termine' ),
                wp_date( $date_format, strtotime( $meta_start ) ),
                wp_date( $time_format, strtotime( $meta_start ) ),
                wp_date( $time_format, strtotime( $meta_end ) )
            );
        } else {
            /* translators: 1: start date, 2: start time, 3: end date, 4: end time */
            $timespan = wp_sprintf( __( '%1$s at %2$s - %3$s at %4$s', 'ev-kirchen-termine' ),
                wp_date( $date_format, strtotime( $meta_start ) ),
                wp_date( $time_format, strtotime( $meta_start ) ),
                wp_date( $date_format, strtotime( $meta_end ) ),
                wp_date( $time_format, strtotime( $meta_end ) )
            );
        }

        $day_in_week = esc_html( wp_date( "D", strtotime( $meta_start ) ) );
        $day         = esc_html( wp_date( "j", strtotime( $meta_start ) ) );

        $highlight = "";
        if($post_meta["_ev_kirchen_termine_meta_key_highlight"][0])
            $highlight = sanitize_html_class( 'evkite-event-featured' );

        $location = "";
        if ( $a["show_location"] && ! empty( $post_meta["_ev_kirchen_termine_meta_key_location_json"][0] ) ) {
            $location_data = maybe_unserialize( $post_meta["_ev_kirchen_termine_meta_key_location_json"][0] );
            if ( ! empty( $location_data["name"] ) ) {
                $location = '<div class="evkite-events-location">' . esc_html( sprintf( /* translators: %s: location name */ __( 'Location: %s', 'ev-kirchen-termine' ), $location_data["name"] ) ) . '</div>';
            }
        }

        $organizer = "";
        if ( $a["show_organizer"] && ! empty( $post_meta["_ev_kirchen_termine_meta_key_user_data"][0] ) ) {
            $user_data = maybe_unserialize( $post_meta["_ev_kirchen_termine_meta_key_user_data"][0] );
            if ( ! empty( $user_data["name"] ) ) {
                $organizer = '<div class="evkite-events-location">' . esc_html( $user_data["name"] ) . '</div>';
            }
        }

        $contact_person = "";
        if ( $a["show_contact_person"] && ! empty( $post_meta["_ev_kirchen_termine_meta_key_person_data"][0] ) ) {
            $person_data = maybe_unserialize( $post_meta["_ev_kirchen_termine_meta_key_person_data"][0] );
            if ( ! empty( $person_data["name"] ) ) {
                $contact_person = '<div class="evkite-events-location">' . esc_html( sprintf( /* translators: %s: name of the contact person */ __( 'Contact person: %s', 'ev-kirchen-termine' ), $person_data["name"] ) ) . '</div>';
            }
        }

        $return .= wp_sprintf(
                '<div class="type-evkite_events evkite-clearfix %s">
                    <div class="evkite-mini-calendar-event event--1 ">
                        <div class="list-date" aria-hidden="true">
                            <span class="list-dayname">%s</span>
                            <span class="list-daynumber">%s</span>
                        </div>
                        <div class="list-info">
                            <%s class="evkite-events-title">
                                <a href="%s" rel="bookmark">%s</a>
                            </%s>
                            %s
                            %s
                            %s
                            <div class="evkite-events-duration">%s</div>
                        </div>
                    </div>
                </div>',
                $highlight,
                $day_in_week,
                $day,
                $title_tag,
                $link,
                esc_html( $title ),
                $title_tag,
                $location,
                $organizer,
                $contact_person,
                esc_html( $timespan )
            );

   }

   if ( $a["show_more_link"] ) {
        $more_link_url = esc_url( sprintf(
            '%s/events/?channel=%s&vid=%s',
            get_site_url(),
            urlencode( $a["channel"] ),
            urlencode( $a["vid"] )
        ) );

        $return .= sprintf( '<a href="%s">%s</a>', $more_link_url, esc_html__( 'more events...', 'ev-kirchen-termine' ) );
    }

   $return .= "</div>";

   return $return;

}

add_shortcode( 'evkite_events_list', 'ev_kirchen_termine_create_small_event_list' );


add_shortcode( 'evkite_events_calendar', 'ev_kirchen_termine_create_calendar' );


function ev_kirchen_termine_create_calendar($atts) {

    $a = shortcode_atts( array (
        'channel' => "",
        'vid' => "",
    ), $atts );

    // phpcs:disable WordPress.Security.NonceVerification.Recommended -- public, read-only filter from links like /events/?channel=...
    if(empty($a["channel"]) && !empty($_GET["channel"])) {
        $a["channel"] = sanitize_text_field( wp_unslash( $_GET["channel"] ) );
    }
    if(empty($a["vid"]) && !empty($_GET["vid"])) {
        $a["vid"] = sanitize_text_field( wp_unslash( $_GET["vid"] ) );
    }
    // phpcs:enable WordPress.Security.NonceVerification.Recommended

    $args = array(
        'post_type'  => 'evkite_event',
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
            "start" => wp_date("c", strtotime($post_meta["_ev_kirchen_termine_meta_key_start"][0])),
            "end" => wp_date("c", strtotime($post_meta["_ev_kirchen_termine_meta_key_end"][0])),
            "title" => $event->post_title,
            "url" => $event->guid
        );

        if($post_meta["_ev_kirchen_termine_meta_key_highlight"][0])
            $show_events[$event->ID]["color"] = "#c81d26";

    }


    wp_enqueue_script('ev_kirchen_events_js');

    $data_json = wp_json_encode( array( 'events' => array_values($show_events), 'locale' => strtolower( substr( determine_locale(), 0, 2 ) ) ) );
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
