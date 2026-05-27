
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

function mobilecheck() {
    return (window.innerWidth < 765);
};

document.addEventListener("DOMContentLoaded", function() {

    var calendarEl = document.getElementById("evkitecalendar");
    if (!calendarEl) return; // Sicherheits-Check

    // evkiteCalendarData will be provided by wp_add_inline_script
    var eventsData = typeof evkiteCalendarData !== 'undefined' ? evkiteCalendarData.events : [];

    var calendar = new FullCalendar.Calendar(calendarEl, {
        headerToolbar: {
            left: "prev,next today",
            center: "title",
            right: mobilecheck() ? "" : "dayGridMonth,timeGridWeek,timeGridDay,listMonth",
        },
        locale: "de",
        weekNumbers: true,
        height: "auto",
        initialView: mobilecheck() ? "listMonth" : "dayGridMonth",
        navLinks: true, // can click day/week names to navigate views
        dayMaxEvents: true, // allow "more" link when too many events
        eventColor: "#213c6b",
        events: eventsData
    });

    calendar.render();

});