<?php

add_filter( 'the_content', 'add_event_postmeta_to_content', 5 );

function add_event_postmeta_to_content( $content ) {

    // Check if we're inside the main loop in a single Post.

    global $post;
    if ($post->post_type == 'event') {

        // remove auto <p>
        remove_filter( 'the_content', 'wpautop' );

        if(get_option("ev_kirchen_termine_show_share_icons")) {
            $content = $content . add_postmeta_to_event($post->ID);
        }

    }

    return $content;

}


function add_postmeta_to_event($post_id) {

    $post_meta = get_post_meta($post_id);

    $location = maybe_unserialize($post_meta["_ev_kirchen_termine_meta_key_location_json"][0]);

    if(empty($location["name"]))
        return "";

    $geo_long       = (float) str_replace(",", ".", $location["longitude"]);
    $geo_lat        = (float) str_replace(",", ".", $location["latitude"]);
    $zoom           = 0.002;
    $para["bbox"]   = ($geo_long+$zoom).",".($geo_lat+$zoom).",".($geo_long-$zoom).",".($geo_lat-$zoom);
    $para["layer"]  = "mapnik";
    $para["marker"] = $geo_lat.",".$geo_long ;
    $osm_iframe_url    = 'https://www.openstreetmap.org/export/embed.html?'.http_build_query($para);


    $para = array(
        "width" => 520,
        "height" => 400,
        "q" => $location["streetAddress"].', '.$location["postalCode"].' '.$location["city"],
        "z" => 15,
        "iwloc" => "B",
        "output" => "embed",
    );
    $google_iframe_url = "https://maps.google.com/maps?".http_build_query($para);

    $osm_map_url = 'https://www.openstreetmap.org/?mlat='.$geo_lat.'&amp;mlon='.$geo_long.'#map=17/'.$geo_lat.'/'.$geo_long;

    $google_map_url = 'https://maps.google.com/maps?q='.urlencode($location["streetAddress"].', '.$location["postalCode"].' '.$location["city"]);

    $para = array(
        "size" => "680x350",
        "markers" => "color:red|label:|$geo_lat,$geo_long",
        "zoom" => 15,
        "key" => get_option("ev_kirchen_termine_google_maps_api_key"),
    );
    $google_image_url = "https://maps.googleapis.com/maps/api/staticmap?".http_build_query($para);

    switch(get_option("ev_kirchen_termine_map_type")) {
        case "google_iframe":
            $map = '<iframe width="100%" height="100%" frameborder="0" scrolling="no" marginheight="0" marginwidth="0" src="'.$google_iframe_url.'" style="border: 1px solid black"></iframe>';
            $map_url = $google_map_url;
            break;
        case "google_image":
            $map = '<a href="'.$google_map_url.'"><img width="100%" height="100%" frameborder="0" src="'.$google_image_url.'" style="border: 1px solid black"></a>';
            $map_url = $google_map_url;
            break;
        case "osm_iframe":
            $map = '<iframe width="100%" height="100%" frameborder="0" scrolling="no" marginheight="0" marginwidth="0" src="'.$osm_iframe_url.'" style="border: 1px solid black"></iframe>';
            $map_url = $osm_map_url;
            break;
        case "osm_image":
            $map = "";
            $map_url = $osm_map_url;

    }

    return '<div class="evkite-events-single-section evkite-events-event-meta secondary evkite-clearfix">
                        <div class="evkite-events-meta-group evkite-events-meta-group-venue">
                            <h2 class="evkite-events-single-section-title">Veranstaltungsort</h2>
                            <dl>
                                <dd class="evkite-venue">'.$location["name"].'</dd>
                                <dd class="evkite-venue-location">
                                    <address class="evkite-events-address">
                                        <span class="evkite-address">
                                            <span class="evkite-street-address">'.$location["streetAddress"].'</span></br>
                                            <span class="evkite-postal-code">'.$location["postalCode"].'</span><span class="evkite-delimiter">, </span><span class="evkite-locality">'.$location["city"].'</span>
                                        </span>
                                        </br></br>
                                        <a class="evkite-events-gmap" rel="noopener" href="'.$google_map_url.'" title="Klicken, um Google Karte anzuzeigen" target="_blank">+ Google Maps</a>
                                        <a class="evkite-events-gmap hide-on-non-ios" rel="noopener" href="http://maps.apple.com/?q='.urlencode($location["streetAddress"].', '.$location["postalCode"].' '.$location["city"]).'" title="Klicken, um Apple Karten anzuzeigen" target="_blank">+ Apple Karten</a>
                                    </address>
                                </dd>
                            </dl>
                        </div>
                        <div class="evkite-events-venue-map">
                            <div id="evkite-events-gmap-0" style="height: 350px; width: 100%">
                                '.$map.'
                                <br/>
                                <small>
                                    <a href="'.$map_url.'">Größere Karte anzeigen</a>
                                </small>
                            </div>
                        </div>
                    </div>';

}
