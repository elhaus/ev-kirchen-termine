<?php

function setup_ev_kirchen_termine_admin_menus() {
    add_submenu_page('options-general.php',
    'Event Settings', 'Ev. Kirchen Termine Einstellungen', 'manage_options',
    'ev_kirchen_termine_settings', 'ev_kirchen_termine_manage_settings');
}

function ev_kirchen_termine_manage_settings() {
    if (!current_user_can('manage_options')) {
        wp_die('You do not have sufficient permissions to access this page.');
    }
    ?>
        <div class="wrap">
            <h2><?php esc_html_e("Ev. Kirchen Termine - settings", 'ev-kirchen-termine'); ?></h2>
    <?php
    if (isset($_POST["update_settings"])) {

        $ev_kirchen_termine_webpage = esc_attr($_POST["ev_kirchen_termine_webpage"]);
        update_option("ev_kirchen_termine_webpage", $ev_kirchen_termine_webpage);

        $ev_kirchen_termine_vid = esc_attr($_POST["ev_kirchen_termine_vid"]);
        update_option("ev_kirchen_termine_vid", $ev_kirchen_termine_vid);

        $ev_kirchen_termine_vid_eventtype_filter = esc_attr($_POST["ev_kirchen_termine_vid_eventtype_filter"]);
        update_option("ev_kirchen_termine_vid_eventtype_filter", $ev_kirchen_termine_vid_eventtype_filter);

        $ev_kirchen_termine_region = esc_attr($_POST["ev_kirchen_termine_region"]);
        update_option("ev_kirchen_termine_region", $ev_kirchen_termine_region);

        $ev_kirchen_termine_region_eventtype_filter = esc_attr($_POST["ev_kirchen_termine_region_eventtype_filter"]);
        update_option("ev_kirchen_termine_region_eventtype_filter", $ev_kirchen_termine_region_eventtype_filter);

        $ev_kirchen_termine_custom_filter = esc_attr($_POST["ev_kirchen_termine_custom_filter"]);
        update_option("ev_kirchen_termine_custom_filter", $ev_kirchen_termine_custom_filter);

        $ev_kirchen_termine_event_template = esc_attr($_POST["ev_kirchen_termine_event_template"]);
        update_option("ev_kirchen_termine_event_template", $ev_kirchen_termine_event_template);

        $ev_kirchen_termine_map_type = esc_attr($_POST["ev_kirchen_termine_map_type"]);
        update_option("ev_kirchen_termine_map_type", $ev_kirchen_termine_map_type);

        $ev_kirchen_termine_google_maps_api_key = esc_attr($_POST["ev_kirchen_termine_google_maps_api_key"]);
        update_option("ev_kirchen_termine_google_maps_api_key", $ev_kirchen_termine_google_maps_api_key);

        $ev_kirchen_termine_show_events_in_search = isset($_POST["ev_kirchen_termine_show_events_in_search"]) ? 1 : 0;
        update_option("ev_kirchen_termine_show_events_in_search", $ev_kirchen_termine_show_events_in_search);

        $ev_kirchen_termine_show_share_icons = isset($_POST["ev_kirchen_termine_show_share_icons"]) ? 1 : 0;
        update_option("ev_kirchen_termine_show_share_icons", $ev_kirchen_termine_show_share_icons);

        $ev_kirchen_termine_show_share_icons = isset($_POST["ev_kirchen_termine_show_feedback_count"]) ? 1 : 0;
        update_option("ev_kirchen_termine_show_feedback_count", $ev_kirchen_termine_show_share_icons);

    }
    ?>
            <form method="POST" action="">
                <p>Für Webseiten von Kirchengemeinden tragen Sie bitte die vid ein und lassen Sie das Feld region leer. Für Webseiten von Kirchenkreisen tragen Sie in vid "all" ein und füllen Sie region entsprechend des Kirchenkreises aus.</p>
                <p>Der Katergorie Filter bezeiht sich auf den "eventtype". Lassen Sie das Feld leer wir nicht weiter gefiltert, tragen Sie z.B. 1 ein werden nur Gottesdienste importiert. Genauere Angaben unter <a href="http://handbuch.evangelische-termine.de/anzeige-im-internet/ausgabe-parameter">http://handbuch.evangelische-termine.de/anzeige-im-internet/ausgabe-parameter</a></p>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">
                            <label for="ev_kirchen_termine_webpage">
                                <?php esc_html_e("Ev. Termine website:", 'ev-kirchen-termine'); ?>
                            </label>
                        </th>
                        <td>
                            <input type="text" name="ev_kirchen_termine_webpage" size="25" value="<?php echo get_option("ev_kirchen_termine_webpage"); ?>" />
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                            <label for="ev_kirchen_termine_vid">
                                <?php esc_html_e("Organizer ID [vid]:", 'ev-kirchen-termine'); ?>
                            </label>
                        </th>
                        <td>
                            <input type="text" name="ev_kirchen_termine_vid" size="25" value="<?php echo get_option("ev_kirchen_termine_vid"); ?>" />
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                            <label for="ev_kirchen_termine_vid_eventtype_filter">
                                <?php esc_html_e("Category Filter", 'ev-kirchen-termine'); ?> (vid) [eventtype]:
                            </label>
                        </th>
                        <td>
                            <input type="text" name="ev_kirchen_termine_vid_eventtype_filter" size="25" value="<?php echo get_option("ev_kirchen_termine_vid_eventtype_filter"); ?>" />
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                            <label for="ev_kirchen_termine_region">
                                <?php esc_html_e("Church district/deanery number", 'ev-kirchen-termine'); ?> [region]:
                            </label>
                        </th>
                        <td>
                            <input type="text" name="ev_kirchen_termine_region" size="25" value="<?php echo get_option("ev_kirchen_termine_region"); ?>" />
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                            <label for="ev_kirchen_termine_region_eventtype_filter">
                                <?php esc_html_e("Category Filter", 'ev-kirchen-termine'); ?> (region) [eventtype]:
                            </label>
                        </th>
                        <td>
                            <input type="text" name="ev_kirchen_termine_region_eventtype_filter" size="25" value="<?php echo get_option("ev_kirchen_termine_region_eventtype_filter"); ?>" />
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                            <label for="ev_kirchen_termine_custom_filter">
                                <?php esc_html_e("Custom Filter", 'ev-kirchen-termine'); ?>:
                            </label>
                        </th>
                        <td>
                            <input type="text" name="ev_kirchen_termine_custom_filter" size="40" value="<?php echo get_option("ev_kirchen_termine_custom_filter"); ?>" />
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                            <label for="ev_kirchen_termine_event_template"><?php esc_html_e("Event Pages Template:", 'ev-kirchen-termine'); ?></label>
                        </th>
                        <td>
                            <select name="ev_kirchen_termine_event_template" id="ev_kirchen_termine_event_template">
                                <option value="">Default</option>"
                                <?php
                                $templates = get_page_templates();
                                foreach ($templates as $template) {
                                    $selected = "";
                                    if($template == get_option("ev_kirchen_termine_event_template")) $selected = " selected";
                                    echo "<option value='$template'$selected>$template</option>";
                                }
                                ?>
                            </select>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                            <label for="ev_kirchen_termine_map_type"><?php _e("Map type:", 'ev-kirchen-termine'); ?></label>
                        </th>
                        <td>
                            <select name="ev_kirchen_termine_map_type" id="ev_kirchen_termine_map_type">
                                <?php
                                $templates = array(
                                    "google_iframe" => __("Google Maps iframe", 'ev-kirchen-termine'),
                                    "google_image" => __("Google Maps image", 'ev-kirchen-termine'),
                                    "osm_iframe" => __("OpenStreetMap iframe", 'ev-kirchen-termine'),
                                    //"osm_image" => __("OpenStreetMap image", 'ev-kirchen-termine')
                                );
                                foreach ($templates as $template_key => $template_name) {
                                    $selected = "";
                                    if($template_key == get_option("ev_kirchen_termine_map_type")) $selected = " selected";
                                    echo "<option value='$template_key'$selected>$template_name</option>";
                                }
                                ?>
                            </select>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                            <label for="ev_kirchen_termine_google_maps_api_key">
                                <?php _e("Google Maps API key", 'ev-kirchen-termine'); ?>:
                            </label>
                        </th>
                        <td>
                            <input type="text" name="ev_kirchen_termine_google_maps_api_key" size="64" value="<?php echo get_option("ev_kirchen_termine_google_maps_api_key"); ?>" />
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                            <?php _e("Other settings:", 'ev-kirchen-termine'); ?>
                        </th>
                        <td>
                            <label for="ev_kirchen_termine_show_share_icons">
                                <input name="ev_kirchen_termine_show_share_icons" type="checkbox" id="ev_kirchen_termine_show_share_icons" <?php
                                    if(get_option("ev_kirchen_termine_show_share_icons")) {
                                        echo "checked";
                                    } ?>>
                                <?php _e("Enable Share links on event page", 'ev-kirchen-termine'); ?>
                            </label></br>
                            <label for="ev_kirchen_termine_show_feedback_count">
                                <input name="ev_kirchen_termine_show_feedback_count" type="checkbox" id="ev_kirchen_termine_show_feedback_count" <?php
                                    if(get_option("ev_kirchen_termine_show_feedback_count")) {
                                        echo "checked";
                                    } ?>>
                                <?php _e("registration form with number of remaining places", 'ev-kirchen-termine'); ?>
                            </label></br>
                            <label for="ev_kirchen_termine_show_events_in_search">
                                <input name="ev_kirchen_termine_show_events_in_search" type="checkbox" id="ev_kirchen_termine_show_events_in_search" <?php
                                    if(get_option("ev_kirchen_termine_show_events_in_search")) {
                                        echo "checked";
                                    } ?>>
                                <?php esc_html_e("Show Events in Search", 'ev-kirchen-termine'); ?>
                            </label>
                        </td>
                    </tr>
                </table>
                <input type="hidden" name="update_settings" value="Y" />
                <p>
                    <input type="submit" value="<?php esc_html_e("save settings", 'ev-kirchen-termine'); ?>" class="button-primary"/>
                </p>
            </form>
        </div>
    <?php
}

// This tells WordPress to call the function named "setup_theme_admin_menus"
// when it's time to create the menu pages.
add_action("admin_menu", "setup_ev_kirchen_termine_admin_menus");





/**
*
* Registration unseres Custom Post Types "Event"
*
*/
function ev_kirchen_termine_custom_post_type() {
    $labels = array(
        'name' => 'Evagelische Termine',
        'singular_name' => 'Veranstaltung',
        'menu_name' => 'Evagelische Termine',
        'parent_item_colon' => '',
        'all_items' => 'Alle Veranstaltungen',
        'view_item' => 'Veranstaltung ansehen',
        'add_new_item' => 'Neue Veranstaltung',
        'add_new' => 'Veranstaltung hinzufügen',
        'edit_item' => 'Veranstaltung bearbeiten',
        'update_item' => 'Veranstaltung aktualisieren',
        'search_items' => '',
        'not_found' => '',
        'not_found_in_trash' => '',
    );
    $rewrite = array(
        'slug' => 'event',
        'with_front' => true,
        'pages' => true,
        'feeds' => true,
    );
    $args = array(
        'labels' => $labels,
        'supports' => array( 'title', 'editor', 'excerpt', 'thumbnail', ),
        'taxonomies' => array( 'post_tag' ),
        'hierarchical' => false,
        'public' => true,
        'show_ui' => true,
        'show_in_menu' => false,
        'show_in_nav_menus' => true,
        'show_in_rest' => true,
        'show_in_admin_bar' => false,
        'menu_position' => 5,
        'can_export' => true,
        'has_archive' => false,
        'exclude_from_search' => !get_option("ev_kirchen_termine_show_events_in_search"),
        'publicly_queryable' => true,
        'rewrite' => $rewrite,
    );
    register_post_type( 'event', $args );
}
// Hook into the 'init' action
add_action( 'init', 'ev_kirchen_termine_custom_post_type', 0 );



abstract class ev_kirchen_termine_Meta_Box {
    /**
     * Set up and add the meta box.
     */
    public static function add() {
        $screens = [ 'event' ];
        foreach ( $screens as $screen ) {
            add_meta_box(
                'event_data',          // Unique ID
                'Veranstaltungsdaten', // Box title
                [ self::class, 'html' ],   // Content callback, must be of type callable
                $screen                  // Post type
            );
        }
    }


    /**
     * Save the meta box selections.
     *
     * @param int $post_id  The post ID.
     */
    public static function save( int $post_id ) {
        if ( array_key_exists( 'event_start', $_POST ) ) {
            update_post_meta(
                $post_id,
                '_ev_kirchen_termine_meta_key_start',
                date("Y-m-d H:i:s", strtotime($_POST['event_start']))
            );
        }
        if ( array_key_exists( 'event_end', $_POST ) ) {
            update_post_meta(
                $post_id,
                '_ev_kirchen_termine_meta_key_end',
                date("Y-m-d H:i:s", strtotime($_POST['event_end']))
            );
        }
        if ( array_key_exists( 'event_id', $_POST ) ) {
            update_post_meta(
                $post_id,
                '_ev_kirchen_termine_meta_key_id',
                $_POST['event_id']
            );
        }
        if ( array_key_exists( 'event_vid', $_POST ) ) {
            update_post_meta(
                $post_id,
                '_ev_kirchen_termine_meta_key_vid',
                $_POST['event_vid']
            );
        }
    }


    /**
     * Display the meta box HTML to the user.
     *
     * @param \WP_Post $post   Post object.
     */
    public static function html( $post ) {
        $event_start = date("Y-m-d\TH:i", strtotime(get_post_meta( $post->ID, '_ev_kirchen_termine_meta_key_start', true )));
        $event_end = date("Y-m-d\TH:i", strtotime(get_post_meta( $post->ID, '_ev_kirchen_termine_meta_key_end', true )));
        $event_id = get_post_meta( $post->ID, '_ev_kirchen_termine_meta_key_id', true );
        $event_vid = get_post_meta( $post->ID, '_ev_kirchen_termine_meta_key_vid', true );
        ?>
        <table class="form-table">
            <tr valign="top">
                <th scope="row">
                    <label for="event_start">Veranstaltungsbeginn</label>
                </th>
                <td>
                    <input name="event_start" id="event_start" type="datetime-local" class="postbox" value="<?php echo $event_start; ?>"></input>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row">
                    <label for="event_end">Veranstaltungsende</label>
                </th>
                <td>
                    <input name="event_end" id="event_end" type="datetime-local" class="postbox" value="<?php echo $event_end; ?>"></input>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row">
                    <label for="event_id">Veranstaltungs-ID</label>
                </th>
                <td>
                    <input name="event_id" id="event_id" type="number" class="postbox" value="<?php echo $event_id; ?>"></input>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row">
                    <label for="event_id">Veranstalter-ID</label>
                </th>
                <td>
                    <input name="event_id" id="event_vid" type="number" class="postbox" value="<?php echo $event_vid; ?>"></input>
                </td>
            </tr>
        </table>
        <?php
    }
}

add_action( 'add_meta_boxes', [ 'ev_kirchen_termine_Meta_Box', 'add' ] );
add_action( 'save_post', [ 'ev_kirchen_termine_Meta_Box', 'save' ] );
