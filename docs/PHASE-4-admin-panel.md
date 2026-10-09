# PHASE 4 - Admin panel for the WordPress platform

Deliverable: /wordpress/googlesearchconsole/admin/ - the operator panel
for the team, matching the ecwid/bigcommerce admin (the current best
shape: stores.php with stores-active/inactive, store-emails.php,
store-view.php).

Est. 1-2 days. Depends on PHASE-3.

---

## 1. Files (copy-adapt from ecwid admin, the closest current shape)

| Source (ecwid admin) | Target | Change |
|---|---|---|
| sign-in.php, logout.php | same | admin credentials (admin_users table) |
| index.php | same | dashboard stats read WpSite |
| stores.php | same | WpSite list; extra column: WP version, plugin version, last seen |
| stores-active.php, stores-inactive.php | same | split by is_active |
| store-view.php | same | per-site detail; plugin + license block added |
| store-emails.php | same | email log (if Encharge in v1) |
| tickets.php, ticket-view.php | same | copy |
| email-templates.php + add/edit | same | copy |
| our-apps.php, app-add.php, app-edit.php | same | copy - manages other_apps for this platform |
| reviews.php, faq.php, settings.php, instructions.php, cancel-plan.php | same | copy |
| other-app-tracking.php | bring from wix | click tracking report, source_platform filter ready |
| core/* (SessionManager, AuthController, StoreManager, TicketManager, ReviewManager, Faq, HeaderData, OtherAppsManager, email/) | same | site table constant -> WpSite |

## 2. WordPress-specific admin additions

- Stores list columns: wp_version + plugin_version + last_seen (the plugin
  reports these on register and on weekly check-in)
- Store view: "Send new verification token to plugin" action (PHASE-2
  push endpoint, operator-triggered)
- License block on store view: key, plan, expires_on, Validate/Revoke

## 3. Checklist

- [ ] admin sign-in works (admin_users in the new DB)
- [ ] stores list shows registered plugin sites with versions
- [ ] store view shows license block
- [ ] tickets/reviews/faq/our-apps CRUD all work
- [ ] other-app-tracking page lists clicks with source_platform = wordpress
- [ ] class audit against this platform's admin CSS: all present
