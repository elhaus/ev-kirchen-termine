
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