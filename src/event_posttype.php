<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

function ev_kirchen_termine_setup_admin_menus() {
    add_submenu_page('options-general.php',
    __('Ev. Kirchen Termine - settings', 'ev-kirchen-termine'), __('Ev. Kirchen Termine settings', 'ev-kirchen-termine'), 'manage_options',
    'ev_kirchen_termine_settings', 'ev_kirchen_termine_manage_settings');
}

function ev_kirchen_termine_manage_settings() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'ev-kirchen-termine'));
    }
    ?>
        <div class="wrap">
            <h2><?php esc_html_e("Ev. Kirchen Termine - settings", 'ev-kirchen-termine'); ?></h2>
    <?php
    if (isset($_POST["update_settings"])) {

        check_admin_referer( 'update_ev-kirchen-termine_settings' );

        // Define your fields by type
        $text_fields = [
            'ev_kirchen_termine_webpage',
            'ev_kirchen_termine_vid',
            'ev_kirchen_termine_vid_eventtype_filter',
            'ev_kirchen_termine_region',
            'ev_kirchen_termine_region_eventtype_filter',
            'ev_kirchen_termine_custom_filter',
            'ev_kirchen_termine_event_template',
            'ev_kirchen_termine_map_type',
            'ev_kirchen_termine_google_maps_api_key'
        ];

        // Process standard text inputs
        foreach ( $text_fields as $field ) {
            $value = isset( $_POST[ $field ] ) 
                ? sanitize_text_field( wp_unslash( $_POST[ $field ] ) ) 
                : '';
            update_option( $field, $value );
        }

        $checkbox_fields = [
            'ev_kirchen_termine_show_events_in_search',
            'ev_kirchen_termine_show_share_icons',
            'ev_kirchen_termine_local_images'
        ];

        // Process checkbox inputs (strictly 1 or 0)
        foreach ( $checkbox_fields as $field ) {
            // If the checkbox is checked, it exists in $_POST, so save 1. Otherwise, save 0.
            $value = isset( $_POST[ $field ] ) ? 1 : 0;
            update_option( $field, $value );
        }

    }
    ?>
            <form method="POST" action="">
                <p><?php esc_html_e('For parish websites, please enter the vid and leave the region field empty. For church district websites, enter "all" as vid and fill in the region of the church district.', 'ev-kirchen-termine'); ?></p>
                <p><?php esc_html_e('The category filter refers to the "eventtype". If the field is left empty, no further filtering is applied. If you enter e.g. 1, only church services are imported. More details:', 'ev-kirchen-termine'); ?> <a href="http://handbuch.evangelische-termine.de/anzeige-im-internet/ausgabe-parameter">http://handbuch.evangelische-termine.de/anzeige-im-internet/ausgabe-parameter</a></p>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">
                            <label for="ev_kirchen_termine_webpage">
                                <?php esc_html_e("Ev. Termine website:", 'ev-kirchen-termine'); ?>
                            </label>
                        </th>
                        <td>
                            <input type="text" name="ev_kirchen_termine_webpage" size="25" value="<?php echo esc_attr(get_option("ev_kirchen_termine_webpage")); ?>" />
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                            <label for="ev_kirchen_termine_vid">
                                <?php esc_html_e("Organizer ID [vid]:", 'ev-kirchen-termine'); ?>
                            </label>
                        </th>
                        <td>
                            <input type="text" name="ev_kirchen_termine_vid" size="25" value="<?php echo esc_attr(get_option("ev_kirchen_termine_vid")); ?>" />
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                            <label for="ev_kirchen_termine_vid_eventtype_filter">
                                <?php esc_html_e("Category Filter", 'ev-kirchen-termine'); ?> (vid) [eventtype]:
                            </label>
                        </th>
                        <td>
                            <input type="text" name="ev_kirchen_termine_vid_eventtype_filter" size="25" value="<?php echo esc_attr(get_option("ev_kirchen_termine_vid_eventtype_filter")); ?>" />
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                            <label for="ev_kirchen_termine_region">
                                <?php esc_html_e("Church district/deanery number", 'ev-kirchen-termine'); ?> [region]:
                            </label>
                        </th>
                        <td>
                            <input type="text" name="ev_kirchen_termine_region" size="25" value="<?php echo esc_attr(get_option("ev_kirchen_termine_region")); ?>" />
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                            <label for="ev_kirchen_termine_region_eventtype_filter">
                                <?php esc_html_e("Category Filter", 'ev-kirchen-termine'); ?> (region) [eventtype]:
                            </label>
                        </th>
                        <td>
                            <input type="text" name="ev_kirchen_termine_region_eventtype_filter" size="25" value="<?php echo esc_attr(get_option("ev_kirchen_termine_region_eventtype_filter")); ?>" />
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                            <label for="ev_kirchen_termine_custom_filter">
                                <?php esc_html_e("Custom Filter", 'ev-kirchen-termine'); ?>:
                            </label>
                        </th>
                        <td>
                            <input type="text" name="ev_kirchen_termine_custom_filter" size="40" value="<?php echo esc_attr(get_option("ev_kirchen_termine_custom_filter")); ?>" />
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                            <label for="ev_kirchen_termine_event_template"><?php esc_html_e("Event Pages Template:", 'ev-kirchen-termine'); ?></label>
                        </th>
                        <td>
                            <select name="ev_kirchen_termine_event_template" id="ev_kirchen_termine_event_template">
                                <option value=""><?php esc_html_e('Default', 'ev-kirchen-termine'); ?></option>
                                <?php
                                $templates = get_page_templates();
                                $current_setting = get_option( 'ev_kirchen_termine_event_template' );
                                foreach ( $templates as $template_name => $template_file ) {
                                    // Escape safely during output
                                    echo wp_sprintf(
                                        '<option value="%s"%s>%s</option>',
                                        esc_attr( $template_file ),
                                        selected( $current_setting, $template_file, false ), // selected() returns securely formatted ' selected="selected"' or empty
                                        esc_html( $template_name )
                                    );
                                }
                                ?>
                            </select>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                            <label for="ev_kirchen_termine_map_type"><?php esc_html_e("Map type:", 'ev-kirchen-termine'); ?></label>
                        </th>
                        <td>
                            <select name="ev_kirchen_termine_map_type" id="ev_kirchen_termine_map_type">
                                <?php
                                $options = array(
                                    "link_only" => __("No map, link only (privacy friendly)", 'ev-kirchen-termine'),
                                    "osm_image" => __("OpenStreetMap image, stored in the media library (privacy friendly)", 'ev-kirchen-termine'),
                                    "google_iframe" => __("Google Maps iframe", 'ev-kirchen-termine'),
                                    "google_image" => __("Google Maps image", 'ev-kirchen-termine'),
                                    "osm_iframe" => __("OpenStreetMap iframe", 'ev-kirchen-termine'),
                                );
                                $current_setting = get_option("ev_kirchen_termine_map_type");
                                foreach ($options as $option_key => $option_name) {
                                    // Escape safely during output
                                    echo wp_sprintf(
                                        '<option value="%s"%s>%s</option>',
                                        esc_attr( $option_key ),
                                        selected( $current_setting, $option_key, false ),  // selected() returns securely formatted ' selected="selected"' or empty
                                        esc_html( $option_name )
                                    );
                                }
                                ?>
                            </select>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                            <label for="ev_kirchen_termine_google_maps_api_key">
                                <?php esc_html_e("Google Maps API key", 'ev-kirchen-termine'); ?>:
                            </label>
                        </th>
                        <td>
                            <input type="text" name="ev_kirchen_termine_google_maps_api_key" size="64" value="<?php echo esc_attr(get_option("ev_kirchen_termine_google_maps_api_key")); ?>" />
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                            <?php esc_html_e("Other settings:", 'ev-kirchen-termine'); ?>
                        </th>
                        <td>
                            <label for="ev_kirchen_termine_show_share_icons">
                                <input name="ev_kirchen_termine_show_share_icons" type="checkbox" id="ev_kirchen_termine_show_share_icons" <?php
                                    if(get_option("ev_kirchen_termine_show_share_icons")) {
                                        echo "checked";
                                    } ?>>
                                <?php esc_html_e("Enable Share links on event page", 'ev-kirchen-termine'); ?>
                            </label></br>
                            <label for="ev_kirchen_termine_show_events_in_search">
                                <input name="ev_kirchen_termine_show_events_in_search" type="checkbox" id="ev_kirchen_termine_show_events_in_search" <?php
                                    if(get_option("ev_kirchen_termine_show_events_in_search")) {
                                        echo "checked";
                                    } ?>>
                                <?php esc_html_e("Show Events in Search", 'ev-kirchen-termine'); ?>
                            </label></br>
                            <label for="ev_kirchen_termine_local_images">
                                <input name="ev_kirchen_termine_local_images" type="checkbox" id="ev_kirchen_termine_local_images" <?php
                                    if(ev_kirchen_termine_use_local_images()) {
                                        echo "checked";
                                    } ?>>
                                <?php esc_html_e("Store event images in the media library (privacy friendly, no images are loaded from the event database server)", 'ev-kirchen-termine'); ?>
                            </label>
                        </td>
                    </tr>
                </table>
                <input type="hidden" name="update_settings" value="Y" />
                <?php wp_nonce_field( 'update_ev-kirchen-termine_settings' ); ?>
                <p>
                    <input type="submit" value="<?php esc_attr_e("save settings", 'ev-kirchen-termine'); ?>" class="button-primary"/>
                </p>
            </form>
        </div>
    <?php
}

// This tells WordPress to call the function named "setup_theme_admin_menus"
// when it's time to create the menu pages.
add_action("admin_menu", "ev_kirchen_termine_setup_admin_menus");





/**
*
* Registration unseres Custom Post Types "Event"
*
*/
function ev_kirchen_termine_custom_post_type() {
    $labels = array(
        'name'               => _x( 'Protestant events', 'post type general name', 'ev-kirchen-termine' ),
        'singular_name'      => _x( 'Event', 'post type singular name', 'ev-kirchen-termine' ),
        'menu_name'          => __( 'Protestant events', 'ev-kirchen-termine' ),
        'all_items'          => __( 'All events', 'ev-kirchen-termine' ),
        'view_item'          => __( 'View event', 'ev-kirchen-termine' ),
        'add_new_item'       => __( 'New event', 'ev-kirchen-termine' ),
        'add_new'            => __( 'Add event', 'ev-kirchen-termine' ),
        'edit_item'          => __( 'Edit event', 'ev-kirchen-termine' ),
        'update_item'        => __( 'Update event', 'ev-kirchen-termine' ),
    );
    $rewrite = array(
        'slug'       => 'event',
        'with_front' => true,
        'pages'      => true,
        'feeds'      => true,
    );
    $args = array(
        'labels' => $labels,
        'supports' => array( 'title', 'editor', 'excerpt', 'thumbnail', ),
        'taxonomies' => array( 'post_tag' ),
        'hierarchical' => false,
        'public' => true,
        'show_ui' => true,
        'show_in_menu' => false, // hides admin view
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
    register_post_type( 'evkite_event', $args );
}
// Hook into the 'init' action
add_action( 'init', 'ev_kirchen_termine_custom_post_type', 0 );



class ev_kirchen_termine_Meta_Box {
    /**
     * Set up and add the meta box.
     */
    public static function add() {
        add_meta_box(
            'event_data',          // Unique ID
            __( 'Event data', 'ev-kirchen-termine' ), // Box title
            [ self::class, 'html' ],   // Content callback, must be of type callable
            'evkite_event', // Posttype 
            'normal',
            'high'
        );
    }


    /**
     * Save the meta box selections.
     *
     * @param int $post_id  The post ID.
     */
    public static function save( int $post_id ) {

        // safty check (Nonce validation)
        $nonce_value = isset( $_POST['ev_kirchen_termine_nonce'] ) 
            ? sanitize_text_field( wp_unslash( $_POST['ev_kirchen_termine_nonce'] ) ) 
            : '';

        if ( empty( $nonce_value ) || ! wp_verify_nonce( $nonce_value, 'ev_kirchen_termine_save_meta' ) ) {
            return;
        }

        // Auto-Save von WordPress ignorieren
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        // Rechte prüfen
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        if ( isset( $_POST['event_start'] ) && ! empty( $_POST['event_start'] ) ) {
            $clean_event_start = sanitize_text_field( wp_unslash( $_POST['event_start'] ) );
            update_post_meta( $post_id, '_ev_kirchen_termine_meta_key_start', gmdate("Y-m-d H:i:s", strtotime($clean_event_start)) );
        }

        if ( isset( $_POST['event_end'] ) && ! empty( $_POST['event_end'] ) ) {
            $clean_event_end = sanitize_text_field( wp_unslash( $_POST['event_end'] ) );
            update_post_meta( $post_id, '_ev_kirchen_termine_meta_key_end', gmdate("Y-m-d H:i:s", strtotime($clean_event_end)) );
        }

        if ( isset( $_POST['event_id'] ) ) {
            update_post_meta( $post_id, '_ev_kirchen_termine_meta_key_id', sanitize_text_field( wp_unslash( $_POST['event_id'] ) ) );
        }

        if ( isset( $_POST['event_vid'] ) ) {
            update_post_meta( $post_id, '_ev_kirchen_termine_meta_key_vid', sanitize_text_field( wp_unslash( $_POST['event_vid'] ) ) );
        }
    }


    /**
     * Display the meta box HTML to the user.
     *
     * @param \WP_Post $post   Post object.
     */
    public static function html( $post ) {
        wp_nonce_field( 'ev_kirchen_termine_save_meta', 'ev_kirchen_termine_nonce' );

        $meta_start = get_post_meta( $post->ID, '_ev_kirchen_termine_meta_key_start', true );
        $meta_end   = get_post_meta( $post->ID, '_ev_kirchen_termine_meta_key_end', true );

        $event_start = ! empty( $meta_start ) ? wp_date("Y-m-d\TH:i", strtotime($meta_start)) : '';
        $event_end   = ! empty( $meta_end ) ? wp_date("Y-m-d\TH:i", strtotime($meta_end)) : '';

        $event_id    = get_post_meta( $post->ID, '_ev_kirchen_termine_meta_key_id', true );
        $event_vid   = get_post_meta( $post->ID, '_ev_kirchen_termine_meta_key_vid', true );?>
        <table class="form-table">
            <tr>
                <th><label for="event_start"><?php esc_html_e( 'Event start', 'ev-kirchen-termine' ); ?></label></th>
                <td><input name="event_start" id="event_start" type="datetime-local" value="<?php echo esc_attr($event_start); ?>"></td>
            </tr>
            <tr>
                <th><label for="event_end"><?php esc_html_e( 'Event end', 'ev-kirchen-termine' ); ?></label></th>
                <td><input name="event_end" id="event_end" type="datetime-local" value="<?php echo esc_attr($event_end); ?>"></td>
            </tr>
            <tr>
                <th><label for="event_id"><?php esc_html_e( 'Event ID', 'ev-kirchen-termine' ); ?></label></th>
                <td><input name="event_id" id="event_id" type="number" value="<?php echo esc_attr($event_id); ?>"></td>
            </tr>
            <tr>
                <th><label for="event_vid"><?php esc_html_e( 'Organizer ID', 'ev-kirchen-termine' ); ?></label></th>
                <td><input name="event_vid" id="event_vid" type="number" value="<?php echo esc_attr($event_vid); ?>"></td>
            </tr>
        </table>
        <?php
    }
}

add_action( 'add_meta_boxes', [ 'ev_kirchen_termine_Meta_Box', 'add' ] );
add_action( 'save_post', [ 'ev_kirchen_termine_Meta_Box', 'save' ] );
