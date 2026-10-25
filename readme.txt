# Ev. Kirchen Termine

Contributors: jan2000
Tags: events, kirche
Requires at least: 6.3
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 0.2.0
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
 - show_location (optional) - default: true
 - show_organizer (optional) - default: false
 - show_contact_person (optional) - default: false
 - show_more_link (optional) - default: true
 - title_level (optional) - heading level of the event titles (2-6), default: 2

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
   * **Images:** By default (setting "Store event images in the media library") event images are downloaded server-side during the import and served from your own media library. No visitor data is shared. If this setting is disabled, the images are embedded directly from the configured domain via standard HTML image tags (`<img>`). Then the visitor's browser downloads the images from that server, exposing the visitor's IP address and user-agent to it.

2. **Map Provider via Iframes**
   With the setting "OpenStreetMap image, stored in the media library" the WordPress server renders a static map image of every event location once during the import from the OpenStreetMap tile server (`https://tile.openstreetmap.org`, see https://operations.osmfoundation.org/policies/tiles/) and stores it in the media library. The visitor's browser only loads this local image, no visitor data is shared. The required attribution "© OpenStreetMap contributors" is shown below the map.
   With the default setting "No map, link only" no map is embedded and the visitor's browser does not connect to any map provider; only plain links to OpenStreetMap / Google Maps / Apple Maps are shown. If a map iframe is chosen in the plugin settings, this triggers client-side external connections:
   * **OpenStreetMap Choice:** If configured to use OpenStreetMap, an iframe connects to `https://*.openstreetmap.org` (or a related tile provider) to display the venue on a map. The privacy policy of OpenStreetMap can be found under: https://osmfoundation.org/wiki/Privacy_Policy
   * **Google Maps Choice:** If configured to use Google Maps, an iframe embeds maps from `https://www.google.com/maps`, which is subject to Google's privacy policy and tracks user interactions. The Google privacy policy can be found under: https://policies.google.com/privacy

== Changelog ==

= 0.2.0 =
* Event images are stored in the media library by default (no hotlinking of the event database server).
* New map setting "No map, link only" (default) without any embedded third-party map.
* New map setting "OpenStreetMap image": a static map of the location is rendered once on the server and stored in the media library.
* Event data is fetched with the WordPress HTTP API (timeout and error handling).
* Calendar scripts are only loaded on pages that show the calendar, jQuery is no longer required.
* Fixed: the OpenStreetMap iframe was always fully zoomed out instead of showing the location.
* The plugin is now prepared for translation on translate.wordpress.org: all texts are in English and translatable, German translation included, translations for the block editor.
* The calendar uses the language of the website instead of always German.
* New attribute `title_level` for the event list (shortcode and block) to set the heading level of the event titles.
* Boolean shortcode attributes like `show_more_link="false"` now work.
* Accessibility: share buttons have labels for screen readers, the registration button is a regular link instead of a popup, empty fields of organizer and contact person are no longer shown.
* The registration link points to the current web form of the event database.
* The alt text of event images is taken from the event database.
* Improved escaping of the imported organizer and contact person data.
* Requires at least WordPress 6.3 and PHP 7.4.
