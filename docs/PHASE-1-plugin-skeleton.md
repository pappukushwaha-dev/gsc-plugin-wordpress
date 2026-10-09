# PHASE 1 - Plugin skeleton and site registration

Deliverable: a working plugin zip. Install on any WordPress site, activate,
site registers itself, Connect button opens the hosted setup wizard.

Est. 1-2 days. No GSC features yet - those are PHASE-2.

---

## 1. Plugin file tree (v1)

```
gsc-plugin-wordpress/
  gsc-plugin.php               header, constants, includes, activation/deactivation
  uninstall.php                removes gscwp_ options on uninstall
  includes/
    class-gscwp-register.php   register-site call, option storage
    class-gscwp-apiclient.php  single HTTP client (wp_remote_*) to our panel
    class-gscwp-adminpage.php  settings page under WP admin > GSC
  assets/admin.css             settings page styling only
  readme.txt                   wp.org format
```

Conventions: prefix `gscwp_` (functions), `GSCWP_` (constants), `gscwp_`
(options). PHP 7.4 minimum. No Composer, no external libraries in v1.

## 2. The registration API contract

`POST https://makkpressapps.com/wordpress/googlesearchconsole/api/register-site.php`

Request JSON:
```
{ "site_url": "https://example.com",
  "wp_version": "6.7",
  "plugin_version": "1.0.0" }
```

Behaviour (server):
- upsert WpSite on site_url (idempotent - reinstall returns same instance_id)
- generates instance_id the same way the platforms do (unique varchar 191)
- sets is_active = 1, stamps created_at on first registration
- returns `{ "success": true, "instance_id": "..." }`

Failure: `{ "success": false, "error": "..." }` - the plugin retries with
backoff on the next admin page load; the site is never broken by it.

## 3. Plugin behaviour

- On activation: fire register-site (non-blocking feel - if it fails, show
  a Retry banner on the settings page, never a fatal).
- Store in wp_options under `gscwp_instance`: instance_id, site_url,
  registered_at, plugin_version.
- Settings page (WP admin > GSC):
  - status block: registered yes/no, instance_id, plan (after PHASE-5)
  - "Connect to Dashboard" button ->
    opens `https://makkpressapps.com/wordpress/googlesearchconsole/setup-wizard.php?instance_id=...&site=<site_url>`
    in a new tab
  - after PHASE-5: license key field + Validate button
- On deactivation: do nothing destructive (keep the registration).
- On uninstall (uninstall.php): delete gscwp_ options. The WpSite row stays
  on the server marked inactive by a best-effort deactivate call.

## 4. Server side (part of this phase)

- `api/register-site.php` on the hosted panel (new file, contract above)
- `WpSite` table migration (01-wp-sites.sql, guarded, idempotent)
- The panel accepts `instance_id` from GET the way other platforms do
  (session bootstrap in config.php - same pattern as the five)

## 5. Checklist

- [ ] plugin zip installs cleanly on a fresh WP (current version)
- [ ] activate -> instance appears in WpSite
- [ ] reactivate/reinstall -> same instance_id (idempotent)
- [ ] Connect button opens setup wizard with correct instance_id
- [ ] server error -> Retry banner, site never fatals
- [ ] uninstall removes plugin options
- [ ] PHP 7.4 and 8.x both pass
