# Verified Baseline - what the five existing apps actually contain

Verified 8 Oct 2026 by direct file inventory of wix, shopify, ecwid, hl and
bigcommerce. This is the evidence base every WordPress phase document refers
to. Only files present in at least three of the five apps count as "common
core"; everything else is platform-specific and listed separately.

---

## 1. Common core - user panel (root)

| File | Present in | Notes for WordPress |
|---|---|---|
| sign-in.php | 5/5 | becomes "already registered via plugin" landing |
| index.php | 5/5 | the dashboard; biggest file (3-5k lines) |
| setup-wizard.php + step-one/two/three.php | 5/5 | wizard steps |
| wizard-instructions.php | 4/5 | copy |
| verify_domain.php | 5/5 | GSC verification screen |
| connect_google.php + google_oauth_callback.php | 5/5 | OAuth, reused as is |
| gsc-report.php | 5/5 | reports with tabs (has the CSV download now) |
| action-center.php | 5/5 | action center |
| my_domains.php | 5/5 | copy |
| sitemap.php | 5/5 | sitemap management |
| settings.php | 5/5 | user settings |
| instructions.php, faq.php | 5/5 | copy |
| pricing.php | 5/5 | adapted to license plans |
| current-plan.php, upgrade.php | 5/5 | copy |
| other-apps.php | 5/5 | copy + click tracking (wix pattern) |
| customer-reviews.php | 5/5 | copy |
| manage-tickets.php | 5/5 | tickets |
| thank-you.php, logout.php | 5/5 | copy |
| why-first-report.php | 5/5 | copy |

## 2. Common core - api/

| File | Present in | Notes |
|---|---|---|
| google/init_client.php, get_auth_url.php | 5/5 | Google client bootstrap - reused as is |
| google/get_gsc_report.php, get_gsc_regex.php | 5/5 | data APIs - as is |
| google/verify_domain.php, create_property.php, insert_ownership.php, check_*.php | 5/5 | verification set - as is |
| google/submit_sitemap.php, sync_sitemaps.php, fetch_sitemaps.php, list_sitemaps.php, get_sitemap_*.php | 5/5 | sitemap set - as is |
| google/inspect_url.php, get_rich_results.php, get_gsc_404_report.php | 4-5/5 | copy |
| billing.php | 5/5 | rewritten for Stripe + license (see PHASE-5) |
| subscription-status.php, whoami.php | 5/5 | small adaptations |
| start_trial.php | 4-5/5 | keep |
| get_plans.php | 5/5 | DB-driven plans (wix pattern) |
| setup-wizard-status.php, setup-wizard-steps-check.php, update-step.php | 5/5 | copy |
| tickets: create_ticket.php, get-tickets.php, post_ticket_message.php, change_ticket_status.php, delete_ticket.php, upload_ticket_attachment.php, serve_ticket_attachment.php, send_ticket_email.php | 5/5 | copy |
| reviews: check-review.php, save-review.php, review-popup.php | 5/5 | copy |
| faq.php, instruction.php | 5/5 | copy |
| run_speed_test.php, run_schema_check.php | 5/5 | PSI + schema, uses includes/psi.php |
| already-submitted.php | 5/5 | copy |
| cancellation-final.php, cancellation-track.php | 5/5 | adapted to Stripe cancel |
| index-apps.php | 5/5 | other-apps strip (wix version has click tracking) |
| redirect-other-app.php | 1/5 (wix) | bring to WordPress from day one |

## 3. Common core - includes/

config.php, db.php, credentials.php, plan_guard.php, psi.php, mailer.php,
EmailHelper.php, email_payload.php, liquid_lite.php, sample_data.php,
sample_fallback.php, schema_check.php, sidebar_data.php, site_health.php,
tickets_min.php - 5/5 or 4/5. All copied; only config.php is rewritten
(APP_BASE, DB name, platform constants).

## 4. Common core - scripts/ (crons)

cron_first_report.php, cron_weekly_digest.php, cron_crash_check.php,
cron_page1_check.php, cron_payload_queue.php, refresh_all_tokens.php,
update_user_encharge.php, direct_user_post_encharge.php - 5/5.
Copy with source_platform 'wordpress'. refresh_all_tokens refreshes Google
OAuth tokens (still needed - users connect Google to OUR panel).

## 5. Common core - admin/

index.php (admin dashboard), stores.php, store-view.php, sign-in.php,
settings.php, email-templates.php + email-template-add/edit.php,
tickets.php + ticket-detail/view.php, reviews.php, faq.php,
our-apps.php + app-add/edit.php, cancel-plan.php, instructions.php,
logout.php - 4-5/5. Plus mail-logs/store-emails.php (3/5).
All copied; stores.php reads WpSite instead of the platform site table.

## 6. Common core - partials/

layouts/layoutTop.php, layoutBottom.php, head.php, navbar.php, sidebar.php,
footer.php, breadcrumb.php, script.php, and the popups (cancel-popup,
trial-popup, review-popup, preview-popup, ticket-submit-popup,
first-report-banner, action-center-bell) - 5/5. Copy; only sidebar links and
brand text change.

## 7. Platform-specific (NOT copied to WordPress)

| App | Specifics | Why |
|---|---|---|
| wix | WixOAuthClient.php, magic-login.php, paid-plan-*.php webhooks, cancellation.php, rich-results-report.php, regex-report.php | Wix market OAuth + Wix billing + Wix-only reports |
| shopify | app_subscriptions_*.php, webhook_gdpr_*.php, shop_update.php, app.php | Shopify billing + mandatory GDPR webhooks |
| ecwid | HighLevelTokenManager.php (legacy), payment.php (Stripe), spider-hook.php | legacy + Stripe entry |
| hl | encryption.php, cron_trial_reminder.php, no webhook dir | HL tokens + agency quirks |
| bigcommerce | credentials_big.php, payment.php, uninstall.php | BC OAuth + Stripe |

WordPress equivalents to BUILD new: register-site API, license-validate API,
the plugin itself, uninstall handling (plugin deactivate/uninstall hooks
calling our API to mark the site inactive - replaces app-uninstall).

---

## 8. Per-app platform constants (the "adapt" list)

| Constant | wix | shopify | ecwid | hl | bigcommerce | **wordpress (target)** |
|---|---|---|---|---|---|---|
| Site table | WixSite | ShopifySite | EcwidSite | HlSite | BigcommerceSite | **WpSite** |
| source_platform | Wix | Shopify | Ecwid | HighLevel | BigCommerce | **WordPress** |
| APP_BASE | /wix/googlesearchconsole | /shopify/... | /ecwid/... | /hl/... | /bigcommerce/... | **/wordpress/googlesearchconsole** |
| Billing | Wix checkout | Shopify billing | Stripe | custom | Stripe | **Stripe + license keys** |
| Install identity | market redirect | market OAuth | market OAuth | agency sub-account | market OAuth | **plugin register-site** |
| DB name | wix db | shopify db | ecwid-googlesearchconsole | hl db | bigcommerce_googlesearchconsole | **wordpress db (TBD)** |

---

## 9. Shared DB tables (identical shapes on every platform)

pricing_plans, plan_features, plan_feature_map, plan_feature_text,
plan_feature_overrides, app_subscriptions, app_free_trials, encharge_email_logs,
other_apps, other_app_clicks (wix only today), gsc_domain_verifications,
gsc_query_daily, gsc_page_query_daily, gsc_ctr_curve, gsc_page1_seen,
gsc_snapshot_runs, psi_results, tickets, ticket_messages, ticket_attachments,
ticket_status_history, faqs, faq_categories, documentation, documentation_categories,
admin_users, admin_settings, sessions, other_apps, app_reviews, otp_verifications.

For WordPress: create the full set fresh in its own database (the
wordpress/googlesearchconsole DB), shapes copied from the ecwid dump
(cleanest recent baseline), names unchanged.
