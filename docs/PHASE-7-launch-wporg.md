# PHASE 7 - Launch: direct download and WordPress.org

Deliverable: the plugin is obtainable two ways - direct from
makkpressapps.com (live immediately) and listed on WordPress.org (organic
reach, review queue).

Est. 1-3 days of work; the wp.org review itself takes their queue time
(typically a few days to two weeks).

---

## 1. Direct launch (goes live first)

- Landing page on makkpressapps.com: what it does, screenshots, download
  zip (free), pricing page link for paid
- The zip is the exact tested build from PHASE-6
- Version + changelog maintained in the plugin readme from day one
- Support entry point: manage-tickets (panel) plus the WP support contact

## 2. WordPress.org submission pack

- readme.txt in wp.org format (name, description, FAQ, screenshots,
  changelog)
- Plugin slug: e.g. `gsc-indexing-tool` (final name = open decision, avoid
  Google trademark phrasing in the slug)
- Free version scope on wp.org: verification + sitemap + basic dashboard
  link (paid features clearly marked, no nag-ware behaviour - wp.org
  rejects pushy upsells)
- Code review notes: no obfuscation, no external tracking without
  disclosure, data handling declared (we send site_url + version to our
  API - must be disclosed in the readme per guidelines)
- Submit, respond to reviewer feedback, expect 1-2 rounds

## 3. Post-launch v1.1 backlog (already built on other platforms)

- Alerts: crash check + page-1 breakthrough (crons, copy from baseline)
- Weekly digest email (cron, copy) + Encharge decision
- Payload queue + email payload (copy)
- other-apps strip on more pages

## 4. Versioning

- 1.0.0 = v1 scope as approved
- 1.1.0 = alerts + digest
- Semver, changelog in readme.txt, plugin update served by wp.org (free)
  and by our own update endpoint (paid direct downloads)
