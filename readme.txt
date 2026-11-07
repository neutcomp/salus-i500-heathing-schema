=== Salus iT500 Heating Schema ===
Contributors: salus-i500-heating-schema
Tags: salus, it500, heating, thermostat
Requires at least: 6.0
Requires PHP: 7.4
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Manage Salus iT500 weekly heating programmes, configure account credentials, and test API authentication from the WordPress admin.

== Description ==

Salus iT500 Heating Schema adds a Heating schema > Schema admin screen and settings under Settings > Salus iT500. Enter your Salus account username and password, save them, and refresh the weekly programme values from your iT500.

Users with the WordPress Editor role (Redacteur) can update heating schedules. Configuring account credentials and testing the connection remain administrator-only.

The schema screen automatically retrieves programme values when no data is cached, and shows a loading indicator while retrieving them. Cached schema data is shown immediately. Select Refresh schema to fetch the latest data manually. Use Rename this zone below a zone heading to set a custom title, which is saved for that Salus account and device. Each heating zone opens directly in edit mode with its Monday-to-Sunday schedule ready to change. Submit the weekly programme one heating zone at a time. Temperatures are shown in the device's configured unit.

When another user has the schema page open, a notice recommends coordinating before saving. Presence updates run every 30 seconds and expire after 75 seconds without a response; this is a heads-up, not an edit lock.

The connection uses the unofficial iT500 authentication flow implemented by pyit500. The API requires an MD5 password digest in the login request. This is compatibility behavior for that protocol, not a recommended password-storage method. TLS certificate verification remains enabled.

The plugin stores credentials in the WordPress options database. Restrict administrator access and protect database backups accordingly. Only connect to accounts and devices you are authorized to use.

The schema is a standalone admin page. Saving a zone sends schedule changes to the Salus API. If a multi-request update fails partway through, refresh the schema to check the device's actual state. Retrieved schema data is cached for up to five minutes.

== Installation ==

1. Upload the `salus-i500-heating-schema` folder to the `/wp-content/plugins/` directory, or install the plugin through WordPress.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Go to **Settings > Salus iT500**.
4. Enter your account username and password, then select **Save credentials**.
5. Select **Test connection** under Settings > Salus iT500 to check authentication.
6. Open Heating schema > Schema. Data loads automatically when there is no cached schema; use **Refresh schema** to retrieve it manually.
7. Optionally select **Rename this zone** below a zone heading to set a custom title. Change times or temperatures directly in each heating zone, then select **Save zone schedule**. Each zone is submitted separately.

== Frequently Asked Questions ==

= Does the connection test change heating settings? =

No. It requests an authentication session only.

= Does refreshing the schema change heating settings? =

No. It reads device and programme attributes only.

= Can I edit schedules? =

Yes. Expand the editor for a heating zone, change up to six time and temperature points per day, and save that zone's weekly schedule. Refresh the schema after any failed update to check the device's actual state.

= Is the session token stored? =

No. The token is checked in memory and discarded after the test.

= Where are the credentials stored? =

They are stored in the WordPress options database. Leave the password field blank when saving to keep the current password, or select **Clear the saved password** to remove it.

= Is this an official Salus API integration? =

No. The authentication flow is based on the public, unofficial pyit500 library and may change without notice.

== Changelog ==

= 0.1.0 =
* Add account credential settings, an authentication-only connection test, a weekly schema view, and per-zone schedule editing.

== Upgrade Notice ==

= 0.1.0 =
Initial version.
