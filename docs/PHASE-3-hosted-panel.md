# PHASE 3 - The hosted panel

Deliverable: makkpressapps.com/wordpress/googlesearchconsole/ running the
full user panel - setup wizard, Google OAuth, dashboard, reports, action
center, tickets, other-apps, settings - functionally identical to the
five live apps.

Est. 3-5 days. Source baseline: the ECWID app (cleanest recent structure),
with wix as source for the pieces ecwid lacks (see VERIFIED-BASELINE).

---

## 1. Platform bootstrap

- New database: `wordpress-googlesearchconsole` (name TBD - open decision)
- Full table set created from the ecwid dump shapes (see
  VERIFIED-BASELINE section 9), site table renamed **WpSite**
- New folder on the server: /var/www/html/wordpress/googlesearchconsole/
- New git repo: gsc-app-wordpress (same layout as the other five)
- includes/config.php: APP_BASE = /wordpress/googlesearchconsole,
  platform constants, source_platform 'wordpress', WpSite table name
- Identity: instance_id comes from the plugin (PHASE-1 register-site);
  config.php bootstrap accepts it from session or GET exactly like the
  other five

## 2. File-by-file map (source -> target -> change)

### Verbatim copy (change only the platform string inside, if any)

| Source (ecwid unless noted) | Target | Notes |
|---|---|---|
| includes/db.php, credentials.php | same | DB creds only |
| includes/plan_guard.php | same | table names per platform constant |
| includes/psi.php, schema_check.php, site_health.php, sidebar_data.php, tickets_min.php, mailer.php, EmailHelper.php, email_payload.php, liquid_lite.php, sample_data.php, sample_fallback.php | same | as is |
| includes/google/* (whole dir) | same | Google client + GSC endpoints, verified in all five |
| api/google/* (whole dir) | same | as is |
| api/get_plans.php, whoami.php, subscription-status.php, start_trial.php | same | DB-driven, no platform strings |
| api/setup-wizard-status.php, setup-wizard-steps-check.php, update-step.php | same | copy |
| api/tickets (all 8 files) | same | copy |
| api/reviews (3 files), faq.php, instruction.php | same | copy |
| api/run_speed_test.php, run_schema_check.php, already-submitted.php | same | copy |
| api/cancellation-final.php, cancellation-track.php | adapt | cancel hook -> Stripe (PHASE-5) |
| scripts/ (all 8 crons) | same | source_platform 'wordpress'; payload queue comes with the baseline |
| gsc-report.php | same | includes the CSV download |
| action-center.php, my_domains.php, sitemap.php, settings.php, instructions.php, faq.php | same | copy |
| manage-tickets.php, customer-reviews.php, thank-you.php, logout.php, why-first-report.php, wizard-instructions.php | same | copy |
| verify_domain.php, connect_google.php, google_oauth_callback.php | same | OAuth, reused in all five |
| partials/* (layoutTop/Bottom, head, navbar, sidebar, footer, breadcrumb, script, popups) | adapt | sidebar links, brand strings |

### Adapt (platform strings + logic touches)

| File | Change |
|---|---|
| index.php (dashboard) | platform strings; sidebar API call path |
| setup-wizard.php + step-one/two/three.php | WP-specific wording (plugin handles the tag) |
| pricing.php + current-plan.php + upgrade.php | license-based plans (PHASE-5) |
| other-apps.php | tracking link pattern from wix (redirect-other-app) |
| sign-in.php | "registered via plugin" - no password form in v1? (open decision: account password vs magic link) |
| includes/config.php | full rewrite of constants |

### Brand new (no source)

| File | Purpose |
|---|---|
| api/register-site.php | PHASE-1 contract |
| api/license-validate.php | PHASE-5 contract |
| api/push-verify-token.php, api/instance-meta.php | PHASE-2 contract |
| webhook/payment.php | Stripe events (PHASE-5, from bc pattern) |
| webhook/site-deactivate.php | plugin deactivation marks WpSite inactive |

### Deliberately NOT copied

rich-results-report.php / regex-report.php (wix-only reports), magic-login
(wix), HighLevelTokenManager (hl legacy), GDPR webhooks (shopify store
requirement), all *-old.php / *_old files everywhere.

## 3. Build order inside the phase

1. Repo + folder + config + DB migration (WpSite + shared tables)
2. Setup wizard end to end (register -> wizard -> Google OAuth -> verify
   -> sitemap) using a real throwaway site
3. Dashboard + reports (index.php, gsc-report.php + api/google)
4. Action center, my_domains, sitemap, settings, faq, instructions
5. Tickets + reviews + other-apps (with tracking)
6. Full pass: every page rendered, every API called once, error log clean

## 4. Checklist

- [ ] fresh install flow works end to end on a throwaway site
- [ ] Google OAuth connects, tokens stored, refresh works
- [ ] dashboard renders with real GSC data
- [ ] gsc-report all tabs return data; CSV download works
- [ ] crons run: first report, weekly digest dry, crash check, page1, payload queue
- [ ] other-apps tracking writes other_app_clicks rows
- [ ] every page's class audit passes against this platform's compiled CSS
- [ ] no PHP warnings in error log across the whole panel
