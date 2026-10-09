# PHASE 6 - End-to-end testing

Deliverable: the full matrix below executed and signed off. No launch
without every box ticked.

Est. 2-3 days. The first WordPress build always finds surprises here -
the estimate exists for exactly that.

---

## 1. Environment

- Throwaway WordPress installs ONLY (never real customer records):
  - a current-version WP on local/staging (PHP 8.x)
  - one on PHP 7.4 (minimum declared)
  - one shared host + one nginx host (different HTTP behaviour)
- Throwaway Google account + GSC property for verification flows
- Stripe in test mode
- The hosted panel pointed at the test database

## 2. The main journey (must pass in one sitting)

1. Install plugin zip -> activate -> registered (WpSite row exists)
2. Connect to Dashboard -> setup wizard opens with correct instance
3. Google account connect (OAuth) -> tokens stored, refresh works
4. Verification token -> pushed to plugin -> meta in wp_head -> GSC
   verifies
5. Sitemap detected -> submitted to GSC -> status shows
6. Dashboard renders with real GSC data
7. gsc-report.php: all tabs return data, CSV download works
8. Action center, my_domains, settings - all functional
9. Other Apps: click -> tracked -> redirect works (admin App Clicks page
   shows the row)
10. Tickets: create from panel, admin replies
11. Plan upgrade (test Stripe) -> gates flip on panel and plugin
12. Cancel -> grace till expiry -> then free gates
13. Uninstall plugin -> site clean (no leftover meta), WpSite inactive
14. Reinstall on the same site -> same instance_id, history intact

## 3. Conflict matrix (WordPress-specific, mandatory)

Run the main journey with each combination:

| Combination | Watch for |
|---|---|
| Yoast active | double verification meta, sitemap collision |
| RankMath active | same |
| Yoast + RankMath together | same (broken configs exist in the wild) |
| A caching plugin (WP Super Cache / W3TC) | cached pages missing the meta tag - purge behaviour |
| Security plugin (Wordfence) | our API calls blocked? |

## 4. Licensing edge cases

- validate endpoint unreachable -> plugin uses 24h cache, site keeps
  working, warning banner only in WP admin
- key entered with spaces/wrong case -> normalised or rejected cleanly
- key used on a second site -> rejected (one key, one site)

## 5. Panel hardening

- register-site: replay abuse (same site_url hammering) -> rate limit
- every API: instance must exist and be active
- error log clean across the whole panel pass
- migration SQL run twice -> second run changes nothing (snapshot diff)

## 6. Sign-off

Every box above ticked with the date and tester initials, pasted into
this file at the bottom. Then PHASE-7.
