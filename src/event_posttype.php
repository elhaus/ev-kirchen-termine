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
            <h2>Ev. Kirchen Termine Einstellungen</h2>
    <?php
    if (isset($_POST["update_settings"])) {
        $ev_kirchen_termine_webpage = esc_attr($_POST["ev_kirchen_termine_webpage"]);
        update_option("ev_kirchen_termine_webpage", $ev_kirchen_termine_webpage);
        $ev_kirchen_termine_vid = esc_attr($_POST["ev_kirchen_termine_vid"]);
        update_option("ev_kirchen_termine_vid", $ev_kirchen_termine_vid);
        $ev_kirchen_termine_event_template = esc_attr($_POST["ev_kirchen_termine_event_template"]);
        update_option("ev_kirchen_termine_event_template", $ev_kirchen_termine_event_template);
        $ev_kirchen_termine_yt_api_key = esc_attr($_POST["ev_kirchen_termine_yt_api_key"]);
        update_option("ev_kirchen_termine_yt_api_key", $ev_kirchen_termine_yt_api_key);
        $ev_kirchen_termine_yt_channel = esc_attr($_POST["ev_kirchen_termine_yt_channel"]);
        update_option("ev_kirchen_termine_yt_channel", $ev_kirchen_termine_yt_channel);
    }
    ?>
            <form method="POST" action="">
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">
                            <label for="ev_kirchen_termine_webpage">
                                Ev. Termine Webseite:
                            </label>
                        </th>
                        <td>
                            <input type="text" name="ev_kirchen_termine_webpage" size="25" value="<?php echo get_option("ev_kirchen_termine_webpage"); ?>" />
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                            <label for="ev_kirchen_termine_vid">
                                Veranstalter ID [vid]:
                            </label>
                        </th>
                        <td>
                            <input type="text" name="ev_kirchen_termine_vid" size="25" value="<?php echo get_option("ev_kirchen_termine_vid"); ?>" />
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                            <label for="ev_kirchen_termine_event_template">Veranstaltungsseiten Template</label>
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
                            <label for="ev_kirchen_termine_yt_api_key">
                                YouTube API-Key:
                            </label>
                        </th>
                        <td>
                            <input type="text" name="ev_kirchen_termine_yt_api_key" size="25" value="<?php echo get_option("ev_kirchen_termine_yt_api_key"); ?>" />
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">
                            <label for="ev_kirchen_termine_yt_channel">
                                YouTube channel-ID:
                            </label>
                        </th>
                        <td>
                            <input type="text" name="ev_kirchen_termine_yt_channel" size="25" value="<?php echo get_option("ev_kirchen_termine_yt_channel"); ?>" />
                        </td>
                    </tr>
                </table>
                <input type="hidden" name="update_settings" value="Y" />
                <p>
                    <input type="submit" value="Save settings" class="button-primary"/>
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
        'has_archive' => true,
        'exclude_from_search' => false,
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
        </table>
        <?php
    }
}

add_action( 'add_meta_boxes', [ 'ev_kirchen_termine_Meta_Box', 'add' ] );
add_action( 'save_post', [ 'ev_kirchen_termine_Meta_Box', 'save' ] );
