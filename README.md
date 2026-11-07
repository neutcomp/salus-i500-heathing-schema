# Salus iT500 Heating Schema

This WordPress plugin adds a standalone **Heating schema → Schema** admin screen and account settings under **Settings → Salus iT500**.

## Connection and weekly schema

1. Enter your Salus account username and password, then save them.
2. Select **Test connection** under **Settings → Salus iT500** to verify authentication.
3. Open **Heating schema → Schema** and select **Refresh schema** to load device programmes.
4. The weekly view decodes each heating zone's Monday-to-Sunday programme into readable time and target-temperature changes. Original device values remain available under each day for troubleshooting.

The connection uses the unofficial authentication flow implemented by [`pyit500`](https://github.com/RichyA/pyit500): HTTPS to the Arrayent Salus service and an MD5 password digest in the JSON login request. The digest is compatibility behavior required by that protocol, not a recommended password-storage mechanism. TLS certificate verification remains enabled.

Credentials are stored in the WordPress options database. Restrict WordPress administrator access and protect database backups accordingly. The schema view is read-only and its cached data expires after five minutes. Temperatures are shown in the device's configured unit. Do not enter credentials for an account you are not authorized to use.

This plugin does not change heating settings.
