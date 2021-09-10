<?php


/**
 *
 * Corn
 *
**/

register_activation_hook(__FILE__, 'ev_kirchen_termine_import_events_task_plugin_activate');
function ev_kirchen_termine_import_events_task_plugin_activate() {
    if (!wp_next_scheduled('ev_kirchen_termine_import_events_task')) {
        wp_schedule_event(time(), 'hourly', 'ev_kirchen_termine_import_events_task');;
    }
}

register_deactivation_hook(__FILE__, 'ev_kirchen_termine_import_events_task_plugin_deactivation');
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

    $parameter["itemsPerPage"] = 5000;
    $parameter["vid"] = get_option("ev_kirchen_termine_vid");
    $parameter["start"] = date("Y-m-d", strtotime("-90 days"));
    $parameter["end"] = date("Y-m-d", strtotime("+240 days"));

    $ev_kirchen_termine_webpage = get_option("ev_kirchen_termine_webpage");

    if(empty($ev_kirchen_termine_webpage))
        return false;

    $url = $ev_kirchen_termine_webpage.'/json?'.http_build_query($parameter);

    //read json file from url in php
    $readJSONFile = file_get_contents($url);

    //convert json to array in php
    $events = json_decode($readJSONFile, TRUE);

    $date_format = get_option( 'date_format' );
    $time_format = get_option( 'time_format' );


    // For Iframe
    remove_filter('content_save_pre', 'wp_filter_post_kses');
    remove_filter('content_filtered_save_pre', 'wp_filter_post_kses');


    // Delete old/delted Events
    foreach ($old_posts_meta as $old_post_meta) {

        $delete_post = true;
        foreach ($events as $event) {
            if($old_post_meta->meta_value == $event["Veranstaltung"]["ID"]) {
                $delete_post = false;
                break;
            }
        }

        if($delete_post)
            wp_delete_post($old_post_meta->post_id, true);

    }

    foreach ($events as $event) {

        if(empty($event["Veranstaltung"]["_event_LONG_DESCRIPTION"]))
             $event["Veranstaltung"]["_event_LONG_DESCRIPTION"] = $event["Veranstaltung"]["_event_TEXTBOX_1"];

        $title          = $event["Veranstaltung"]["_event_TITLE"];
        $text           = nl2br($event["Veranstaltung"]["_event_LONG_DESCRIPTION"]);

        $text           = preg_replace(
                            '`([^"=\'>])(((http|https|ftp)://|www.)[^\s<]+[^\s<\.)])`i',
                            '$1<a href="$2" rel="nofollow">$2</a>',
                            $text
                        );
        $text           = preg_replace('`href=\"www`','href="http://www',$text);

        $start_datetime = get_date_from_gmt($event["Veranstaltung"]["START_RFC"]);
        $end_datetime   = get_date_from_gmt($event["Veranstaltung"]["END_RFC"]);

        if(date("Y-m-d", strtotime($start_datetime)) == date("Y-m-d", strtotime($end_datetime))) {
             $timespan  = date_i18n($date_format, strtotime($start_datetime))." um ".date_i18n($time_format, strtotime($start_datetime))." - ".date_i18n($time_format, strtotime($end_datetime));
             $date      = date_i18n($date_format, strtotime($start_datetime));
             $time      = date_i18n($time_format, strtotime($start_datetime))." - ".date_i18n($time_format, strtotime($end_datetime));
        } else {
            $timespan   = date_i18n($date_format, strtotime($start_datetime))." um ".date_i18n($time_format, strtotime($start_datetime))." - ".date_i18n($date_format, strtotime($end_datetime))." um ".date_i18n($time_format, strtotime($end_datetime));
            $date       = date_i18n($date_format, strtotime($start_datetime))." - ".date_i18n($date_format, strtotime($end_datetime));
            $time       = $timespan;
        }

        $media          = "";
        if(strpos($event["Veranstaltung"]["_event_LINK"], "https://youtu.be") !== false || strpos($event["Veranstaltung"]["_event_LINK"], "https://www.youtube.com") !== false) {
            $media     .= '<a target="_blank" title="YouTube" class="btn" style="background-color: #e52d27;" href="'.$event["Veranstaltung"]["_event_LINK"].'">zu YouTube</a><br/>';
        } elseif(strpos($event["Veranstaltung"]["_event_LINK"], ".zoom.us/j/") !== false) {
            if(date("Y-m-d", strtotime($end_datetime)) >= date("Y-m-d")) {
                $media     .= '<a target="_blank" title="Zoom" class="btn" style="background-color: #2D8CFF;" href="'.$event["Veranstaltung"]["_event_LINK"].'">zum Zoom-Meeting</a><br/>';
            } else {
                $media     .= '<a target="_blank" title="Zoom" class="btn" style="background-color: #2D8CFF;" href="#" disabled>das Zoom-Meeting ist schon vorbei</a><br/>';
            }
        } elseif(!empty($event["Veranstaltung"]["_event_LINK"])) {
            $media     .= '<a target="_blank" title="Webseite" class="btn" style="background-color: #e52d27;" href="'.$event["Veranstaltung"]["_event_LINK"].'">zur Veranstaltungs Seite</a><br/>';
        }

        if(!empty($event["Veranstaltung"]["_event_IMAGE"]))
            $media .= '<img src="' .$event["Veranstaltung"]["_event_IMAGE"] .'" alt="Event_Bild" class="alignright" width="414" height="auto">';
        elseif(!empty($event["Veranstaltung"]["_place_IMAGE"]))
            $media .= '<img src="' .$event["Veranstaltung"]["_place_IMAGE"] .'" alt="Event_Bild" class="alignright" width="414" height="auto">';

        $feedback_link  = (empty($event["Veranstaltung"]["_event_FEEDBACK_ID"]) ? "" : get_option("ev_kirchen_termine_webpage")."/rueckmeldeformular".$event["Veranstaltung"]["_event_FEEDBACK_ID"]."-".$event["Veranstaltung"]["ID"]);
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

        $ical_link      = get_option("ev_kirchen_termine_webpage")."/ical-". $event["Veranstaltung"]["ID"];

        $google_cal_link_parameter = array();
        $google_cal_link_parameter["action"] = "TEMPLATE";
        $google_cal_link_parameter["text"] = $event["Veranstaltung"]["_event_TITLE"];
        $google_cal_link_parameter["dates"] = date("Ymd\THis", strtotime($start_datetime))."/".date("Ymd\THis", strtotime($end_datetime));
        $google_cal_link_parameter["details"] = $event["Veranstaltung"]["_event_LONG_DESCRIPTION"];
        $google_cal_link_parameter["location"] = $event["Veranstaltung"]["_place_STREET_NR"].", ".$event["Veranstaltung"]["_place_ZIP"]." ".$event["Veranstaltung"]["_place_CITY"];
        $google_cal_link_parameter["trp"] = true;
        $google_cal_link_parameter["ctz"] = "Europe/Berlin";

        $google_cal_link = 'https://www.google.com/calendar/event?'.http_build_query($google_cal_link_parameter);


        $type           = implode(", ", explode(",", $event["Veranstaltung"]["_event_EVENTTYPE"]));
        $tags           = explode(",", $event["Veranstaltung"]["CHANNELS"]);
        foreach ($tags as $tag_key => $tag_value) {
            $tags[$tag_key] = '<a href="'.get_site_url().'/tag/'.sanitize_title($tag_value).'">'.$tag_value.'</a>';
        }
        $tags           = implode(", ", $tags);


        $place_name     = $event["Veranstaltung"]["_place_NAME"];
        $place_street   = $event["Veranstaltung"]["_place_STREET_NR"];
        $place_city     = $event["Veranstaltung"]["_place_CITY"];
        $place_zip      = $event["Veranstaltung"]["_place_ZIP"];

        $geo_long       = (float) str_replace(",", ".", $event["Veranstaltung"]["_place_GLONG"]);
        $geo_lat        = (float) str_replace(",", ".", $event["Veranstaltung"]["_place_GLAT"]);
        $zoom           = 0.002;
        $para["bbox"]   = ($geo_long+$zoom).",".($geo_lat+$zoom).",".($geo_long-$zoom).",".($geo_lat-$zoom);
        $para["layer"]  = "mapnik";
        $para["marker"] = $geo_lat.",".$geo_long ;
        $map_url        = 'https://www.openstreetmap.org/export/embed.html?'.http_build_query($para);

        $org_name       = $event["Veranstaltung"]["_user_REALNAME"];
        $org_contact    = $event["Veranstaltung"]["_user_CONTACT"];
        $org_url        = $event["Veranstaltung"]["_user_URL"];
        $org_email      = $event["Veranstaltung"]["_user_EMAIL"];

    		$event_tags		= explode(",", $event["Veranstaltung"]["CHANNELS"]);
    		if($event["Veranstaltung"]["_event_HIGHLIGHT"] !== "low")
    			array_push($event_tags, "Event-Highlight");

        $organizer      = "";
        if(!empty($event["Veranstaltung"]["_event_PERSON_ID"]))
            $organizer  = '<div class="evkite-events-meta-group evkite-events-meta-group-organizer">
                                <h2 class="evkite-events-single-section-title">Ansprechpartner</h2>
                                <dl>
                                    <dt style="display:none;"></dt>
                                    <dd class="evkite-organizer">
                                        <a >'.$event["Veranstaltung"]["_person_NAME"].'</a>
                                    </dd>
                                    <dt class="evkite-organizer-email-label">E-Mail:</dt>
                                    <dd class="evkite-organizer-email"><a href="mailto:'.$event["Veranstaltung"]["_person_EMAIL"].'">'.$event["Veranstaltung"]["_person_EMAIL"].'</a></dd>
                                    <dt class="evkite-organizer-contact-label">Kontakt:</dt>
                                    <dd class="evkite-organizer-contact">'.$event["Veranstaltung"]["_person_CONTACT"].'</dd>
                                </dl>
                            </div>';

        if(!empty($geo_long) && !empty($geo_lat))
            $map        = '<div class="evkite-events-venue-map">
                                <div id="evkite-events-gmap-0" style="height: 350px; width: 100%">
                                    <iframe width="100%" height="100%" frameborder="0" scrolling="no" marginheight="0" marginwidth="0" src="'.$map_url.'" style="border: 1px solid black"></iframe>
                                    <br/>
                                    <small>
                                        <a href="https://www.openstreetmap.org/?mlat='.$geo_lat.'&amp;mlon='.$geo_long.'#map=17/'.$geo_lat.'/'.$geo_long.'">Größere Karte anzeigen</a>
                                    </small>
                                </div>
                            </div>';

        $place_text = "";
        if(!empty($event["Veranstaltung"]["_place_ID"]) && $event["Veranstaltung"]["_place_ID"] !== "1")
            $place_text =  '<div class="evkite-events-single-section evkite-events-event-meta secondary evkite-clearfix">
                                <div class="evkite-events-meta-group evkite-events-meta-group-venue">
                                    <h2 class="evkite-events-single-section-title">Veranstaltungsort</h2>
                                    <dl>
                                        <dd class="evkite-venue">'.$place_name.'</dd>
                                        <dd class="evkite-venue-location">
                                            <address class="evkite-events-address">
                                                <span class="evkite-address">
                                                    <span class="evkite-street-address">'.$place_street.'</span>
                                                    <span class="evkite-postal-code">'.$place_zip.'</span><span class="evkite-delimiter">, </span><span class="evkite-locality">'.$place_city.'</span>
                                                </span>
                                                <a class="evkite-events-gmap" rel="noopener" href="https://maps.google.com/maps?f=q&#038;source=s_q&#038;hl=en&#038;geocode=&#038;q='.urlencode($place_street.', '.$place_zip.' '.$place_city).'" title="Klicken, um Google Karte anzuzeigen" target="_blank">+ Google Maps</a>
                                                <a class="evkite-events-gmap hide-on-non-ios" rel="noopener" href="http://maps.apple.com/?q='.urlencode($place_street.', '.$place_zip.' '.$place_city).'" title="Klicken, um Apple Karten anzuzeigen" target="_blank">+ Apple Karten</a>
                                            </address>
                                        </dd>
                                    </dl>
                                </div>
                                '.$map.'
                            </div>';



        $parameter_event_shema = array(
            "@context"=> "https://schema.org",
            "@type"=> "Event",
            "name"=> $event["Veranstaltung"]["_event_TITLE"],
            "startDate"=> $event["Veranstaltung"]["START_RFC"],
            "endDate"=> $event["Veranstaltung"]["END_RFC"],
            "description"=> addslashes($event["Veranstaltung"]["_event_LONG_DESCRIPTION"]),
            "location"=> array(),
            "image"=> array(),
            "sameAs"=> $ev_kirchen_termine_webpage. "/veranstaltung_detail". $event["Veranstaltung"]["ID"]. ".html",
        );

        // ermittlung ob Event abgesagt wurde anhand des Titels

        if(
            fnmatch("*entfällt", $event["Veranstaltung"]["_event_TITLE"]) ||
            fnmatch("*fällt*aus*", $event["Veranstaltung"]["_event_TITLE"]) ||
            fnmatch("*Corona-Pause*", $event["Veranstaltung"]["_event_TITLE"]) ||
            fnmatch("*geschlossen*", $event["Veranstaltung"]["_event_TITLE"])
        ) {
            $parameter_event_shema["eventStatus"] = "https://schema.org/EventCancelled";
        } else {
            $parameter_event_shema["eventStatus"] = "https://schema.org/EventScheduled";
        }

        // Veranstaltungsort

        if(!empty($event["Veranstaltung"]["_place_ID"]) && $event["Veranstaltung"]["_place_ID"] !== "1")
            $parameter_event_shema["location"][] = array(
                "@type"=> "Place",
                "name"=> $event["Veranstaltung"]["_place_NAME"],
                "address"=> array(
                    "@type"=> "PostalAddress",
                    "streetAddress"=> $event["Veranstaltung"]["_place_STREET_NR"],
                    "addressLocality"=> $event["Veranstaltung"]["_place_CITY"],
                    "postalCode"=> $event["Veranstaltung"]["_place_ZIP"],
                    "addressCountry"=> "DE"
                ),
                "geo"=> array(
                    "@type"=> "GeoCoordinates",
                    "latitude"=> $event["Veranstaltung"]["_place_GLAT"],
                    "longitude"=> $event["Veranstaltung"]["_place_GLONG"],
                ),
            );


        // Virtuelle veranstalltungen mit Youtube Link oder Zoom Link

        if(strpos($event["Veranstaltung"]["_event_LINK"], "https://youtu.be") !== false || strpos($event["Veranstaltung"]["_event_LINK"], "https://www.youtube.com") !== false)
            $parameter_event_shema["location"][] = array(
                "@type"=> "VirtualLocation",
                "url"=> $event["Veranstaltung"]["_event_LINK"],
            );

        if(strpos($event["Veranstaltung"]["_event_LINK"], ".zoom.us/j/") !== false)
            $parameter_event_shema["location"][] = array(
                "@type"=> "VirtualLocation",
                "url"=> $event["Veranstaltung"]["_event_LINK"],
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

        if(!empty($event["Veranstaltung"]["_event_IMAGE"]))
            $parameter_event_shema["image"][] = $event["Veranstaltung"]["_event_IMAGE"];

        if(!empty($event["Veranstaltung"]["_place_IMAGE"]))
            $parameter_event_shema["image"][] = $event["Veranstaltung"]["_place_IMAGE"];


        // Nasprechpartner der Veranstaltung

        if(!empty($event["Veranstaltung"]["_event_PERSON_ID"]))
            $parameter_event_shema["performer"] = array(
                "@type"=> "Person",
                "name"=> $event["Veranstaltung"]["_person_NAME"],
                "email"=> $event["Veranstaltung"]["_person_EMAIL"],
            );

        // Veranstalter

        if(!empty($event["Veranstaltung"]["_user_ID"]))
            $parameter_event_shema["organizer"] = array(
                "@type"=> "Organization",
                "name"=> $event["Veranstaltung"]["_user_REALNAME"],
                "description"=> $event["Veranstaltung"]["_user_DESCRIPTION"],
                "url"=> $event["Veranstaltung"]["_user_URL"],
                "email"=> $event["Veranstaltung"]["_user_EMAIL"],
                "address"=> array(
                    "@type"=> "PostalAddress",
                    "streetAddress"=> $event["Veranstaltung"]["_user_STREET_NR"],
                    "addressLocality"=> $event["Veranstaltung"]["_user_CITY"],
                    "postalCode"=> $event["Veranstaltung"]["_user_ZIP"],
                    "addressCountry"=> "DE"
                ),
                "logo"=> $event["Veranstaltung"]["_user_IMAGE"],
            );


        $args = array(
            'post_type'    => 'event',
            'post_status'  => 'publish',
            'post_excerpt' => $timespan.' / </br>'.$place_name.' / </br>mit '.$event["Veranstaltung"]["_person_NAME"],
            'post_content' =>
                '<script type="application/ld+json">'.json_encode($parameter_event_shema, JSON_UNESCAPED_UNICODE).'</script>
                <div id="evkite-events" class="evkite-no-js" data-live_ajax="1" data-datepicker_format="11" data-category="" data-featured="">
                    <div class="evkite-events-before-html"></div>
                    <div id="evkite-events-content" class="evkite-events-single">
                        <p class="evkite-events-back">
                            <a href="'.get_site_url().'/events/">&laquo;Alle Veranstaltungen</a>
                        </p>
                        <h1 class="evkite-events-single-event-title">'.$title.'</h1>
                        <div class="evkite-events-schedule evkite-clearfix">
                            <h2>'.$timespan.'</h2>
                        </div>
                        <div class="evkite_events type-evkite_events status-publish hentry">
                            <div class="evkite-events-single-event-description evkite-events-content">
                                <p>'.$media.$text.$feedback.'</p>
                            </div>
                            <div class="evkite-events-cal-links">
                                <a class="evkite-events-ical evkite-events-button" href="'.$ical_link.'" title=".ics Datei herunterladen">+ Exportiere iCal</a> <a target="_blank" class="evkite-events-gcal evkite-events-button" href="'.$google_cal_link.'" title="zu Google Kalender hinzufügen">zu Google Kalender hinzufügen</a>
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
                                            <a >'.$org_name.'</a>
                                        </dd>
                                        <dt class="evkite-organizer-tel-label">Kontakt:</dt>
                                        <dd class="evkite-organizer-tel">'.$org_contact.'</dd>
                                        <dt class="evkite-organizer-email-label">E-Mail:</dt>
                                        <dd class="evkite-organizer-email"><a href="mailto:'.$org_email.'">'.$org_email.'</a></dd>
                                        <dt class="evkite-organizer-url-label">Website:</dt>
                                        <dd class="evkite-organizer-url">
                                            <a href="'.$org_url.'" target="_self">'.$org_url.'</a>
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                            '.$place_text.'
                        </div>
                    </div>
                </div>',
            'post_title'   => $event["Veranstaltung"]["_event_TITLE"],
            'tags_input'   => $event_tags,
            'meta_input'   => array(
                '_ev_kirchen_termine_meta_key_start' => $start_datetime,
                '_ev_kirchen_termine_meta_key_end' => $end_datetime,
                '_ev_kirchen_termine_meta_key_id' => (int) $event["Veranstaltung"]["ID"],
                '_ev_kirchen_termine_meta_key_highlight' => ($event["Veranstaltung"]["_event_HIGHLIGHT"] !== "low"),
            )
        );

        $current_id = NULL;

        foreach ($old_posts_meta as $old_post_meta) {
            if($old_post_meta->meta_value == $event["Veranstaltung"]["ID"]) {
                $current_id = $old_post_meta->post_id;
            }
        }

        if(empty($current_id)) {
            $args['post_name'] = date("Y-m-d", strtotime($start_datetime)) ." - " .$event["Veranstaltung"]["_event_TITLE"];
            $id = wp_insert_post($args);
        } elseif(
            get_date_from_gmt( date("Y-m-d H:i:s", filemtime(__FILE__)), 'Y-m-d H:i:s' ) > get_the_modified_date("Y-m-d H:i:s", $current_id) ||
            date("Y-m-d H:i:s", strtotime($event["Veranstaltung"]["_event_MODIFIED"])) > get_the_modified_date("Y-m-d H:i:s", $current_id)
        ) {
            $args["ID"] = $current_id;
            wp_update_post( $args );
            wp_set_post_tags($current_id, $args["tags_input"]);
        }

    }

    add_filter('content_save_pre', 'wp_filter_post_kses');
    add_filter('content_filtered_save_pre', 'wp_filter_post_kses');

}
