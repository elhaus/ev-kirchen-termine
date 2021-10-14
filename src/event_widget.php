<?php

// Creating the widget
class ev_event_widget extends WP_Widget {

    function __construct() {
        parent::__construct(

            // Base ID of your widget
            'ev_event_widget',

            // Widget name will appear in UI
            __('Ev. Kirchen Termine - Kalendar', 'ev_event_widget_domain'),

            // Widget description
            array( 'description' => __( 'Kalendar-Widget das alle Termine oder Termine mit bestimmten Tags anzeigt.', 'ev_event_widget_domain' ), )
        );
    }

    // Creating widget front-end

    public function widget( $args, $instance ) {

        if(!isset($instance['tag_filter'])) $instance['tag_filter'] = "";
        if(!isset($instance['number_of_events'])) $instance['number_of_events'] = 5;

        $events = create_small_event_list(
            array(
                "channel"=>$instance['tag_filter'],
                "limit"=>$instance['number_of_events'],
            ));

        // before and after widget arguments are defined by themes
        echo $args['before_widget'];

        // This is where you run the code and display the output
        echo __( $events, 'ev_event_widget_domain' );
        echo $args['after_widget'];
    }

    // Widget Backend
    public function form( $instance ) {

        global $wpdb;

        $event_tags = $wpdb->get_results(
            "SELECT $wpdb->terms.slug as 'slug',$wpdb->terms.name as 'name' FROM $wpdb->term_relationships
                INNER JOIN $wpdb->posts on $wpdb->posts.ID = $wpdb->term_relationships.object_id
                INNER JOIN $wpdb->terms on $wpdb->term_relationships.term_taxonomy_id = $wpdb->terms.term_id
                WHERE $wpdb->posts.post_type = 'event'
                GROUP BY slug");

        if ( isset( $instance[ 'tag_filter' ] ) ) {
            $tag_filter = $instance[ 'tag_filter' ];
        }
        else {
            $tag_filter = __( '', 'ev_event_widget_domain' );
        }

        if ( isset( $instance[ 'number_of_events' ] ) ) {
            $number_of_events = $instance[ 'number_of_events' ];
        }
        else {
            $number_of_events = __( '5', 'ev_event_widget_domain' );
        }

        // Widget admin form
        ?>
        <p>
            <label for="<?php echo $this->get_field_id( 'tag_filter' ); ?>"><?php _e( 'Filter:' ); ?></label>
            <select name="<?php echo $this->get_field_name( 'tag_filter' ); ?>[]" id="<?php echo $this->get_field_id( 'tag_filter' ); ?>" multiple>
                <option value="">Alle Events</option>"
                <?php
                foreach ($event_tags as $event_tag) {
                    $selected = "";
                    if(in_array($event_tag->slug, explode(",", $tag_filter))) $selected = " selected";
                    echo "<option value='".$event_tag->slug."'$selected>".$event_tag->name."</option>";
                }
                ?>
            </select>

            <label for="<?php echo $this->get_field_id( 'number_of_events' ); ?>"><?php _e( 'Anzahl der Termine:' ); ?></label>
            <input type="number" name="<?php echo $this->get_field_name( 'number_of_events' ); ?>" id="<?php echo $this->get_field_id( 'number_of_events' ); ?>" value="<?php echo $number_of_events; ?>" min="1" max="50" step="1">
        </p>
        <?php
    }

    // Updating widget replacing old instances with new
    public function update( $new_instance, $old_instance ) {
        if(is_array($new_instance['tag_filter'])) $new_instance['tag_filter'] = implode(",", $new_instance['tag_filter']);
        $instance = array();
        $instance['tag_filter'] = ( ! empty( $new_instance['tag_filter'] ) ) ? strip_tags( $new_instance['tag_filter'] ) : '';
        $instance['number_of_events'] = ( ! empty( $new_instance['number_of_events'] ) ) ? strip_tags( $new_instance['number_of_events'] ) : '5';
        return $instance;
    }

    // Class ev_event_widget ends here
}


// Register and load the widget
function ev_event_load_widget() {
    register_widget( 'ev_event_widget' );
}
add_action( 'widgets_init', 'ev_event_load_widget' );
