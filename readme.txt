=== Salus iT500 Heating Schema ===
Contributors: salus-i500-heating-schema
Tags: salus, it500, heating, thermostat
Requires at least: 6.0
Requires PHP: 7.4
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

View Salus iT500 weekly heating programme values, configure account credentials, and test API authentication from the WordPress admin.

== Description ==

Salus iT500 Heating Schema adds a Heating schema > Schema admin screen and settings under Settings > Salus iT500. Enter your Salus account username and password, save them, and refresh the weekly programme values from your iT500.

The schema screen displays programme values for each available heating zone from Monday to Sunday. The iT500 API provides these as proprietary strings, and the pyit500 library does not document their encoding into time/temperature periods. The plugin therefore displays the exact raw values and does not infer a schedule.

The connection uses the unofficial iT500 authentication flow implemented by pyit500. The API requires an MD5 password digest in the login request. This is compatibility behavior for that protocol, not a recommended password-storage method. TLS certificate verification remains enabled.

The plugin stores credentials in the WordPress options database. Restrict administrator access and protect database backups accordingly. Only connect to accounts and devices you are authorized to use.

The schema is read-only. This plugin does not change heating settings. Retrieved schema data is cached for up to five minutes.

== Installation ==

1. Upload the `salus-i500-heating-schema` folder to the `/wp-content/plugins/` directory, or install the plugin through WordPress.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Go to **Settings > Salus iT500**.
4. Enter your account username and password, then select **Save credentials**.
5. Select **Test connection** under Settings > Salus iT500 to check authentication.
6. Open Heating schema > Schema and select **Refresh schema** to load weekly programme values.

== Frequently Asked Questions ==

= Does the connection test change heating settings? =

No. It requests an authentication session only.

= Does refreshing the schema change heating settings? =

No. It reads device and programme attributes only.

= Is the session token stored? =

No. The token is checked in memory and discarded after the test.

= Where are the credentials stored? =

They are stored in the WordPress options database. Leave the password field blank when saving to keep the current password, or select **Clear the saved password** to remove it.

= Is this an official Salus API integration? =

No. The authentication flow is based on the public, unofficial pyit500 library and may change without notice.

== Changelog ==

= 0.1.0 =
* Add account credential settings, an authentication-only connection test, and a read-only weekly schema view.

== Upgrade Notice ==

= 0.1.0 =
Initial version.
