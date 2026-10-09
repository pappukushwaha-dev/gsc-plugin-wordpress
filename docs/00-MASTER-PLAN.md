# GSC Plugin for WordPress - Master Plan

Status: PLANNING - no code written yet. Work starts only after this
document set is approved.

Read order:

1. VERIFIED-BASELINE.md - what the five live apps contain (evidence)
2. PHASE-1 .. PHASE-7 - the build, phase by phase, each with a checklist
3. README.md - short overview (older, keep for quick reference)

---

## The product, in one paragraph

A WordPress plugin that puts the site's Google Search Console work on
rails (verification, sitemap, install-into-site), and a hosted panel at
makkpressapps.com/wordpress/googlesearchconsole/ that carries everything
the other five platform apps already do: setup wizard, Google OAuth,
dashboards, reports, action center, alerts, tickets, other-apps cross
promotion, and free/paid plans billed through Stripe with license keys.

## The two parts

| Part | Lives on | Built from |
|---|---|---|
| The plugin (gsc-plugin-wordpress) | the user's WordPress site | NEW code, WP standards |
| The hosted panel | makkpressapps.com | copy-adapt of the ecwid app (cleanest baseline), wix for the pieces ecwid lacks |

## Phase map

| Phase | Doc | Deliverable | Est. |
|---|---|---|---|
| 1 | PHASE-1-plugin-skeleton.md | plugin installs, registers site, Connect button opens the panel | 1-2 d |
| 2 | PHASE-2-onsite-features.md | verification tag, sitemap detection, plan-gated on-site features | 1-2 d |
| 3 | PHASE-3-hosted-panel.md | full user panel + setup wizard + Google OAuth on our domain | 3-5 d |
| 4 | PHASE-4-admin-panel.md | admin panel for the WordPress platform | 1-2 d |
| 5 | PHASE-5-billing-licensing.md | Stripe + license keys + plan gating (panel and plugin) | 2-3 d |
| 6 | PHASE-6-testing.md | end-to-end test matrix executed and signed off | 2-3 d |
| 7 | PHASE-7-launch-wporg.md | direct launch + wp.org submission pack | 1-3 d + review queue |

v1 total: 10-13 working days (alerts/digest emails land in v1.1 - the
crons are copy jobs from the baseline).

## Identity model (the one real architectural difference)

Every other platform got its instance_id from a marketplace at install
time. WordPress has no market, so the plugin mints the instance:

```
activate plugin
  -> wp_remote_post POST api/register-site.php { site_url, wp_version, plugin_version }
  -> server upserts WpSite (idempotent on site_url), returns instance_id
  -> plugin stores instance_id in wp_options, shows Connect button
  -> Connect button opens the hosted setup wizard with instance_id
```

From that point the hosted panel cannot tell a WordPress user from a Wix
user - same wizard, same Google OAuth, same tables.

## Conventions carried over (non-negotiable)

- source_platform value: `wordpress`
- Site table: `WpSite`; own database for the platform
- New tables utf8mb4_unicode_ci; never join mixed-collation tables without
  explicit COLLATE
- Tailwind utilities only from the compiled stylesheet of this platform
  (audit before shipping a page)
- English-only delivered code, no em dashes, no emoji
- Test data never touches real customer records

## Open decisions (blockers for phase 1)

1. Direct-download first with wp.org in parallel (recommended) - confirm
2. WordPress plan pricing (mirror Wix $0/9/20 or own numbers)
3. Public product name on WordPress (avoid GSC trademark phrasing)
4. Encharge emails in v1 or v1.1
5. Database name for the platform (suggest: wordpress-googlesearchconsole)
