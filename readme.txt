# Ev. Kirchen Termine

Contributors: jan2000
Tags: events, kirche
Tested up to: 7.0
Stable tag: 0.1.3
License: GPL v2 or later

This Plugin imports events form event databases like https://www.veranstaltungen-ekvw.de/.
The events will be handled as a custom post type.

## Description

### Shortcodes

#### Small list of events

`[evkite_events_list channel="" limit="5" highlight="" event_ids="" vid=""]`
 - channel (optional)
 - limit (optional) - default: 5
 - highlight (optional)
 - event_ids (optional)
 - vid (optional)

#### calendar view of events

`[evkite_events_calendar channel="" vid=""]`
 - channel (optional)
 - vid (optional)

== External services ==

This plugin establishes connections to remote servers to fetch event data, embed media, and render maps. Depending on your admin configuration, user data (like IP addresses) may be shared with these third-party providers when a visitor loads an event page.

This plugin communicates with the following external endpoints:

1. ** Church Event API & Image Hosting**
   * **Target Domain:** Dynamically defined in the plugin settings (e.g., `https://www.veranstaltungen-ekvw.de/`).
   * **Data Fetching:** The local WordPress server performs periodic background HTTP requests to this specified domain to import event details (titles, descriptions, times, and categories). This is done server-side and does not expose user data.
   * **Image Embedding:** Event images uploaded to the central church portal are embedded directly from this domain via standard HTML image tags (`<img>`). When a user visits your website, their browser will download the images directly from the configured domain, exposing the visitor's IP address and user-agent to that server.

2. **Map Provider via Iframes**
   Depending on the chosen settings in the plugin configuration dashboard, event locations are displayed using interactive map iframes. This triggers client-side external connections:
   * **OpenStreetMap Choice:** If configured to use OpenStreetMap, an iframe connects to `https://*.openstreetmap.org` (or a related tile provider) to display the venue on a map. The privacy policy of OpenStreetMap can be found under: https://osmfoundation.org/wiki/Privacy_Policy
   * **Google Maps Choice:** If configured to use Google Maps, an iframe embeds maps from `https://www.google.com/maps`, which is subject to Google's privacy policy and tracks user interactions. The Google privacy policy can be found under: https://policies.google.com/privacy