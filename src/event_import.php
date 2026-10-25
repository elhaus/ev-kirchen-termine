<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 *
 * Cron
 *
**/

function ev_kirchen_termine_import_events_task_plugin_activate() {
    if (!wp_next_scheduled('ev_kirchen_termine_import_events_task')) {
        wp_schedule_event(time(), 'hourly', 'ev_kirchen_termine_import_events_task');
    }

    // Create a archive page if not exsting
    $check_page_exist = get_page_by_path('events', 'OBJECT', 'page');
    // Check if the page already exists
    if(empty($check_page_exist)) {
        $page_id = wp_insert_post(
            array(
            'comment_status' => 'close',
            'ping_status'    => 'close',
            'post_title'     => __('Events', 'ev-kirchen-termine'),
            'post_name'      => strtolower(trim('events')),
            'post_status'    => 'publish',
            'post_content'   => '[evkite_events_calendar]',
            'post_type'      => 'page',
            )
        );
    }

}

function ev_kirchen_termine_import_events_task_plugin_deactivation() {
    wp_clear_scheduled_hook('ev_kirchen_termine_import_events_task');
}

add_action('ev_kirchen_termine_import_events_task', 'ev_kirchen_termine_import_events');


/**
 *
 * Import
 *
**/

/**
 * Loads the events from the given XML url and returns them as a list of arrays.
 * Returns an empty array if the request fails or no events are available.
 */
function ev_kirchen_termine_fetch_events($url) {

    //read xml file from url in php
    $response = wp_remote_get($url, array('timeout' => 30));
    if(is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200)
        return array();

    $xml = wp_remote_retrieve_body($response);
    if(empty($xml))
        return array();

    try {
        $data = new SimpleXMLElement($xml);
    } catch (Exception $e) {
        return array();
    }

    //convert xml to array in php
    $export = json_decode(json_encode($data->Export), true);

    if(empty($export["Veranstaltung"]) || !is_array($export["Veranstaltung"]))
        return array();

    // a single event is not wrapped in a list
    if(isset($export["Veranstaltung"]["ID"]))
        return array($export["Veranstaltung"]);

    return $export["Veranstaltung"];
}

/**
 * Returns a <dt>/<dd> pair of a definition list, or nothing if the value is empty.
 * Empty rows and <dt> without label are reported as accessibility errors.
 */
function ev_kirchen_termine_dl_row($label, $value_html, $class) {
    if(trim(wp_strip_all_tags($value_html)) === '')
        return '';

    return '<dt class="'.esc_attr($class).'-label">'.esc_html($label).'</dt><dd class="'.esc_attr($class).'">'.$value_html.'</dd>';
}

/**
 * Returns a mailto link or an empty string if no valid email address is given.
 */
function ev_kirchen_termine_mail_link($email) {
    $email = sanitize_email($email);
    if(empty($email))
        return '';

    return '<a href="mailto:'.esc_attr($email).'">'.esc_html($email).'</a>';
}

/**
 * Returns true if images of the event database should be stored in the media library
 * instead of being embedded from the remote server. Enabled by default for privacy reasons.
 */
function ev_kirchen_termine_use_local_images() {
    return (bool) get_option("ev_kirchen_termine_local_images", 1);
}

/**
 * Downloads a remote image into the media library (once) and returns the local url.
 * Returns an empty string if the image could not be stored locally.
 */
function ev_kirchen_termine_get_local_image_url($remote_url) {

    if(empty($remote_url))
        return "";

    $existing = get_posts(array(
        'post_type'      => 'attachment',
        'post_status'    => 'inherit',
        'posts_per_page' => 1,
        'fields'         => 'ids',
        'meta_key'       => '_ev_kirchen_termine_source_url', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- runs during the import only
        'meta_value'     => $remote_url, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- runs during the import only
    ));

    if(!empty($existing))
        return wp_get_attachment_url($existing[0]);

    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';

    $attachment_id = media_sideload_image($remote_url, 0, null, 'id');
    if(is_wp_error($attachment_id))
        return "";

    update_post_meta($attachment_id, '_ev_kirchen_termine_source_url', $remote_url);

    return wp_get_attachment_url($attachment_id);
}

/**
 * Deletes imported images which are not used by any of the current events anymore.
 */
function ev_kirchen_termine_delete_unused_images($used_remote_urls) {

    $attachments = get_posts(array(
        'post_type'      => 'attachment',
        'post_status'    => 'inherit',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'meta_key'       => '_ev_kirchen_termine_source_url', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- runs during the import only
    ));

    foreach ($attachments as $attachment_id) {
        if(!in_array(get_post_meta($attachment_id, '_ev_kirchen_termine_source_url', true), $used_remote_urls, true))
            wp_delete_attachment($attachment_id, true);
    }
}

/**
 * Returns the url under which an image of the event database should be shown on the website.
 */
function ev_kirchen_termine_image_url($remote_url) {

    if(!ev_kirchen_termine_use_local_images())
        return $remote_url;

    return ev_kirchen_termine_get_local_image_url($remote_url);
}

function ev_kirchen_termine_import_events($force = false) {

    // existing event posts, mapped by the event id of the event database
    $old_post_ids = get_posts(array(
        'post_type'      => 'evkite_event',
        'post_status'    => array_keys(get_post_stati()),
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'no_found_rows'  => true,
    ));
    update_meta_cache('post', $old_post_ids);

    $old_posts = array();
    foreach ($old_post_ids as $old_post_id) {
        $old_event_id = get_post_meta($old_post_id, '_ev_kirchen_termine_meta_key_id', true);
        if($old_event_id !== '')
            $old_posts[(string) $old_event_id] = $old_post_id;
    }

    $events = array();
    $events_data = array();

    $parameter["region"] = "all";
    $parameter["itemsPerPage"] = 3000;
    $parameter["highlight"] = "all";
    $parameter["past"] = 2; // load also past events
    $parameter["start"] = wp_date("Y-m-d", strtotime("-30 days"));
    $parameter["end"] = wp_date("Y-m-d", strtotime("+120 days"));

    $ev_kirchen_termine_webpage = get_option("ev_kirchen_termine_webpage");

    if(empty($ev_kirchen_termine_webpage))
        return false;

    # Get events from vid
    if(!empty(get_option("ev_kirchen_termine_vid"))) {

        $parameter["vid"] = get_option("ev_kirchen_termine_vid");

        if(!empty(get_option("ev_kirchen_termine_vid_eventtype_filter")))
            $parameter["eventtype"] = get_option("ev_kirchen_termine_vid_eventtype_filter");

        $url = $ev_kirchen_termine_webpage.'/Veranstalter/xml.php?'.http_build_query($parameter);

        $events_data = array_merge($events_data, ev_kirchen_termine_fetch_events($url));

    }

    # Get events from region
    if(!empty(get_option("ev_kirchen_termine_region"))) {

        $parameter["region"] = get_option("ev_kirchen_termine_region");
        $parameter["vid"] = "all";
        $parameter["eventtype"] = "all";

        if(!empty(get_option("ev_kirchen_termine_region_eventtype_filter")))
            $parameter["eventtype"] = get_option("ev_kirchen_termine_region_eventtype_filter");

        $url = $ev_kirchen_termine_webpage.'/Veranstalter/xml.php?'.http_build_query($parameter);

        $events_data = array_merge($events_data, ev_kirchen_termine_fetch_events($url));

    }

    # Get events from custom filter
    if(!empty(get_option("ev_kirchen_termine_custom_filter"))) {

        $url = $ev_kirchen_termine_webpage.'/Veranstalter/xml.php?'.html_entity_decode(get_option("ev_kirchen_termine_custom_filter"));

        $events_data = array_merge($events_data, ev_kirchen_termine_fetch_events($url));

    }


    // Transform Array

    $data_keys = array(
        "ID",
        "SUBTITLE",
        "START_RFC",
        "END_RFC",
        "_event_TITLE",
        "_event_EVENTTYPE",
        "_event_PEOPLE",
        "_event_SHORT_DESCRIPTION",
        "_event_LONG_DESCRIPTION",
        "_event_LINK",
        "_event_EMAIL",
        "_event_PERSON_ID",
        "_event_TEXTBOX_1",
        "_event_FEEDBACK_ID",
        "_poll_ID",
        "_event_HIGHLIGHT",
        "_event_USER_ID",
        "_event_IMAGE",
        "_event_IMAGE_ALT_DESCRIPTION",
        "_event_CAPTION",
        "_event_MODIFIED",
        "_place_ID",
        "_place_NAME",
        "_place_STREET_NR",
        "_place_ZIP",
        "_place_CITY",
        "_place_IMAGE",
        "_place_POSITION",
        "_place_KAT",
        "_place_GLAT",
        "_place_GLONG",
        "_place_EQUIPTEXT",
        "_person_NAME",
        "_person_EMAIL",
        "_person_CONTACT",
        "_user_ID",
        "_user_REALNAME",
        "_user_DESCRIPTION",
        "_user_STREET_NR",
        "_user_ZIP",
        "_user_CITY",
        "_user_EMAIL",
        "_user_URL",
        "_user_CONTACT",
        "_user_IMAGE",
        "LITURG_BEZ",
        "CHANNELS"
    );


    foreach ($events_data as $event_data) {

        $event = array();

        foreach($data_keys as $data_key) {
            if(isset($event_data[$data_key]) && is_string($event_data[$data_key])) {
                $event[$data_key] = $event_data[$data_key];
            } else {
                $event[$data_key] = "";
            }
        }

        if(!empty($event_data["_place_EQUIP"]["@attributes"]["db"])) {
            $event["_place_EQUIP"] = $event_data["_place_EQUIP"]["@attributes"]["db"];
        } else {
            $event["_place_EQUIP"] = "";
        }
        

        if(isset($event_data["ID"])) 
            $events[$event_data["ID"]] = $event;
    }

    // End Transform

    $date_format = get_option( 'date_format' );
    $time_format = get_option( 'time_format' );


    if(empty($events)) {
        return false;
    }

    // For Iframe
    remove_filter('content_save_pre', 'wp_filter_post_kses');
    remove_filter('content_filtered_save_pre', 'wp_filter_post_kses');


    // Delete old/delted Events
    $current_event_ids = array_map('strval', array_column($events, 'ID'));
    foreach ($old_posts as $old_event_id => $old_post_id) {
        if(!in_array((string) $old_event_id, $current_event_ids, true))
            wp_delete_post($old_post_id, true);
    }

    $used_image_urls = array();
    $used_map_image_ids = array();

    foreach ($events as $event) {

        if(empty($event["_event_LONG_DESCRIPTION"]))
             $event["_event_LONG_DESCRIPTION"] = $event["_event_TEXTBOX_1"];

        $title          = $event["_event_TITLE"];

        $subtitle_html  = "";
        if(!empty($event["SUBTITLE"]))
            $subtitle_html  = '<h2 class="evkite-events-single-event-subtitle">'.$event["SUBTITLE"].'</h2>';


        $text           = nl2br($event["_event_LONG_DESCRIPTION"]);

        $text           = preg_replace(
                            '`([^"=\'>])(((http|https|ftp)://|www.)[^\s<]+[^\s<\.)])`i',
                            '$1<a href="$2" rel="nofollow">$2</a>',
                            $text
                        );
        $text           = preg_replace('`href=\"www`','href="http://www',$text);

        $start_datetime = get_date_from_gmt($event["START_RFC"]);
        $end_datetime   = get_date_from_gmt($event["END_RFC"]);
        $start_timestamp = strtotime($event["START_RFC"]);
        $end_timestamp   = strtotime($event["END_RFC"]);

        if(wp_date("Y-m-d", $start_timestamp) == wp_date("Y-m-d", $end_timestamp)) {
            /* translators: 1: date, 2: start time, 3: end time */
            $timespan  = wp_sprintf(__('%1$s at %2$s - %3$s', 'ev-kirchen-termine'),
                wp_date($date_format, $start_timestamp),
                wp_date($time_format, $start_timestamp),
                wp_date($time_format, $end_timestamp)
            );
            $date      = wp_date($date_format, $start_timestamp);
            $time      = wp_sprintf('%s - %s',
                wp_date($time_format, $start_timestamp),
                wp_date($time_format, $end_timestamp)
            );
        } else {
            /* translators: 1: start date, 2: start time, 3: end date, 4: end time */
            $timespan  = wp_sprintf(__('%1$s at %2$s - %3$s at %4$s', 'ev-kirchen-termine'),
                wp_date($date_format, $start_timestamp),
                wp_date($time_format, $start_timestamp),
                wp_date($date_format, $end_timestamp),
                wp_date($time_format, $end_timestamp)
            );
            $date      = wp_sprintf('%s - %s',
                wp_date($date_format, $start_timestamp),
                wp_date($date_format, $end_timestamp),
            );
            $time      = $timespan;
        }

        $media          = "";
        if(strpos($event["_event_LINK"], "https://youtu.be") !== false || strpos($event["_event_LINK"], "https://www.youtube.com") !== false) {
            $media     .= '<a target="_blank" title="YouTube" class="btn" style="background-color: #e52d27;" href="'.$event["_event_LINK"].'">'.esc_html__('Go to YouTube', 'ev-kirchen-termine').'</a><br/>';
        } elseif(strpos($event["_event_LINK"], ".zoom.us/j/") !== false) {
            if(wp_date("Y-m-d", $end_timestamp) >= wp_date("Y-m-d")) {
                $media     .= '<a target="_blank" title="Zoom" class="btn" style="background-color: #2D8CFF;" href="'.$event["_event_LINK"].'">'.esc_html__('Join the Zoom meeting', 'ev-kirchen-termine').'</a><br/>';
            } else {
                $media     .= '<a target="_blank" title="Zoom" class="btn" style="background-color: #2D8CFF;" href="#" disabled>'.esc_html__('The Zoom meeting is already over', 'ev-kirchen-termine').'</a><br/>';
            }
        } elseif(!empty($event["_event_LINK"])) {
            $media     .= '<a target="_blank" title="'.esc_attr__('Website', 'ev-kirchen-termine').'" class="btn" style="background-color: #e52d27;" href="'.$event["_event_LINK"].'">'.esc_html__('Go to the event page', 'ev-kirchen-termine').'</a><br/>';
        }

        $image_remote_url = !empty($event["_event_IMAGE"]) ? $event["_event_IMAGE"] : $event["_place_IMAGE"];
        // the event database returns protocol relative urls
        if(strpos($image_remote_url, "//") === 0)
            $image_remote_url = "https:".$image_remote_url;
        $image_url        = "";
        if(!empty($image_remote_url)) {
            $used_image_urls[] = $image_remote_url;
            $image_url = ev_kirchen_termine_image_url($image_remote_url);
        }

        $image_alt = !empty($event["_event_IMAGE_ALT_DESCRIPTION"]) ? $event["_event_IMAGE_ALT_DESCRIPTION"] : __('Event image', 'ev-kirchen-termine');
        if(!empty($image_url))
            $media .= '<img src="' .esc_url($image_url) .'" alt="'.esc_attr($image_alt).'" class="alignright" width="414" height="auto">';

        $feedback_link  = (empty($event["_poll_ID"]) ? "" : get_option("ev_kirchen_termine_webpage")."/webformular/input/liste/".$event["_poll_ID"]);
        $feedback       = "";
        // a real link instead of a JS popup: reachable by keyboard and announced as a link by screen readers
        if(!empty($feedback_link))
            $feedback   = ' <br/>
                            <a href="'.esc_url($feedback_link).'" target="_blank" rel="noopener" class="btn btn-primary">'
                                .esc_html__('Go to registration', 'ev-kirchen-termine')
                                .'<span class="screen-reader-text"> '.esc_html__('(opens in a new tab)', 'ev-kirchen-termine').'</span>'
                            .'</a>';

        $ical_link      = get_option("ev_kirchen_termine_webpage")."/ical-". $event["ID"];

        $google_cal_link_parameter = array();
        $google_cal_link_parameter["action"] = "TEMPLATE";
        $google_cal_link_parameter["text"] = $event["_event_TITLE"];
        $google_cal_link_parameter["dates"] = wp_date("Ymd\THis", $start_timestamp)."/".wp_date("Ymd\THis", $end_timestamp);
        $google_cal_link_parameter["details"] = $event["_event_LONG_DESCRIPTION"];
        $google_cal_link_parameter["location"] = $event["_place_STREET_NR"].", ".$event["_place_ZIP"]." ".$event["_place_CITY"];
        $google_cal_link_parameter["trp"] = true;
        $google_cal_link_parameter["ctz"] = "Europe/Berlin";

        $google_cal_link = 'https://www.google.com/calendar/event?'.http_build_query($google_cal_link_parameter);


        $office_365_cal_link_parameter = array();
        $google_cal_cal_link_parameter["rru"] = "addevent";
        $office_365_cal_link_parameter["subject"] = $event["_event_TITLE"];
        $office_365_cal_link_parameter["startdt"] = $event["START_RFC"];
        $office_365_cal_link_parameter["enddt"] = $event["END_RFC"];
        $office_365_cal_link_parameter["body"] = $event["_event_LONG_DESCRIPTION"];
        $office_365_cal_link_parameter["location"] = $event["_place_STREET_NR"].", ".$event["_place_ZIP"]." ".$event["_place_CITY"];
        $office_365_cal_link_parameter["path"] = "/calendar/action/compose";

        $office_365_cal_link = 'https://outlook.office.com/calendar/0/deeplink/compose?'.http_build_query($office_365_cal_link_parameter);


        $type           = implode(", ", explode(",", $event["_event_EVENTTYPE"]));
        $tags           = explode(",", $event["CHANNELS"]);
        foreach ($tags as $tag_key => $tag_value) {
            $tags[$tag_key] = '<a href="'.get_site_url().'/events/?channel='.sanitize_title($tag_value).'">'.$tag_value.'</a>';
        }
        $tags           = implode(", ", $tags);


        $org_name       = $event["_user_REALNAME"];
        $org_contact    = $event["_user_CONTACT"];
        $org_url        = $event["_user_URL"];
        $org_email      = $event["_user_EMAIL"];

    		$event_tags		= explode(",", $event["CHANNELS"]);
    		if($event["_event_HIGHLIGHT"] !== "low")
    			array_push($event_tags, "Event-Highlight");

        $organizer      = "";
        if(!empty($event["_event_PERSON_ID"]))
            $organizer  = '<div class="evkite-events-meta-group evkite-events-meta-group-organizer">
                                <h2 class="evkite-events-single-section-title">'.esc_html__('Contact person', 'ev-kirchen-termine').'</h2>
                                <dl>'.
                                    ev_kirchen_termine_dl_row(__('Name:', 'ev-kirchen-termine'), esc_html($event["_person_NAME"]), 'evkite-organizer').
                                    ev_kirchen_termine_dl_row(__('Email:', 'ev-kirchen-termine'), ev_kirchen_termine_mail_link($event["_person_EMAIL"]), 'evkite-organizer-email').
                                    ev_kirchen_termine_dl_row(__('Contact:', 'ev-kirchen-termine'), nl2br(esc_html($event["_person_CONTACT"])), 'evkite-organizer-contact').
                                '</dl>
                            </div>';


        $parameter_event_shema = array(
            "@context"=> "https://schema.org",
            "@type"=> "Event",
            "name"=> $event["_event_TITLE"],
            "startDate"=> $event["START_RFC"],
            "endDate"=> $event["END_RFC"],
            "description"=> addslashes($event["_event_LONG_DESCRIPTION"]),
            "location"=> array(),
            "image"=> array(),
            "sameAs"=> $ev_kirchen_termine_webpage. "/veranstaltung_detail". $event["ID"]. ".html",
        );

        // ermittlung ob Event abgesagt wurde anhand des Titels

        if(
            fnmatch("*entfällt", $event["_event_TITLE"]) ||
            fnmatch("*fällt*aus*", $event["_event_TITLE"]) ||
            fnmatch("*Corona-Pause*", $event["_event_TITLE"]) ||
            fnmatch("*geschlossen*", $event["_event_TITLE"])
        ) {
            $parameter_event_shema["eventStatus"] = "https://schema.org/EventCancelled";
        } else {
            $parameter_event_shema["eventStatus"] = "https://schema.org/EventScheduled";
        }

        // Veranstaltungsort

        if(!empty($event["_place_ID"]) && $event["_place_ID"] !== "1")
            $parameter_event_shema["location"][] = array(
                "@type"=> "Place",
                "name"=> $event["_place_NAME"],
                "address"=> array(
                    "@type"=> "PostalAddress",
                    "streetAddress"=> $event["_place_STREET_NR"],
                    "addressLocality"=> $event["_place_CITY"],
                    "postalCode"=> $event["_place_ZIP"],
                    "addressCountry"=> "DE"
                ),
                "geo"=> array(
                    "@type"=> "GeoCoordinates",
                    "latitude"=> $event["_place_GLAT"],
                    "longitude"=> $event["_place_GLONG"],
                ),
            );


        // Virtuelle veranstalltungen mit Youtube Link oder Zoom Link

        if(strpos($event["_event_LINK"], "https://youtu.be") !== false || strpos($event["_event_LINK"], "https://www.youtube.com") !== false)
            $parameter_event_shema["location"][] = array(
                "@type"=> "VirtualLocation",
                "url"=> $event["_event_LINK"],
            );

        if(strpos($event["_event_LINK"], ".zoom.us/j/") !== false)
            $parameter_event_shema["location"][] = array(
                "@type"=> "VirtualLocation",
                "url"=> $event["_event_LINK"],
            );


        // Veranstalltungstyp ermittlung Offline/Online/Hybrid

        if(in_array('VirtualLocation', array_column($parameter_event_shema["location"], '@type'))) {
            if(in_array('Place', array_column($parameter_event_shema["location"], '@type'))) {
                $parameter_event_shema["eventAttendanceMode"] = "https://schema.org/MixedEventAttendanceMode";
            } else {
                $parameter_event_shema["eventAttendanceMode"] = "https://schema.org/OnlineEventAttendanceMode";
            }
        } else {
            if(in_array('Place', array_column($parameter_event_shema["location"], '@type'))) {
                $parameter_event_shema["eventAttendanceMode"] = "https://schema.org/OfflineEventAttendanceMode";
            }
        }


        // Veranstalltungsbilder

        if(!empty($image_url))
            $parameter_event_shema["image"][] = $image_url;


        // Ansprechpartner der Veranstaltung

        if(!empty($event["_event_PERSON_ID"]))
            $parameter_event_shema["performer"] = array(
                "@type"=> "Person",
                "name"=> $event["_person_NAME"],
                "email"=> $event["_person_EMAIL"],
            );

        // Veranstalter

        if(!empty($event["_user_ID"]))
            $parameter_event_shema["organizer"] = array(
                "@type"=> "Organization",
                "name"=> $event["_user_REALNAME"],
                "description"=> $event["_user_DESCRIPTION"],
                "url"=> $event["_user_URL"],
                "email"=> $event["_user_EMAIL"],
                "address"=> array(
                    "@type"=> "PostalAddress",
                    "streetAddress"=> $event["_user_STREET_NR"],
                    "addressLocality"=> $event["_user_CITY"],
                    "postalCode"=> $event["_user_ZIP"],
                    "addressCountry"=> "DE"
                ),
                "logo"=> $event["_user_IMAGE"],
            );

        $location_json = array(
            "id"=> $event["_place_ID"],
            "name"=> $event["_place_NAME"],
            "streetAddress"=> $event["_place_STREET_NR"],
            "city"=> $event["_place_CITY"],
            "postalCode"=> $event["_place_ZIP"],
            "country"=> "DE",
            "latitude"=> $event["_place_GLAT"],
            "longitude"=> $event["_place_GLONG"],
            "equipped"=> $event["_place_EQUIP"],
            "equipped_text"=> $event["_place_EQUIPTEXT"],
            "map_image_id"=> 0,
        );

        if(ev_kirchen_termine_use_osm_map_image() && !empty($event["_place_ID"]) && $event["_place_ID"] !== "1") {
            $location_json["map_image_id"] = ev_kirchen_termine_get_osm_map_image_id($event["_place_GLAT"], $event["_place_GLONG"], $event["_place_NAME"]);
            $used_map_image_ids[] = $location_json["map_image_id"];
        }

        $user_data = array(
            "id"=> $event["_user_ID"],
            "name"=> $event["_user_REALNAME"],
            "description"=> $event["_user_DESCRIPTION"],
            "email"=> $event["_user_EMAIL"],
            "url"=> $event["_user_URL"],
            "contact"=> $event["_user_CONTACT"],
            "streetAddress"=> $event["_user_STREET_NR"],
            "city"=> $event["_user_CITY"],
            "postalCode"=> $event["_user_ZIP"],
            "country"=> "DE",
            "image"=> $event["_user_IMAGE"],
        );

        $person_data = array(
            "id"=> $event["_event_PERSON_ID"],
            "name"=> $event["_person_NAME"],
            "email"=> $event["_person_EMAIL"],
            "contact"=> $event["_person_CONTACT"],
        );


        $args = array(
            'post_type'    => 'evkite_event',
            'post_status'  => 'publish',
            'post_excerpt' => $timespan.' / </br>'.$event["_place_NAME"].' / </br>'.sprintf(/* translators: %s: name of the contact person */ __('with %s', 'ev-kirchen-termine'), $event["_person_NAME"]),
            'post_content' =>
                '<script type="application/ld+json">'.json_encode($parameter_event_shema, JSON_UNESCAPED_UNICODE).'</script>
                <div id="evkite-events">
                    <div id="evkite-events-content" class="evkite-events-single">
                        <p class="evkite-events-back">
                            <a href="'.get_site_url().'/events/">&laquo;'.esc_html__('All events', 'ev-kirchen-termine').'</a>
                        </p>
                        <h1 class="evkite-events-single-event-title">'.$title.'</h1>
                        '.$subtitle_html.'
                        <div class="evkite-events-schedule evkite-clearfix">
                            <h2>'.$timespan.'</h2>
                        </div>
                        </br>
                        <div class="evkite_events type-evkite_events status-publish hentry">
                            <div class="evkite-events-single-event-description evkite-events-content">
                                <p>'.$media.$text.$feedback.'</p>
                            </div>
                            <div class="evkite-events-cal-links">
                                <a class="evkite-events-ical evkite-events-button" href="'.$ical_link.'" title="'.esc_attr__('Download .ics file', 'ev-kirchen-termine').'">+ '.esc_html__('Export iCal', 'ev-kirchen-termine').'</a>
                                <a target="_blank" class="evkite-events-gcal evkite-events-button" href="'.$google_cal_link.'" title="'.esc_attr__('Add to Google Calendar', 'ev-kirchen-termine').'">'.esc_html__('Add to Google Calendar', 'ev-kirchen-termine').'</a>
                                <a target="_blank" class="evkite-events-o365 evkite-events-button" href="'.$office_365_cal_link.'" title="'.esc_attr__('Add to Outlook.com / Office 365 calendar', 'ev-kirchen-termine').'">'.esc_html__('Add to Outlook.com calendar', 'ev-kirchen-termine').'</a>
                            </div>
                            <div class="evkite-events-single-section evkite-events-event-meta primary evkite-clearfix">
                                <div class="evkite-events-meta-group evkite-events-meta-group-details">
                                    <h2 class="evkite-events-single-section-title">'.esc_html__('Details', 'ev-kirchen-termine').'</h2>
                                    <dl>
                                        <dt class="evkite-events-start-date-label">'.esc_html__('Date:', 'ev-kirchen-termine').'</dt>
                                        <dd>
                                            <abbr class="evkite-events-abbr evkite-events-date published dtstart">'.$date.'</abbr>
                                        </dd>
                                        <dt class="evkite-events-start-time-label">'.esc_html__('Time:', 'ev-kirchen-termine').'</dt>
                                        <dd>
                                            <div class="evkite-events-abbr evkite-events-time published dtstart">'.$time.'</div>
                                        </dd>
                                        <dt class="evkite-events-event-categories-label">'.esc_html__('Event category:', 'ev-kirchen-termine').'</dt>
                                        <dd class="evkite-events-event-categories">'.$type.'</dd>
                                        <dt>'.esc_html__('Event tags:', 'ev-kirchen-termine').'</dt>
                                        <dd class="evkite-event-tags">'.$tags.'</dd>
                                    </dl>
                                </div>
                                '.$organizer.'
                                <div class="evkite-events-meta-group evkite-events-meta-group-organizer">
                                    <h2 class="evkite-events-single-section-title">'.esc_html__('Organizer / published by', 'ev-kirchen-termine').'</h2>
                                    <dl>'.
                                        ev_kirchen_termine_dl_row(__('Name:', 'ev-kirchen-termine'), empty($org_name) ? '' : '<a href="'.esc_url(get_site_url().'/events/?vid='.$event["_event_USER_ID"]).'">'.esc_html($org_name).'</a>', 'evkite-organizer').
                                        ev_kirchen_termine_dl_row(__('Contact:', 'ev-kirchen-termine'), nl2br(esc_html($org_contact)), 'evkite-organizer-tel').
                                        ev_kirchen_termine_dl_row(__('Email:', 'ev-kirchen-termine'), ev_kirchen_termine_mail_link($org_email), 'evkite-organizer-email').
                                        ev_kirchen_termine_dl_row(__('Website:', 'ev-kirchen-termine'), empty($org_url) ? '' : '<a href="'.esc_url($org_url).'">'.esc_html($org_url).'</a>', 'evkite-organizer-url').
                                    '</dl>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>',
            'post_title'   => $event["_event_TITLE"],
            'tags_input'   => $event_tags,
            'meta_input'   => array(
                '_ev_kirchen_termine_meta_key_start' => gmdate("Y-m-d H:i:s", $start_timestamp),
                '_ev_kirchen_termine_meta_key_end' => gmdate("Y-m-d H:i:s", $end_timestamp),
                '_ev_kirchen_termine_meta_key_location_json' => $location_json,
                '_ev_kirchen_termine_meta_key_id' => (int) $event["ID"],
                '_ev_kirchen_termine_meta_key_vid' => (int) $event["_event_USER_ID"],
                '_ev_kirchen_termine_meta_key_user_data' => $user_data,
                '_ev_kirchen_termine_meta_key_person_data' => $person_data,
                '_ev_kirchen_termine_meta_key_highlight' => ($event["_event_HIGHLIGHT"] !== "low"),
            )
        );

        $args["post_content"] = str_replace(array("\n","\r","\t"), "", $args["post_content"]);

        $current_id = isset($old_posts[(string) $event["ID"]]) ? $old_posts[(string) $event["ID"]] : NULL;

        if(empty($current_id)) {
            $args['post_name'] = wp_date("Y-m-d", $start_timestamp) ." - " .$event["_event_TITLE"];
            if($event["_user_ID"] !== $parameter["vid"])
                $args['post_name'] .= ' - '.$event["_user_REALNAME"];
            $id = wp_insert_post($args);
        } elseif(
            wp_date("Y-m-d H:i:s", filemtime(__FILE__)) > get_the_modified_date("Y-m-d H:i:s", $current_id) ||
            wp_date("Y-m-d H:i:s", strtotime($event["_event_MODIFIED"])) > get_the_modified_date("Y-m-d H:i:s", $current_id) ||
            $force
        ) {
            $args["ID"] = $current_id;
            wp_update_post( $args );
            wp_set_post_tags($current_id, $args["tags_input"]);
        }

    }

    if(ev_kirchen_termine_use_local_images())
        ev_kirchen_termine_delete_unused_images($used_image_urls);

    if(ev_kirchen_termine_use_osm_map_image())
        ev_kirchen_termine_delete_unused_osm_map_images($used_map_image_ids);

    add_filter('content_save_pre', 'wp_filter_post_kses');
    add_filter('content_filtered_save_pre', 'wp_filter_post_kses');

}
