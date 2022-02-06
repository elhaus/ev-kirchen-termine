# ev-kirchen-termine

ev-kirchen-termine is a WordPress plugin to import events form a event database like https://www.veranstaltungen-ekvw.de/.
The events will be handled as a custom post type.

## Shortcodes

#### Small list of events

    [events_list channel="" limit="5" highlight="" event_ids="" vid=""]
 - channel (optional)
 - limit (optional) - default: 5
 - highlight (optional)
 - event_ids (optional)
 - vid (optional)

#### calendar view of events

    [events_calendar channel="" vid=""]
 - channel (optional)
 - vid (optional)

## Installation

 1. extract the plugin data to the wordpress dirotory "/wp-content/plugins/ev-kirchen-termine/" or upload the plugin via the wordpress plugin menu (zip file)
 2. go to Settings -> Ev. Kirchen Termine Einstellungen, configure event source website and vid
