# ev-kirchen-termine

ev-kirchen-termine is a WordPress plugin to import events form a event database like https://www.veranstaltungen-ekvw.de/.
The events will be handled as a custom post type.

## Shortcodes

    [events_list ]
 - channel (optional)
 - limit (optional) - default: 5
 - highlight (optional)
 - event_ids (optional)

    [events_calendar channel="<channel-name>"]
 - channel (optional)

## YouTube-Live notification

If a YouTube api-key and a the YouTube channelID were configured, a notification will popup if the channel is live.
