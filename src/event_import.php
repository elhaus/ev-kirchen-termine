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
            'post_title'     => ucwords('Termine'),
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

function ev_kirchen_termine_import_events($force = false) {

    global $wpdb;
    $old_posts_meta = $wpdb->get_results("SELECT * FROM $wpdb->postmeta WHERE meta_key LIKE '_ev_kirchen_termine_meta_key_id'");

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

        //read xml file from url in php
        $data = new SimpleXMLElement(file_get_contents($url));

        //convert xml to array in php
        $events_data = array_merge($events_data, json_decode(json_encode($data->Export), true)["Veranstaltung"]);

    }

    # Get events from region
    if(!empty(get_option("ev_kirchen_termine_region"))) {

        $parameter["region"] = get_option("ev_kirchen_termine_region");
        $parameter["vid"] = "all";
        $parameter["eventtype"] = "all";

        if(!empty(get_option("ev_kirchen_termine_region_eventtype_filter")))
            $parameter["eventtype"] = get_option("ev_kirchen_termine_region_eventtype_filter");

        $url = $ev_kirchen_termine_webpage.'/Veranstalter/xml.php?'.http_build_query($parameter);

        //read xml file from url in php
        $data = new SimpleXMLElement(file_get_contents($url));

        //convert xml to array in php
        $events_data = array_merge($events_data, json_decode(json_encode($data->Export), true)["Veranstaltung"]);

    }

    # Get events from custom filter
    if(!empty(get_option("ev_kirchen_termine_custom_filter"))) {

        $url = $ev_kirchen_termine_webpage.'/Veranstalter/xml.php?'.html_entity_decode(get_option("ev_kirchen_termine_custom_filter"));

        //read xml file from url in php
        $data = new SimpleXMLElement(file_get_contents($url));

        //convert xml to array in php
        $events_data = array_merge($events_data, json_decode(json_encode($data->Export), true)["Veranstaltung"]);

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
        "_event_HIGHLIGHT",
        "_event_USER_ID",
        "_event_IMAGE",
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
    foreach ($old_posts_meta as $old_post_meta) {

        $delete_post = true;
        foreach ($events as $event) {
            if($old_post_meta->meta_value == $event["ID"]) {
                $delete_post = false;
                break;
            }
        }

        if($delete_post)
            wp_delete_post($old_post_meta->post_id, true);

    }

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
            $timespan  = wp_sprintf('%s um %s - %s Uhr',
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
            $timespan  = wp_sprintf('%s um %s - %s um %s',
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
            $media     .= '<a target="_blank" title="YouTube" class="btn" style="background-color: #e52d27;" href="'.$event["_event_LINK"].'">zu YouTube</a><br/>';
        } elseif(strpos($event["_event_LINK"], ".zoom.us/j/") !== false) {
            if(wp_date("Y-m-d", $end_timestamp) >= wp_date("Y-m-d")) {
                $media     .= '<a target="_blank" title="Zoom" class="btn" style="background-color: #2D8CFF;" href="'.$event["_event_LINK"].'">zum Zoom-Meeting</a><br/>';
            } else {
                $media     .= '<a target="_blank" title="Zoom" class="btn" style="background-color: #2D8CFF;" href="#" disabled>das Zoom-Meeting ist schon vorbei</a><br/>';
            }
        } elseif(!empty($event["_event_LINK"])) {
            $media     .= '<a target="_blank" title="Webseite" class="btn" style="background-color: #e52d27;" href="'.$event["_event_LINK"].'">zur Veranstaltungs Seite</a><br/>';
        }

        if(!empty($event["_event_IMAGE"]))
            $media .= '<img src="' .$event["_event_IMAGE"] .'" alt="Event_Bild" class="alignright" width="414" height="auto">';
        elseif(!empty($event["_place_IMAGE"]))
            $media .= '<img src="' .$event["_place_IMAGE"] .'" alt="Event_Bild" class="alignright" width="414" height="auto">';

        $feedback_link  = (empty($event["_event_FEEDBACK_ID"]) ? "" : get_option("ev_kirchen_termine_webpage")."/rueckmeldeformular".$event["_event_FEEDBACK_ID"]."-".$event["ID"]);
        $feedback       = "";
        if(!empty($feedback_link))
            $feedback   = ' <br/>
                            <a target="_blank" title="Anmelden" class="btn btn-primary"
                                onclick=\'window.open("'.$feedback_link.'", "Rückmeldeformular","toolbar=no,location=no,status=no,menubar=no,scrollbars=yes,resizable=yes,width=850,height=650,top=10,left=200");\'>
                                zur Anmeldung
                            </a>
                            <noscript>
                                <a target="_blank" href="'.$feedback_link.'" title="Anmelden" class="btn btn-primary">
                                    zur Anmeldung
                                </a>
                            </noscript>';

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
                                <h2 class="evkite-events-single-section-title">Ansprechpartner</h2>
                                <dl>
                                    <dt style="display:none;"></dt>
                                    <dd class="evkite-organizer">'.$event["_person_NAME"].'</dd>
                                    <dt class="evkite-organizer-email-label">E-Mail:</dt>
                                    <dd class="evkite-organizer-email"><a href="mailto:'.$event["_person_EMAIL"].'">'.$event["_person_EMAIL"].'</a></dd>
                                    <dt class="evkite-organizer-contact-label">Kontakt:</dt>
                                    <dd class="evkite-organizer-contact">'.nl2br($event["_person_CONTACT"]).'</dd>
                                </dl>
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

        if(!empty($event["_event_IMAGE"]))
            $parameter_event_shema["image"][] = $event["_event_IMAGE"];

        if(!empty($event["_place_IMAGE"]))
            $parameter_event_shema["image"][] = $event["_place_IMAGE"];


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
        );

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


        $args = array(
            'post_type'    => 'evkite_event',
            'post_status'  => 'publish',
            'post_excerpt' => $timespan.' / </br>'.$event["_place_NAME"].' / </br>mit '.$event["_person_NAME"],
            'post_content' =>
                '<script type="application/ld+json">'.json_encode($parameter_event_shema, JSON_UNESCAPED_UNICODE).'</script>
                <div id="evkite-events">
                    <div id="evkite-events-content" class="evkite-events-single">
                        <p class="evkite-events-back">
                            <a href="'.get_site_url().'/events/">&laquo;Alle Veranstaltungen</a>
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
                                <a class="evkite-events-ical evkite-events-button" href="'.$ical_link.'" title=".ics Datei herunterladen">+ Exportiere iCal</a>
                                <a target="_blank" class="evkite-events-gcal evkite-events-button" href="'.$google_cal_link.'" title="zu Google Kalender hinzufügen">zu Google Kalender hinzufügen</a>
                                <a target="_blank" class="evkite-events-o365 evkite-events-button" href="'.$office_365_cal_link.'" title="zu Outlook.com / Office365 Kalender hinzufügen">zu Outlook.com Kalender hinzufügen</a>
                            </div>
                            <div class="evkite-events-single-section evkite-events-event-meta primary evkite-clearfix">
                                <div class="evkite-events-meta-group evkite-events-meta-group-details">
                                    <h2 class="evkite-events-single-section-title">Details</h2>
                                    <dl>
                                        <dt class="evkite-events-start-date-label">Datum:</dt>
                                        <dd>
                                            <abbr class="evkite-events-abbr evkite-events-date published dtstart">'.$date.'</abbr>
                                        </dd>
                                        <dt class="evkite-events-start-time-label">Zeit:</dt>
                                        <dd>
                                            <div class="evkite-events-abbr evkite-events-time published dtstart">'.$time.'</div>
                                        </dd>
                                        <dt class="evkite-events-event-categories-label">Veranstaltungskategorie:</dt>
                                        <dd class="evkite-events-event-categories">'.$type.'</dd>
                                        <dt>Veranstaltung-Tags:</dt>
                                        <dd class="evkite-event-tags">'.$tags.'</dd>
                                    </dl>
                                </div>
                                '.$organizer.'
                                <div class="evkite-events-meta-group evkite-events-meta-group-organizer">
                                    <h2 class="evkite-events-single-section-title">Veranstalter / veröffentlicht von</h2>
                                    <dl>
                                        <dt style="display:none;"></dt>
                                        <dd class="evkite-organizer">
                                            <a href="'.get_site_url().'/events/?vid='.$event["_event_USER_ID"].'">'.$org_name.'</a>
                                        </dd>
                                        <dt class="evkite-organizer-tel-label">Kontakt:</dt>
                                        <dd class="evkite-organizer-tel">'.nl2br($org_contact).'</dd>
                                        <dt class="evkite-organizer-email-label">E-Mail:</dt>
                                        <dd class="evkite-organizer-email"><a href="mailto:'.$org_email.'">'.$org_email.'</a></dd>
                                        <dt class="evkite-organizer-url-label">Website:</dt>
                                        <dd class="evkite-organizer-url">
                                            <a href="'.$org_url.'" target="_self">'.$org_url.'</a>
                                        </dd>
                                    </dl>
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
                '_ev_kirchen_termine_meta_key_highlight' => ($event["_event_HIGHLIGHT"] !== "low"),
            )
        );

        $args["post_content"] = str_replace(array("\n","\r","\t"), "", $args["post_content"]);

        $current_id = NULL;

        foreach ($old_posts_meta as $old_post_meta) {
            if($old_post_meta->meta_value == $event["ID"]) {
                $current_id = $old_post_meta->post_id;
            }
        }

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

    add_filter('content_save_pre', 'wp_filter_post_kses');
    add_filter('content_filtered_save_pre', 'wp_filter_post_kses');

}
