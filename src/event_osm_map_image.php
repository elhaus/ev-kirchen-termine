<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 *
 * OpenStreetMap map image
 *
 * Renders a static map image of an event location from OpenStreetMap tiles on the
 * server and stores it in the media library. The visitor's browser only loads the
 * local image, no connection to OpenStreetMap is established.
 *
 * Every location is rendered only once and cached as attachment, so only a few tiles
 * are requested (see https://operations.osmfoundation.org/policies/tiles/).
 *
**/

const EV_KIRCHEN_TERMINE_OSM_ZOOM       = 17;
const EV_KIRCHEN_TERMINE_OSM_WIDTH      = 680;
const EV_KIRCHEN_TERMINE_OSM_HEIGHT     = 350;
const EV_KIRCHEN_TERMINE_OSM_TILE_URL   = 'https://tile.openstreetmap.org/%d/%d/%d.png';
const EV_KIRCHEN_TERMINE_OSM_TILE_SIZE  = 256;


/**
 * Returns true if the map of the event location should be shown as locally stored OpenStreetMap image.
 */
function ev_kirchen_termine_use_osm_map_image() {
    return get_option("ev_kirchen_termine_map_type") === "osm_image";
}

/**
 * Converts a coordinate of the event database (may use a comma as decimal separator) to float.
 */
function ev_kirchen_termine_parse_coordinate($value) {
    return (float) str_replace(",", ".", (string) $value);
}

/**
 * Returns the cache key of a map image for the given coordinates.
 */
function ev_kirchen_termine_osm_map_key($lat, $lon) {
    return sprintf('%.6F,%.6F,%d,%dx%d', $lat, $lon, EV_KIRCHEN_TERMINE_OSM_ZOOM, EV_KIRCHEN_TERMINE_OSM_WIDTH, EV_KIRCHEN_TERMINE_OSM_HEIGHT);
}

/**
 * Returns the attachment id of the map image for the given coordinates.
 * The image is rendered and stored in the media library if it does not exist yet.
 * Returns 0 if the image could not be created.
 */
function ev_kirchen_termine_get_osm_map_image_id($lat, $lon, $place_name = "") {

    $lat = ev_kirchen_termine_parse_coordinate($lat);
    $lon = ev_kirchen_termine_parse_coordinate($lon);

    if(empty($lat) || empty($lon) || !function_exists('imagecreatetruecolor'))
        return 0;

    $key = ev_kirchen_termine_osm_map_key($lat, $lon);

    // many events share the same location, look up every location only once per import
    static $cache = array();
    if(isset($cache[$key]))
        return $cache[$key];

    $existing = get_posts(array(
        'post_type'      => 'attachment',
        'post_status'    => 'inherit',
        'posts_per_page' => 1,
        'fields'         => 'ids',
        'meta_key'       => '_ev_kirchen_termine_osm_map_key', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- runs during the import only
        'meta_value'     => $key, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- runs during the import only
    ));

    if(!empty($existing))
        return $cache[$key] = (int) $existing[0];

    $image = ev_kirchen_termine_render_osm_map($lat, $lon);
    if(!$image)
        return 0;

    $tmp_file = wp_tempnam('osm-map');
    $saved    = imagepng($image, $tmp_file, 9);
    imagedestroy($image);

    if(!$saved) {
        wp_delete_file($tmp_file);
        return 0;
    }

    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';

    /* translators: %s: location name */
    $title = !empty($place_name) ? sprintf(__('Map: %s', 'ev-kirchen-termine'), $place_name) : __('Map', 'ev-kirchen-termine');

    $attachment_id = media_handle_sideload(
        array(
            'name'     => 'karte-'.sanitize_title(!empty($place_name) ? $place_name : $key).'.png',
            'tmp_name' => $tmp_file,
        ),
        0,
        $title
    );

    if(is_wp_error($attachment_id)) {
        wp_delete_file($tmp_file);
        return 0;
    }

    update_post_meta($attachment_id, '_ev_kirchen_termine_osm_map_key', $key);
    update_post_meta($attachment_id, '_wp_attachment_image_alt', $title);

    return $cache[$key] = (int) $attachment_id;
}

/**
 * Deletes map images which are not used by any of the current events anymore.
 */
function ev_kirchen_termine_delete_unused_osm_map_images($used_attachment_ids) {

    $attachments = get_posts(array(
        'post_type'      => 'attachment',
        'post_status'    => 'inherit',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'meta_key'       => '_ev_kirchen_termine_osm_map_key', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- runs during the import only
    ));

    foreach ($attachments as $attachment_id) {
        if(!in_array((int) $attachment_id, $used_attachment_ids, true))
            wp_delete_attachment($attachment_id, true);
    }
}

/**
 * Loads a single OpenStreetMap tile. Returns a GD image or false.
 */
function ev_kirchen_termine_load_osm_tile($zoom, $x, $y) {

    $response = wp_remote_get(
        sprintf(EV_KIRCHEN_TERMINE_OSM_TILE_URL, $zoom, $x, $y),
        array(
            'timeout'    => 20,
            // the tile usage policy requires a user agent which identifies the application
            'user-agent' => 'WordPress-Plugin ev-kirchen-termine/'.EV_KIRCHEN_TERMINE_VERSION.' (+'.home_url('/').')',
            'headers'    => array( 'Referer' => home_url('/') ),
        )
    );

    if(is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200)
        return false;

    return @imagecreatefromstring(wp_remote_retrieve_body($response));
}

/**
 * Renders a map image centered on the given coordinates with a marker. Returns a GD image or false.
 */
function ev_kirchen_termine_render_osm_map($lat, $lon) {

    $zoom      = EV_KIRCHEN_TERMINE_OSM_ZOOM;
    $width     = EV_KIRCHEN_TERMINE_OSM_WIDTH;
    $height    = EV_KIRCHEN_TERMINE_OSM_HEIGHT;
    $tile_size = EV_KIRCHEN_TERMINE_OSM_TILE_SIZE;
    $max_tile  = (1 << $zoom) - 1;

    // position of the location in global pixel coordinates (Web Mercator)
    $world_size = $tile_size * (1 << $zoom);
    $lat_rad    = deg2rad($lat);
    $center_x   = ($lon + 180) / 360 * $world_size;
    $center_y   = (1 - log(tan($lat_rad) + 1 / cos($lat_rad)) / M_PI) / 2 * $world_size;

    $left = (int) floor($center_x - $width / 2);
    $top  = (int) floor($center_y - $height / 2);

    $image = imagecreatetruecolor($width, $height);
    imagefill($image, 0, 0, imagecolorallocate($image, 242, 239, 233));

    for($tile_x = intdiv($left, $tile_size); $tile_x * $tile_size < $left + $width; $tile_x++) {
        for($tile_y = intdiv($top, $tile_size); $tile_y * $tile_size < $top + $height; $tile_y++) {

            if($tile_x < 0 || $tile_y < 0 || $tile_x > $max_tile || $tile_y > $max_tile)
                continue;

            $tile = ev_kirchen_termine_load_osm_tile($zoom, $tile_x, $tile_y);
            if(!$tile) {
                imagedestroy($image);
                return false;
            }

            imagecopy($image, $tile, $tile_x * $tile_size - $left, $tile_y * $tile_size - $top, 0, 0, $tile_size, $tile_size);
            imagedestroy($tile);
        }
    }

    ev_kirchen_termine_draw_map_marker($image, (int) round($center_x - $left), (int) round($center_y - $top));

    return $image;
}

/**
 * Draws a map pin whose tip points to the given position.
 */
function ev_kirchen_termine_draw_map_marker($image, $x, $y) {

    $red   = imagecolorallocate($image, 200, 29, 38);
    $dark  = imagecolorallocate($image, 110, 10, 16);
    $white = imagecolorallocate($image, 255, 255, 255);

    // outline
    imagefilledellipse($image, $x, $y - 24, 28, 28, $dark);
    ev_kirchen_termine_filled_triangle($image, array($x - 12, $y - 18, $x + 12, $y - 18, $x, $y), $dark);

    // pin
    imagefilledellipse($image, $x, $y - 24, 24, 24, $red);
    ev_kirchen_termine_filled_triangle($image, array($x - 10, $y - 18, $x + 10, $y - 18, $x, $y - 2), $red);

    imagefilledellipse($image, $x, $y - 24, 9, 9, $white);
}

/**
 * imagefilledpolygon() without the deprecated $num_points argument is only available since PHP 8.0.
 */
function ev_kirchen_termine_filled_triangle($image, $points, $color) {
    if(PHP_VERSION_ID >= 80000)
        imagefilledpolygon($image, $points, $color);
    else
        imagefilledpolygon($image, $points, 3, $color);
}
