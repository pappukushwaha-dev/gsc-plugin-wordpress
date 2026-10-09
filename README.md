# GSC Plugin for WordPress - End-to-End Plan

> **Documentation set (read these first):**
> - docs/00-MASTER-PLAN.md - the master plan, phase map, open decisions
> - docs/VERIFIED-BASELINE.md - file inventory of the five live apps (evidence base)
> - docs/PHASE-1-plugin-skeleton.md - plugin install, registration, Connect button
> - docs/PHASE-2-onsite-features.md - verification tag, sitemap, update pings
> - docs/PHASE-3-hosted-panel.md - the full hosted panel, file by file
> - docs/PHASE-4-admin-panel.md - the operator panel
> - docs/PHASE-5-billing-licensing.md - Stripe, license keys, plan gating
> - docs/PHASE-6-testing.md - end-to-end test matrix
> - docs/PHASE-7-launch-wporg.md - direct launch + WordPress.org

The Google Search Console app, sixth platform. Two deliverables, one product:

1. **The WordPress plugin** - lives inside the user's site. Handles on-site
   work: GSC verification tag, sitemap help, content-update pings, and the
   button that connects the site to the hosted dashboard.
2. **The hosted panel** - `https://makkpressapps.com/wordpress/googlesearchconsole/`
   - same shape as the Wix/Ecwid/BigCommerce apps. Setup wizard, Google
   OAuth, dashboards, reports, alerts, admin, billing.

Distribution: WordPress.org free listing (reach) plus direct download from
makkpressapps.com (control). Free vs paid via license keys and Stripe.

---

## 1. How a user flows, end to end

```
User installs plugin (wp.org, or zip from our site)
  -> Activates it in WP admin
  -> Plugin registers the site with our API:
       POST api/register-site  { site_url, wp_secret }
       <- { instance_id, secret }
  -> Plugin settings page shows "Connect to Dashboard"
  -> Opens https://makkpressapps.com/wordpress/googlesearchconsole/
       setup-wizard.php?instance_id=...
  -> Setup wizard (same as Wix/Ecwid):
       - Google account connect (google_oauth_callback.php, reused)
       - GSC verification: plugin injects the token itself, wizard just confirms
       - Sitemap: plugin detects the WP sitemap, submits it to GSC
  -> Dashboard, reports, alerts (hosted panel, GSC API data)
  -> Free vs Paid: license key entered in the plugin; panel gates by plan
```

The only structural difference from the other five platforms is the first
step: Wix's market created the instance at install time. Here the plugin
creates it by calling register-site at activation.

---

## 2. The plugin (code that ships to users)

```
gsc-plugin-wordpress/            (plugin root - becomes the zip)
  gsc-plugin.php                 main file: header, activation, hooks
  includes/
    class-gsc-register.php       register-site call, stores instance_id + secret (option table)
    class-gsc-verify.php         injects the GSC meta tag into wp_head
    class-gsc-sitemap.php        detects WP sitemap (wp-sitemap.xml), hands it to the panel
    class-gsc-license.php        license key entry, validation against our API, plan cache
    class-gsc-admin-page.php     settings screen (WP admin), Connect button, status
    class-gsc-api-client.php     one place that talks to makkpressapps.com endpoints
  assets/
    admin.css / admin.js         settings page styling
  readme.txt                     wp.org format readme
```

Rules for the plugin code:

- One prefix on everything: `gscwp_` for functions, `GSCWP_` for constants,
  `gscwp_` for options - avoids clashes with Yoast/RankMath/anything else.
- No external libraries at v1. WP HTTP API (`wp_remote_post`) instead of curl.
- All settings in one WP option array; uninstall removes them (uninstall.php).
- Nonces + capability checks (`manage_options`) on every admin action.
- No page output without escaping; all strings translatable-ready.

---

## 3. The hosted panel (on makkpressapps.com)

Copy-adapted from the Ecwid app (closest structure), new platform folder:

```
makkpressapps.com/wordpress/googlesearchconsole/
  index.php                      dashboard (from ecwid index.php, adapted)
  setup-wizard.php               wizard steps 1-3
  google_oauth_callback.php      reused as is
  gsc-report.php                 reused
  action-center.php              reused
  my_domains.php, sitemap.php    reused
  settings.php, faq.php          reused
  pricing.php                    adapted to license-based plans
  customer-reviews.php           reused
  api/
    register-site.php            NEW - instance creation for plugins
    license-validate.php         NEW - license key checks for the plugin
    google/                      reused as is
  includes/
    config.php                   APP_BASE = /wordpress/googlesearchconsole
    db.php, plan_guard.php       reused
    WpSite.php logic             (WixSite pattern, new table)
  admin/
    stores.php etc.              reused, adapted to WpSite
  webhook/
    license events (Stripe)      reused from ecwid pattern
  scripts/
    cron_first_report.php        reused, source_platform 'wordpress'
    cron_weekly_digest.php       reused
    cron_crash_check.php, cron_page1_check.php   reused
```

### TWO admin surfaces - do not confuse them

| | Hosted admin (ours) | Plugin settings (user's) |
|---|---|---|
| Who uses it | the MakkPress team | the end user |
| Where | makkpressapps.com/.../admin/ | inside their WP dashboard |
| What it does | sites list, plans, licenses, settings, email templates, Google account status | Connect to Dashboard button, license key entry, verification and sitemap status |
| Phase | 4 | 1-2 (part of the plugin) |


Panel conventions carried over from the other five apps:

- Table for users: **`WpSite`** (the WixSite/EcwidSite/BigcommerceSite pattern).
  Columns: id, instance_id, site_url, wp_version, email, is_active,
  created_at, digest_last_sent_at.
- `source_platform` value everywhere: **`wordpress`**.
- Collation: create new tables `utf8mb4_unicode_ci` and never join across
  tables of mixed collation without explicit COLLATE (the five-app lesson).
- Purged Tailwind builds: only classes already in the compiled stylesheet
  (the five-app lesson).

---

## 4. Database tables

New in the WordPress database:

```
WpSite            the registered sites (the WixSite pattern)
app_subscriptions  plan state (license/Stripe events) - same shape as ecwid
app_free_trials    trial state - same shape as ecwid
encharge_email_logs  only if Encharge emails are used on this platform
```

Plus, inside each WordPress install, two options in `wp_options` (not our DB):
`gscwp_instance` (instance_id, secret, site_url) and `gscwp_license`.

---

## 5. New APIs (on the hosted panel)

### POST api/register-site.php
Request: `{ site_url, wp_version, plugin_version, secret_hint }`
Behaviour: creates or returns the instance for that site_url, stores
`WpSite` row, returns `{ instance_id }`.
Idempotent on site_url: reinstalling the plugin returns the same
instance_id, so a deleted plugin does not orphan the account.

### POST api/license-validate.php
Request: `{ instance_id, license_key }`
Behaviour: checks the key against Stripe/app_subscriptions, returns
`{ valid, plan, expires_on }`. The plugin caches the answer for 24 hours
so the site keeps working if our server is briefly unreachable.

---

## 6. Free vs Paid

| | Free (Starter) | Paid (Grow/Booster - pricing TBD) |
|---|---|---|
| Verification + sitemap | yes | yes |
| Dashboard, basic reports | limited window | full |
| Alerts (crash, page-1) | no | yes |
| Weekly digest email | no | yes |
| Content-update pings | limited | unlimited |

Gating lives in two places, same as the other apps: the panel's
`plan_guard.php` for pages, and the plugin's license cache for on-site
features. Renewal, cancellation and expiry follow the ecwid Stripe pattern.

---

## 7. Phases and timeline

| Phase | What | Est. |
|---|---|---|
| 1 | Plugin skeleton, register-site flow, Connect button | 1-2 d |
| 2 | On-site: verification tag, sitemap detection | 1-2 d |
| 3 | Hosted panel: copy-adapt ecwid structure, setup wizard, Google OAuth | 3-5 d |
| 4 | Admin panel adaptation | 1-2 d |
| 5 | License + Stripe + plan gating (plugin and panel) | 2-3 d |
| 6 | End-to-end testing (see section 8) | 2-3 d |
| 7 | wp.org submission prep + review wait | 1-3 d + their queue |

v1 total: **10-13 working days** with alerts/emails/crons moved to a v1.1
update (the crons are copy jobs, they land fast afterwards).

---

## 8. End-to-end testing plan

1. Fresh WordPress (current version) on a throwaway site: install zip,
   activate, register, wizard, Google connect, verify, sitemap, dashboard
   data, one full report.
2. **Conflict matrix**: same site with Yoast active, then RankMath active,
   then both - verification tag must not double-inject, no fatals.
3. Hosting variations: at least one shared host and one nginx host - WP
   HTTP calls and permalinks behave differently.
4. Licensing: free plan limits, valid key, invalid key, expired key,
   server-down grace period (plugin must not brick the site).
5. Reinstall: plugin deleted and reinstalled on the same site must return
   the same instance_id and keep history.
6. Migration idempotency for every SQL file, run twice, snapshot compared.
7. Paid flow: test Stripe transaction, plan gate flips on the panel and in
   the plugin.

Never test against real customer records - throwaway sites only.

---

## 9. Risks

| Risk | Mitigation |
|---|---|
| Plugin conflicts (SEO plugins) | prefixed code, conflict matrix testing |
| wp.org review delays | direct-download path works from day one; wp.org adds reach later |
| Server down breaks sites | plugin caches license + instance locally, 24h grace |
| Users on old WP/PHP | declare minimums, test on PHP 7.4 and 8.x |
| Duplicate verification with SEO plugins | detect existing GSC meta before injecting |

---

## 10. Open decisions (before phase 1 starts)

1. wp.org first or direct-download first (recommend: build direct, submit
   wp.org in parallel).
2. Pricing for the WordPress plans (mirror Wix or different).
3. Product name on WordPress - "Google Indexing" style name (the other_apps
   list already uses that) vs GSC phrasing, since GSC is Google's trademark
   territory.
4. Whether v1 ships with Encharge emails or defers them.

---

Last updated: 2026-10-08
