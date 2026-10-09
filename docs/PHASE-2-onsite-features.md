# PHASE 2 - On-site features (what the plugin does inside WordPress)

Deliverable: the plugin does the on-site Google work itself - the part
that makes it genuinely useful on WordPress and not just a bookmark to
the panel.

Est. 1-2 days. Depends on PHASE-1.

---

## 1. GSC verification tag (the front door)

- Wizard (hosted) generates the verification token - reuse
  api/google/generate_meta.php logic.
- Hand-off to the plugin: the wizard shows a one-click button
  "Push token to plugin" -> POST api/push-verify-token.php (admin auth:
  instance_id + secret) -> stores the token in the instance record.
- Plugin polls (or receives on next settings load) and injects into
  `wp_head`:
  `<meta name="google-site-verification" content="...">`.
- Before injecting: check for an existing google-site-verification meta
  (Yoast/RankMath may already print one). If present, do not inject a
  second; tell the wizard "already handled by another SEO plugin".
- Server-side confirmation loop: reuse api/google/check_verification.php
  (existing, verified in all five apps).

## 2. Sitemap

- WordPress ships wp-sitemap.xml natively (WP 5.5+).
- Plugin detects it (`home_url('/wp-sitemap.xml')` HEAD check) and reports
  the URL back to the panel (instance_meta).
- Wizard shows: "your sitemap: <url> - Submit to Google" -> reuse
  api/google/submit_sitemap.php as is.
- Sitemap status on the plugin settings page (submitted / last checked) -
  reuse api/google/check_sitemap_status.php.

## 3. Content-update ping (the USP - keep behind the paid gate)

- Hook `save_post`: on publish/update of a public post type, queue a ping
  to Google (Indexing API where applicable, else sitemap refresh + ping
  endpoint).
- Rate-limited per day on the free plan (constant, e.g. 10/day), unlimited
  on paid - the plugin reads its cached plan from the license check
  (PHASE-5).
- A small queue table in wp_options (no custom tables in v1) with
  max-attempts, so a failed ping retries without hammering.

## 4. What the plugin does NOT do in v1

- No analytics storage on the site - all reporting lives in the panel.
- No email sending.
- No custom tables (options only) - keeps uninstall trivial.

## 5. New server-side endpoints for this phase

| Endpoint | Purpose | Reuse |
|---|---|---|
| api/push-verify-token.php | wizard -> plugin token hand-off | new (small) |
| api/google/check_verification.php | confirm verification | existing |
| api/google/submit_sitemap.php | submit wp-sitemap.xml | existing |
| api/google/check_sitemap_status.php | status | existing |
| api/instance-meta.php | plugin reports sitemap/ping state | new (small) |

## 6. Checklist

- [ ] token pushed from wizard lands in plugin, meta appears in wp_head
- [ ] Yoast active -> no double meta, wizard says "already handled"
- [ ] RankMath active -> same
- [ ] wp-sitemap.xml detected, submitted, status shows in panel
- [ ] publish a post -> ping fires (paid) / rate-limited (free)
- [ ] plugin settings show verification + sitemap state
- [ ] deactivating plugin removes the meta tag (clean site)
