<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

add_filter( 'the_content', 'ev_kirchen_termine_add_event_postmeta_to_content', 5 );

function ev_kirchen_termine_add_event_postmeta_to_content( $content ) {

    // Check if we're inside the main loop in a single Post.

    global $post;
    if ($post->post_type == 'evkite_event') {

        // remove auto <p>
        remove_filter( 'the_content', 'wpautop' );

        if(get_option("ev_kirchen_termine_show_share_icons")) {
            $content = $content . ev_kirchen_termine_add_postmeta_to_event($post->ID);
        }

    }

    return $content;

}


function ev_kirchen_termine_add_postmeta_to_event($post_id) {

    $post_meta = get_post_meta($post_id);

    if ( empty( $post_meta["_ev_kirchen_termine_meta_key_location_json"][0] ) ) {
        return "";
    }

    $location = maybe_unserialize($post_meta["_ev_kirchen_termine_meta_key_location_json"][0]);

    if ( empty( $location['name'] ) || 'Ohne Ort' === $location['name'] ) {
        return "";
    }

    $geo_long       = (float) str_replace( ",", ".", isset( $location["longitude"] ) ? $location["longitude"] : 0 );
    $geo_lat        = (float) str_replace( ",", ".", isset( $location["latitude"] ) ? $location["latitude"] : 0 );
    $zoom           = 0.002;

    // OSM Embed URL generieren
    $para = array(
        "bbox"   => ( $geo_long + $zoom ) . "," . ( $geo_lat + $zoom ) . "," . ( $geo_long - $zoom ) . "," . ( $geo_lat - $zoom ),
        "layer"  => "mapnik",
        "marker" => $geo_lat . "," . $geo_long,
    );
    $osm_iframe_url = 'https://www.openstreetmap.org/export/embed.html?' . http_build_query($para);
    
    // Google Embed URL generieren
    $address_query = ( isset( $location["streetAddress"] ) ? $location["streetAddress"] : '' ) . ', ' . 
                     ( isset( $location["postalCode"] ) ? $location["postalCode"] : '' ) . ' ' . 
                     ( isset( $location["city"] ) ? $location["city"] : '' );

    $para = array(
        "width" => 520,
        "height" => 400,
        "q" => $address_query,
        "z" => 15,
        "iwloc" => "B",
        "output" => "embed",
    );
    $google_iframe_url = "https://maps.google.com/maps?".http_build_query($para);

    $osm_map_url = 'https://www.openstreetmap.org/?mlat='.$geo_lat.'&amp;mlon='.$geo_long.'#map=17/'.$geo_lat.'/'.$geo_long;

    $google_map_url = 'https://maps.google.com/maps?q='. urlencode($address_query);

    $para = array(
        "size" => "680x350",
        "markers" => "color:red|label:|" . $geo_lat . "," . $geo_long,
        "zoom" => 15,
        "key" => get_option("ev_kirchen_termine_google_maps_api_key"),
    );
    $google_image_url = "https://maps.googleapis.com/maps/api/staticmap?".http_build_query($para);

    $map     = "";
    $map_url = $osm_map_url;

    switch(get_option("ev_kirchen_termine_map_type")) {
        case "google_iframe":
            $map = '<iframe width="100%" height="100%" frameborder="0" scrolling="no" marginheight="0" marginwidth="0" src="'.esc_url($google_iframe_url).'" style="border: 1px solid black"></iframe>';
            $map_url = $google_map_url;
            break;
        case "google_image":
            $map = '<a href="'.esc_url($google_map_url).'"><img width="100%" height="100%" frameborder="0" src="'.esc_url($google_image_url).'" style="border: 1px solid black"></a>';
            $map_url = $google_map_url;
            break;
        case "osm_iframe":
            $map = '<iframe width="100%" height="100%" frameborder="0" scrolling="no" marginheight="0" marginwidth="0" src="'.esc_url($osm_iframe_url).'" style="border: 1px solid black"></iframe>';
            $map_url = $osm_map_url;
            break;
        case "osm_image":
            $map = "";
            $map_url = $osm_map_url;

    }

    $barrier_free = "";
    $equipped     = ! empty( $location["equipped"] ) ? explode( ",", $location["equipped"] ) : array();
    $plugin_img_url = plugins_url( 'public/img/', dirname(__FILE__) ); // Best Practice für Plugin-Pfade

    if(in_array("1", $equipped)) {
        $barrier_free .= '<img class="equipicon" title="Rollstuhlgerecht" alt="Rollstuhlgerecht" src="' . esc_url( $plugin_img_url . '1_rollstuhlgerecht.png' ) . '">';
    }
    if(in_array("2", $equipped)) {
        $barrier_free .= '<img class="equipicon" title="Induktionsanlage für Hörgeräte" alt="Induktionsanlage" src="' . esc_url( $plugin_img_url . '2_induktion.png' ) . '">';
    }
    if(in_array("3", $equipped)) {
        $barrier_free .= '<img class="equipicon" title="Behinderten-WC" alt="Behinderten-WC" src="' . esc_url( $plugin_img_url . '3_behinderten-wc.png' ) . '">';
    }
    if(in_array("4", $equipped)) {
        $barrier_free .= '<img class="equipicon" title="Behindertenparkplatz" alt="Behindertenparkplatz" src="' . esc_url( $plugin_img_url . '4_behindertenparkplatz.png' ) . '">';
    }
    if(!empty($location["equipped_text"])) {
        $barrier_free .= '<p class="equipicon">' . esc_html( $location["equipped_text"] ) . '</p>';
    }

    if(!empty($barrier_free)) {
        $barrier_free = '<h3 class="evkite-events-single-section-title">Angaben zur Barrierefreiheit</h3><div>' .$barrier_free.'</div>';
    } else {
        $barrier_free = '<h3 class="evkite-events-single-section-title">Keine Angaben zur Barrierefreiheit</h3>';
    }


    return '<div class="evkite-events-single-section evkite-events-event-meta secondary evkite-clearfix">
                        <div class="evkite-events-meta-group evkite-events-meta-group-venue">
                            <h2 class="evkite-events-single-section-title">Veranstaltungsort</h2>
                            <dl>
                                <dd class="evkite-venue">' . esc_html( $location["name"] ) . '</dd>
                                <dd class="evkite-venue-location">
                                    <address class="evkite-events-address">
                                        <span class="evkite-address">
                                            <span class="evkite-street-address">' . esc_html( $location["streetAddress"] ) . '</span></br>
                                            <span class="evkite-postal-code">' . esc_html( $location["postalCode"] ) . '</span><span class="evkite-delimiter">, </span><span class="evkite-locality">' . esc_html( $location["city"] ) . '</span>
                                        </span>
                                        </br></br>
                                        <a class="evkite-events-gmap" rel="noopener" href="' . esc_url( $google_map_url ) . '" title="Klicken, um Google Karte anzuzeigen" target="_blank">+ Google Maps</a>
                                        <a class="evkite-events-gmap hide-on-non-ios" rel="noopener" href="' . esc_url( 'maps://maps.apple.com/?q=' . urlencode( $address_query ) ) . '" title="Klicken, um Apple Karten anzuzeigen" target="_blank">+ Apple Karten</a>
                                    </address>
                                </dd>
                            </dl>
                            '.$barrier_free.'
                        </div>
                        <div class="evkite-events-venue-map">
                            <div id="evkite-events-gmap-0" style="height: 350px; width: 100%">
                                '.$map.'
                                <br/>
                                <small>
                                    <a href="' . esc_url( $map_url ) . '">Größere Karte anzeigen</a>
                                </small>
                            </div>
                        </div>
                    </div>';

}
