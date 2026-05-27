<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Creating the widget
class ev_event_widget extends WP_Widget {

    function __construct() {
        parent::__construct(

            // Base ID of your widget
            'ev_event_widget',

            // Widget name will appear in UI
            __('Ev. Kirchen Termine - calendar', 'ev-kirchen-termine'),

            // Widget description
            array( 'description' => __( 'Calendar widget that displays all appointments or appointments with specific tags.', 'ev-kirchen-termine' ), )
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
        echo $events;
        echo $args['after_widget'];
    }

    // Widget Backend
    public function form( $instance ) {

        global $wpdb;

        // Get all IDs of the 'event' post type
        $event_ids = get_posts( array(
            'post_type'      => 'event',
            'posts_per_page' => -1,
            'fields'         => 'ids', // optimized query returning only IDs
        ) );

        $event_tags = array();

        // Fetch the terms (tags) attached to those specific event IDs
        if ( ! empty( $event_ids ) ) {
            $event_tags = wp_get_object_terms( $event_ids, 'post_tag', array(
                'orderby' => 'name',
                'order'   => 'ASC',
            ) );
            
            // De-duplicate the results
            $event_tags = array_unique( $event_tags, SORT_REGULAR );
        }

        if ( isset( $instance[ 'tag_filter' ] ) ) {
            $tag_filter = $instance[ 'tag_filter' ];
        }
        else {
            $tag_filter = '';
        }

        if ( isset( $instance[ 'number_of_events' ] ) ) {
            $number_of_events = $instance[ 'number_of_events' ];
        }
        else {
            $number_of_events = '5';
        }

        // Widget admin form
        ?>
        <p>
            <label for="<?php echo $this->get_field_id( 'tag_filter' ); ?>"><?php esc_html_e( 'Filter:', 'ev-kirchen-termine' ); ?></label>
            <select name="<?php echo $this->get_field_name( 'tag_filter' ); ?>[]" id="<?php echo $this->get_field_id( 'tag_filter' ); ?>" multiple>
                <option value="">Alle Events</option>"
                <?php
                foreach ($event_tags as $event_tag) {
                    $selected = "";
                    if(in_array($event_tag->slug, explode(",", $tag_filter))) $selected = " selected";
                    echo sprintf(
                        '<option value="%s"%s>%s</option>',
                        esc_attr( $event_tag->slug ),
                        selected( in_array($event_tag->slug, explode(",", $tag_filter)), true, false ),
                        esc_html( $event_tag->name )
                    );
                }
                ?>
            </select>

            <label for="<?php echo esc_attr($this->get_field_id( 'number_of_events' )); ?>"><?php esc_html_e( 'Number of events:', 'ev-kirchen-termine' ); ?></label>
            <input type="number" name="<?php echo esc_attr($this->get_field_name( 'number_of_events' )); ?>" id="<?php echo esc_attr($this->get_field_id( 'number_of_events' )); ?>" value="<?php echo esc_attr($number_of_events); ?>" min="1" max="50" step="1">
        </p>
        <?php
    }

    // Updating widget replacing old instances with new
    public function update( $new_instance, $old_instance ) {
        if(is_array($new_instance['tag_filter'])) $new_instance['tag_filter'] = implode(",", $new_instance['tag_filter']);
        $instance = array();
        $instance['tag_filter'] = ( ! empty( $new_instance['tag_filter'] ) ) ? wp_strip_all_tags( $new_instance['tag_filter'] ) : '';
        $instance['number_of_events'] = ( ! empty( $new_instance['number_of_events'] ) ) ? wp_strip_all_tags( $new_instance['number_of_events'] ) : '5';
        return $instance;
    }

    // Class ev_event_widget ends here
}


// Register and load the widget
function ev_event_load_widget() {
    register_widget( 'ev_event_widget' );
}
add_action( 'widgets_init', 'ev_event_load_widget' );
