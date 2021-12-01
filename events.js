
jQuery(document).ready(function($) {

    $( ".event_feedback_counter" ).each(function( ) {
        var count = get_feedback_count($( this ).attr('event_id'));
        $( this ).text(
            $( this ).text().replace("%count%", count)
        );
        if(count >= 0) {
            $( this ).show();
        }
        if(count == 0) {
            $( this ).closest("a").prop("disabled",true);;
        }
        // event in past
        if(count == -10) {
            $( this ).closest("a").hide();
        }
    });


    function get_feedback_count(id) {

        return $.ajax({
            url: ev_kirchen_events_js_data.ajaxurl + 'feedback_status.php?id=' + id,
            type: "GET",
            dataType:"json",
            async: false
        }).responseText;

    }

});
