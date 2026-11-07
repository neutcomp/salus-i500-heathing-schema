# Salus iT500 Heating Schema

This WordPress plugin adds a settings page under **Settings → Salus iT500**.

## Connection check

1. Enter your Salus account username and password, then save them.
2. Select **Test connection**. The test only requests an authentication session; it does not read or change heating settings.
3. A successful response is reported only when the API returns both a user ID and security token. The token is not persisted.

The connection uses the unofficial authentication flow implemented by [`pyit500`](https://github.com/RichyA/pyit500): HTTPS to the Arrayent Salus service and an MD5 password digest in the JSON login request. The digest is compatibility behavior required by that protocol, not a recommended password-storage mechanism. TLS certificate verification remains enabled.

Credentials are stored in the WordPress options database. Restrict WordPress administrator access and protect database backups accordingly. Do not enter credentials for an account you are not authorized to use.

This plugin currently provides only credential configuration and an authentication check; it does not yet display a device schema or control heating.
