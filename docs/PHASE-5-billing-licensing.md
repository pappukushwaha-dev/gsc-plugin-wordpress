# PHASE 5 - Billing, licensing and plan gating

Deliverable: free works forever, paid unlocks the gated features, money
flows through Stripe, and the WordPress platform bills nobody through a
marketplace (there is no marketplace).

Est. 2-3 days. Depends on PHASE-3 (panel) and touches PHASE-1 (plugin).

---

## 1. The model

- Plans live in pricing_plans (get_plans.php already reads them) - the
  WordPress database carries its own plan rows (open decision: pricing).
- Paid = a Stripe subscription tied to the site's license key.
- The plugin holds the license key; the panel holds the subscription
  state. The two meet on license-validate.php.

## 2. Licence keys

- Keys generated at first payment and on request: format
  `MPWP-XXXX-XXXX-XXXX` (prefix MPWP = platform).
- Stored hashed (like credentials.php pattern) in license_keys table:
  id, key_hash, instance_id, plan_code, status (active/expired/revoked),
  stripe_subscription_id, created_at, expires_on.
- api/license-validate.php: { instance_id, license_key } -> checks hash,
  subscription state via Stripe, returns { valid, plan, expires_on }.
  Plugin caches the answer for 24h (grace on our downtime).

## 3. Stripe (reuse the ecwid pattern)

- webhook/payment.php (from bigcommerce/ecwid shape): checkout.completed,
  subscription renewal paid, subscription cancelled/expired events ->
  update app_subscriptions + license_keys.
- Checkout session created from pricing.php Buy button (Stripe
  Checkout API, ecwid's payment.php logic).
- Cancel: current-plan.php -> Stripe portal link or cancel endpoint ->
  cancellation-final.php flow (grace period till expires_on, then
  plan_guard flips to free - existing logic).

## 4. Gating - both ends

Panel (plan_guard.php, existing logic):
- allowed = paid plan active or active trial; source rows are
  app_subscriptions/app_free_trials exactly like the other apps.

Plugin (new, small):
- license-validate answer cached 24h in wp_options
- on-site gates in v1: content-update ping daily limit (free) vs
  unlimited (paid)
- expired key -> plugin shows renew banner, site keeps working (never
  breaks the user's site over money)

## 5. Trials

- start_trial.php reused: 7-day full access, app_free_trials row, same
  shape as the other apps. Entry point: panel pricing page (WP users have
  accounts here, unlike pure plugin-only platforms).

## 6. Checklist

- [ ] Stripe test-mode checkout -> subscription active -> license issued
- [ ] license-validate returns valid for the key
- [ ] renewal event extends expires_on
- [ ] cancel event -> grace till expires_on -> then free (panel gates)
- [ ] invalid/expired key -> plugin renew banner, no site breakage
- [ ] free plan ping limit enforced, paid unlimited
- [ ] get_plans.php shows the WordPress plan rows on pricing.php
