<?php
/**
 * The plugin's admin presence.
 *
 * - A top-level sidebar menu ("GSC Plugin"), not buried in Settings.
 * - Links on the Plugins list row: version context, the company site,
 *   the MCP connector, and a direct button into this page.
 * - The page itself: a premium-styled overview with the dashboard
 *   connection front and centre.
 *
 * Notices: WordPress injects every admin notice after the first
 * heading it finds inside .wrap. This page's heading lives inside the
 * gradient hero, so without the hr.wp-header-end marker below, WP
 * would drop foreign notices into the middle of the hero and tear the
 * layout. The marker (plus the custom gscwp-alert styling for our own
 * transient notices) keeps every message above the page content.
 *
 * Every action is nonce checked and capability checked; every value
 * printed is escaped.
 */

if (!defined('ABSPATH')) {
    exit;
}

class GSCWP_AdminPage
{
    const CAP = 'manage_options';
    const MENU_SLUG = 'gscwp';

    public static function init(): void
    {
        add_action('admin_menu', [self::class, 'addMenu']);
        add_action('admin_init', [self::class, 'handleActions']);
        add_filter('plugin_action_links_' . GSCWP_PLUGIN_BASENAME, [self::class, 'actionLinks']);
        add_filter('plugin_row_meta', [self::class, 'rowMeta'], 10, 2);
    }

    /**
     * Top-level sidebar entry - the user asked for it "samne", not
     * inside Settings.
     */
    public static function addMenu(): void
    {
        add_menu_page(
            __('GSC Plugin', 'gscwp'),
            __('GSC Plugin', 'gscwp'),
            self::CAP,
            self::MENU_SLUG,
            [self::class, 'render'],
            'dashicons-chart-line',
            56
        );

        // A matching first submenu keeps the highlighted label from
        // reading as a duplicate of the menu title.
        add_submenu_page(
            self::MENU_SLUG,
            __('GSC Plugin', 'gscwp'),
            __('Dashboard', 'gscwp'),
            self::CAP,
            self::MENU_SLUG,
            [self::class, 'render']
        );
    }

    /**
     * Plugins list row, action-link slot (next to Activate/Deactivate):
     * the entry button first, then the company and MCP connector sites.
     */
    public static function actionLinks(array $links): array
    {
        $open = sprintf(
            '<a href="%s" style="font-weight:600;">%s</a>',
            esc_url(admin_url('admin.php?page=' . self::MENU_SLUG)),
            esc_html__('Open GSC Plugin', 'gscwp')
        );
        $company = sprintf(
            '<a href="%s" target="_blank" rel="noopener">%s</a>',
            esc_url(GSCWP_COMPANY_URL),
            esc_html__('MakkPress Apps', 'gscwp')
        );
        $mcp = sprintf(
            '<a href="%s" target="_blank" rel="noopener">%s</a>',
            esc_url(GSCWP_MCP_URL),
            esc_html__('MCP Connector', 'gscwp')
        );

        array_unshift($links, $open, $company, $mcp);
        return $links;
    }

    /**
     * Plugins list row, meta slot (under the description, beside the
     * native version label).
     */
    public static function rowMeta(array $meta, string $file): array
    {
        if ($file !== GSCWP_PLUGIN_BASENAME) {
            return $meta;
        }

        $meta[] = sprintf(
            '<a href="%s" target="_blank" rel="noopener">%s</a>',
            esc_url(GSCWP_COMPANY_URL),
            esc_html__('makkpressapps.com', 'gscwp')
        );
        $meta[] = sprintf(
            '<a href="%s" target="_blank" rel="noopener">%s</a>',
            esc_url(GSCWP_MCP_URL),
            esc_html__('orgalytics.ai (MCP)', 'gscwp')
        );
        $meta[] = sprintf(
            '<a href="%s">%s</a>',
            esc_url(admin_url('admin.php?page=' . self::MENU_SLUG)),
            esc_html__('Open GSC Plugin', 'gscwp')
        );
        return $meta;
    }

    /** One transient-based notice slot; set by register/actions. */
    public static function setNotice(string $message, string $type = 'success'): void
    {
        set_transient('gscwp_admin_notice', ['msg' => $message, 'type' => $type], 60);
    }

    public static function handleActions(): void
    {
        if (!isset($_POST['gscwp_action'])) {
            return;
        }
        if (!current_user_can(self::CAP)) {
            return;
        }
        if (!isset($_POST['gscwp_nonce'])
            || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['gscwp_nonce'])), 'gscwp_action')) {
            return;
        }

        $action = sanitize_key(wp_unslash($_POST['gscwp_action']));

        if ($action === 'register') {
            GSCWP_Register::register();
            wp_safe_redirect(admin_url('admin.php?page=' . self::MENU_SLUG));
            exit;
        }
    }

    /** Google "G" mark, inline so nothing external loads. */
    private static function googleMark(int $size = 30): string
    {
        return '<svg viewBox="0 0 48 48" width="' . $size . '" height="' . $size . '" aria-hidden="true">'
            . '<path fill="#FFC107" d="M43.6 20.5H42V20H24v8h11.3C33.7 32.7 29.3 36 24 36c-6.6 0-12-5.4-12-12s5.4-12 12-12c3.1 0 5.9 1.2 8 3l5.7-5.7C34.3 6.1 29.4 4 24 4 13 4 4 13 4 24s9 20 20 20 20-9 20-20c0-1.2-.1-2.4-.4-3.5z"/>'
            . '<path fill="#FF3D00" d="M6.3 14.7l6.6 4.8C14.7 15.1 19 12 24 12c3.1 0 5.9 1.2 8 3l5.7-5.7C34.3 6.1 29.4 4 24 4 16.3 4 9.7 8.3 6.3 14.7z"/>'
            . '<path fill="#4CAF50" d="M24 44c5.2 0 9.9-2 13.4-5.2l-6.2-5.2C29.2 35.1 26.7 36 24 36c-5.3 0-9.7-3.3-11.3-8l-6.5 5C9.5 39.6 16.2 44 24 44z"/>'
            . '<path fill="#1976D2" d="M43.6 20.5H42V20H24v8h11.3c-.8 2.3-2.3 4.3-4.1 5.7l6.2 5.2C36.9 39.2 44 34 44 24c0-1.2-.1-2.4-.4-3.5z"/>'
            . '</svg>';
    }

    /**
     * The connector illustration for the hero: WordPress on the left,
     * Google on the right, an animated dashed link between them and a
     * live check once connected. Inline SVG - no external asset, crisp
     * on every display.
     */
    private static function connectorIllustration(bool $connected): string
    {
        $line  = $connected ? 'rgba(255,255,255,.95)' : 'rgba(255,255,255,.6)';
        $badge = $connected ? '#3ddc84' : 'rgba(255,255,255,.35)';

        return '<svg class="gscwp-link-art" viewBox="0 0 250 120" width="250" height="120" aria-hidden="true">
            <defs>
                <linearGradient id="gscwpNodeA" x1="0" y1="0" x2="1" y2="1">
                    <stop offset="0" stop-color="#ffffff"/><stop offset="1" stop-color="#e8edff"/>
                </linearGradient>
            </defs>
            <!-- WordPress node -->
            <g>
                <circle cx="40" cy="60" r="27" fill="url(#gscwpNodeA)"/>
                <circle cx="40" cy="60" r="27" fill="none" stroke="rgba(255,255,255,.55)" stroke-width="1.5"/>
                <circle cx="40" cy="60" r="19" fill="none" stroke="#21759b" stroke-width="2.4"/>
                <text x="40" y="66" text-anchor="middle" font-family="Georgia, serif"
                      font-size="19" font-weight="700" fill="#21759b">W</text>
            </g>
            <!-- animated link line -->
            <path class="gscwp-dash" d="M70 60 H 180" fill="none" stroke="' . $line . '"
                  stroke-width="2.6" stroke-linecap="round" stroke-dasharray="1 9"/>
            <!-- connector badge in the middle -->
            <circle cx="125" cy="60" r="15" fill="rgba(255,255,255,.18)" stroke="rgba(255,255,255,.6)" stroke-width="1.5"/>
            <path d="M118 60h14M120 56h10M120 64h10" stroke="#fff" stroke-width="2.2"
                  stroke-linecap="round" fill="none"/>
            <!-- status badge on the link -->
            <circle cx="125" cy="37" r="9" fill="' . $badge . '"/>'
            . ($connected
                ? '<path d="M121 37l3 3 5-6" stroke="#14532d" stroke-width="2.2" stroke-linecap="round"
                      stroke-linejoin="round" fill="none"/>'
                : '<circle cx="125" cy="37" r="3.2" fill="#4c2bd4"/>')
            . '<!-- Google node -->
            <g transform="translate(184,34)">' . self::googleMark(52) . '</g>
        </svg>';
    }

    /** One status card for the overview grid. */
    private static function statusCard(string $icon, string $tint, string $label, string $value, string $hint, bool $good): string
    {
        $dotColor = $good ? '#00a32a' : '#dba617';
        return sprintf(
            '<div class="gscwp-card">
                <div class="gscwp-card-head">
                    <span class="gscwp-ic" style="background:%1$s;">%2$s</span>
                    <span class="gscwp-card-label">%3$s</span>
                    <span class="gscwp-dot" style="background:%4$s;"></span>
                </div>
                <div class="gscwp-card-value">%5$s</div>
                <div class="gscwp-card-hint">%6$s</div>
            </div>',
            $tint,
            $icon,
            esc_html($label),
            $dotColor,
            $value, // already escaped by the caller
            esc_html($hint)
        );
    }

    /** One "what's inside" feature tile - the value story of the plugin. */
    private static function featureTile(string $icon, string $tint, string $color, string $title, string $line): string
    {
        return sprintf(
            '<div class="gscwp-feat">
                <span class="gscwp-ic" style="background:%1$s;color:%2$s;">%3$s</span>
                <div>
                    <div class="gscwp-feat-t">%4$s</div>
                    <div class="gscwp-feat-d">%5$s</div>
                </div>
            </div>',
            $tint,
            $color,
            $icon,
            esc_html($title),
            esc_html($line)
        );
    }

    /** The setup stepper shown before the site is connected. */
    private static function setupStepper(): string
    {
        $steps = [
            ['Register this site', 'One click below - your site gets a secure identity on the dashboard.', true],
            ['Connect Google', 'Sign in with Google on the dashboard and pick your Search Console account.', false],
            ['Automatic setup', 'Verification tag, sitemap and reports are handled for you - no code.', false],
        ];

        $html = '<div class="gscwp-steps-wrap">';
        $html .= '<h2 class="gscwp-sec-title">' . esc_html__('Three steps and your site is on Search Console', 'gscwp') . '</h2>';
        $html .= '<div class="gscwp-steps">';
        foreach ($steps as $i => [$title, $line, $here]) {
            $html .= sprintf(
                '<div class="gscwp-step %1$s">
                    <div class="gscwp-step-num">%2$d</div>
                    <div class="gscwp-step-t">%3$s</div>
                    <div class="gscwp-step-d">%4$s</div>
                    %5$s
                </div>',
                $here ? 'is-here' : '',
                $i + 1,
                esc_html($title),
                esc_html($line),
                $here ? '<span class="gscwp-step-badge">' . esc_html__('You are here', 'gscwp') . '</span>' : ''
            );
        }
        $html .= '</div></div>';
        return $html;
    }

    public static function render(): void
    {
        $state      = GSCWP_Register::getState();
        $registered = ($state['instance_id'] ?? '') !== '';
        $nonce      = wp_create_nonce('gscwp_action');
        /* The dashboard entry, not the wizard: finished users land on
           their dashboard directly, and the dashboard's setup gate
           forwards anyone with steps pending to the exact wizard step
           they stopped at. */
        $connectUrl = $registered
            ? GSCWP_ApiClient::panelUrl('dashboard.php')
                . '?instance_id=' . rawurlencode((string) $state['instance_id'])
            : '';

        $token = $registered ? GSCWP_Verify::token() : '';
        $map   = $registered ? (string) (GSCWP_Sync::meta()['sitemap_url'] ?? '') : '';
        ?>
        <div class="wrap gscwp-wrap">
            <hr class="wp-header-end">
            <style>
                .gscwp-wrap { max-width: 1080px; }

                /* our own transient notices, styled to the page instead of
                   the stock WP notice block */
                .gscwp-alert {
                    display: flex; align-items: center; gap: 10px;
                    margin: 14px 0 0; padding: 12px 16px; border-radius: 10px;
                    font-size: 13.5px; font-weight: 600;
                }
                .gscwp-alert.is-success { background: #ecfdf3; color: #067647; border: 1px solid #abefc6; }
                .gscwp-alert.is-error   { background: #fef3f2; color: #b42318; border: 1px solid #fecdca; }
                .gscwp-alert .dashicons { font-size: 18px; width: 18px; height: 18px; }
                .gscwp-alert button {
                    margin-left: auto; background: none; border: 0; cursor: pointer;
                    font-size: 18px; line-height: 1; color: inherit; opacity: .6; padding: 0 2px;
                }
                .gscwp-alert button:hover { opacity: 1; }

                /* entrance choreography */
                @keyframes gscwpRise { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: none; } }
                .gscwp-hero   { animation: gscwpRise .55s cubic-bezier(.22,1,.36,1) both; }
                .gscwp-grid .gscwp-card:nth-child(1) { animation: gscwpRise .5s cubic-bezier(.22,1,.36,1) .08s both; }
                .gscwp-grid .gscwp-card:nth-child(2) { animation: gscwpRise .5s cubic-bezier(.22,1,.36,1) .16s both; }
                .gscwp-grid .gscwp-card:nth-child(3) { animation: gscwpRise .5s cubic-bezier(.22,1,.36,1) .24s both; }
                .gscwp-panel  { animation: gscwpRise .5s cubic-bezier(.22,1,.36,1) .3s both; }

                .gscwp-hero {
                    position: relative; overflow: hidden;
                    display: flex; align-items: center; justify-content: space-between;
                    gap: 24px; flex-wrap: wrap;
                    margin-top: 16px; padding: 34px 38px; border-radius: 20px;
                    background: linear-gradient(120deg, #4c2bd4 0%, #652ec3 48%, #3b5bfd 100%);
                    color: #fff; box-shadow: 0 18px 50px rgba(76, 43, 212, .32);
                }
                /* slow drifting aurora blobs */
                .gscwp-hero::before, .gscwp-hero::after {
                    content: ""; position: absolute; border-radius: 50%; pointer-events: none;
                    filter: blur(2px);
                }
                .gscwp-hero::before {
                    width: 340px; height: 340px; top: -150px; right: -80px;
                    background: radial-gradient(circle, rgba(255,255,255,.18), transparent 68%);
                    animation: gscwpFloat 9s ease-in-out infinite alternate;
                }
                .gscwp-hero::after {
                    width: 260px; height: 260px; bottom: -130px; left: -70px;
                    background: radial-gradient(circle, rgba(61,220,132,.20), transparent 70%);
                    animation: gscwpFloat 11s ease-in-out infinite alternate-reverse;
                }
                @keyframes gscwpFloat {
                    from { transform: translate(0, 0) scale(1); }
                    to   { transform: translate(-26px, 18px) scale(1.12); }
                }
                .gscwp-hero > * { position: relative; z-index: 1; }
                .gscwp-hero-left { display: flex; align-items: center; gap: 20px; min-width: 0; flex: 1; }
                .gscwp-hero-mark {
                    width: 62px; height: 62px; border-radius: 15px; flex: none;
                    background: rgba(255, 255, 255, .16);
                    display: flex; align-items: center; justify-content: center;
                    border: 1px solid rgba(255,255,255,.25);
                }
                .gscwp-hero h1 {
                    margin: 0; color: #fff; font-size: 25px; line-height: 1.25;
                    display: flex; align-items: center; gap: 10px; flex-wrap: wrap;
                }
                .gscwp-hero p { margin: 5px 0 0; font-size: 13.5px; color: rgba(255,255,255,.85); max-width: 470px; }
                .gscwp-hero-right { display: flex; flex-direction: column; align-items: flex-end; gap: 12px; }

                .gscwp-trust {
                    display: inline-flex; align-items: center; gap: 7px;
                    font-size: 11.5px; font-weight: 700; letter-spacing: .03em;
                    background: rgba(255,255,255,.14); border: 1px solid rgba(255,255,255,.32);
                    padding: 5px 12px; border-radius: 999px; text-transform: uppercase;
                }
                .gscwp-pill {
                    display: inline-flex; align-items: center; gap: 8px;
                    padding: 6px 14px; border-radius: 999px; font-size: 12.5px; font-weight: 600;
                    background: rgba(255,255,255,.15); border: 1px solid rgba(255,255,255,.35);
                }
                .gscwp-pill i { width: 8px; height: 8px; border-radius: 50%; display: inline-block;
                                box-shadow: 0 0 0 3px rgba(255,255,255,.12); }
                .gscwp-cta {
                    display: inline-flex; align-items: center; gap: 9px;
                    background: #fff; color: #3d2a8c !important; text-decoration: none;
                    font-size: 14.5px; font-weight: 700;
                    padding: 12px 26px; border-radius: 11px; border: 0; cursor: pointer;
                    box-shadow: 0 6px 18px rgba(0,0,0,.18);
                    transition: transform .15s ease, box-shadow .15s ease;
                }
                .gscwp-cta:hover { transform: translateY(-2px); box-shadow: 0 12px 28px rgba(0,0,0,.24); }
                .gscwp-cta .arr { transition: transform .15s ease; }
                .gscwp-cta:hover .arr { transform: translateX(4px); }

                .gscwp-hero-illus {
                    background: rgba(255,255,255,.09); border: 1px solid rgba(255,255,255,.22);
                    border-radius: 16px; padding: 10px 12px;
                    transition: transform .25s ease;
                }
                .gscwp-hero-illus:hover { transform: scale(1.02); }
                .gscwp-dash { animation: gscwpDash 1.1s linear infinite; }
                @keyframes gscwpDash { to { stroke-dashoffset: -20; } }
                @media (prefers-reduced-motion: reduce) {
                    .gscwp-dash { animation: none; }
                    .gscwp-hero::before, .gscwp-hero::after { animation: none; }
                    .gscwp-hero, .gscwp-grid .gscwp-card, .gscwp-panel { animation: none; }
                }

                .gscwp-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; margin-top: 18px; }
                .gscwp-card {
                    background: #fff; border: 1px solid #e2e6f0; border-radius: 14px;
                    padding: 18px 20px; box-shadow: 0 1px 2px rgba(16,24,40,.04);
                    transition: transform .18s ease, box-shadow .18s ease;
                }
                .gscwp-card:hover {
                    transform: translateY(-3px);
                    box-shadow: 0 10px 28px rgba(16,24,40,.10);
                }
                .gscwp-card-head { display: flex; align-items: center; gap: 10px; }
                .gscwp-ic {
                    width: 34px; height: 34px; border-radius: 9px; flex: none;
                    display: flex; align-items: center; justify-content: center;
                    transition: transform .18s ease;
                }
                .gscwp-card:hover .gscwp-ic { transform: scale(1.1); }
                .gscwp-ic .dashicons { font-size: 19px; width: 19px; height: 19px; }
                .gscwp-card-label { font-size: 12px; font-weight: 700; letter-spacing: .04em;
                                    text-transform: uppercase; color: #667085; flex: 1; }
                .gscwp-dot { width: 9px; height: 9px; border-radius: 50%; flex: none; }
                .gscwp-card-value { margin-top: 12px; font-size: 14.5px; font-weight: 650; color: #101828; }
                .gscwp-card-hint { margin-top: 4px; font-size: 12.5px; color: #667085; line-height: 1.5; }

                /* "what's inside" strip */
                .gscwp-sec-title {
                    margin: 26px 0 0; font-size: 15px; color: #101828;
                    display: flex; align-items: center; gap: 8px;
                }
                .gscwp-sec-title .dashicons { color: #652ec3; }
                .gscwp-features {
                    display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-top: 12px;
                }
                .gscwp-feat {
                    display: flex; gap: 12px; align-items: flex-start;
                    background: #fff; border: 1px solid #e2e6f0; border-radius: 12px;
                    padding: 14px 15px; transition: transform .18s ease, box-shadow .18s ease;
                }
                .gscwp-feat:hover { transform: translateY(-2px); box-shadow: 0 8px 22px rgba(16,24,40,.08); }
                .gscwp-feat-t { font-size: 13px; font-weight: 700; color: #101828; }
                .gscwp-feat-d { margin-top: 3px; font-size: 12px; color: #667085; line-height: 1.5; }

                /* setup stepper (pre-connection) */
                .gscwp-steps-wrap {
                    margin-top: 18px; background: #fff; border: 1px solid #e2e6f0;
                    border-radius: 14px; padding: 22px 24px;
                }
                .gscwp-steps { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; margin-top: 14px; }
                .gscwp-step {
                    position: relative; border: 1px solid #e2e6f0; border-radius: 12px;
                    padding: 18px; text-align: center; background: #fafbfe;
                }
                .gscwp-step.is-here { border-color: #652ec3; background: #f6f2ff; }
                .gscwp-step-num {
                    width: 34px; height: 34px; border-radius: 50%; margin: 0 auto 10px;
                    display: flex; align-items: center; justify-content: center;
                    background: #e8eaf2; color: #667085; font-weight: 800; font-size: 14px;
                }
                .gscwp-step.is-here .gscwp-step-num { background: #652ec3; color: #fff; }
                .gscwp-step-t { font-size: 13.5px; font-weight: 700; color: #101828; }
                .gscwp-step-d { margin-top: 5px; font-size: 12px; color: #667085; line-height: 1.5; }
                .gscwp-step-badge {
                    display: inline-block; margin-top: 10px; font-size: 11px; font-weight: 700;
                    color: #652ec3; background: rgba(101,46,195,.10);
                    border-radius: 999px; padding: 3px 10px; text-transform: uppercase; letter-spacing: .03em;
                }

                .gscwp-panel {
                    margin-top: 18px; background: #fff; border: 1px solid #e2e6f0;
                    border-radius: 14px; padding: 20px 24px;
                    display: flex; align-items: center; justify-content: space-between;
                    gap: 20px; flex-wrap: wrap;
                }
                .gscwp-panel h2 { margin: 0 0 8px; font-size: 15px; }
                .gscwp-meta { font-size: 12.5px; color: #667085; line-height: 1.9; }
                .gscwp-meta code { background: #f4f5f9; padding: 1px 7px; border-radius: 5px; }
                .gscwp-footer {
                    margin-top: 18px; display: flex; align-items: center; gap: 18px; flex-wrap: wrap;
                    font-size: 12.5px; color: #667085; padding: 0 4px 8px;
                }
                .gscwp-footer a { color: #652ec3; text-decoration: none; font-weight: 600; }
                .gscwp-footer a:hover { text-decoration: underline; }
                .gscwp-footer .sep { color: #d0d5dd; }

                @media (max-width: 900px) {
                    .gscwp-grid, .gscwp-features, .gscwp-steps { grid-template-columns: 1fr; }
                    .gscwp-hero { padding: 24px; }
                    .gscwp-hero-right { align-items: flex-start; }
                    .gscwp-hero-illus { display: none; }
                }
            </style>

            <?php
            $notice = get_transient('gscwp_admin_notice');
            if (is_array($notice)) {
                $isErr = ($notice['type'] === 'error');
                printf(
                    '<div class="gscwp-alert %1$s">
                        <span class="dashicons %2$s"></span>
                        <span>%3$s</span>
                        <button type="button" onclick="this.parentNode.remove()" aria-label="%4$s">&times;</button>
                    </div>',
                    $isErr ? 'is-error' : 'is-success',
                    $isErr ? 'dashicons-warning' : 'dashicons-yes-alt',
                    esc_html((string) $notice['msg']),
                    esc_attr__('Dismiss', 'gscwp')
                );
                delete_transient('gscwp_admin_notice');
            }
            ?>

            <!-- hero -->
            <div class="gscwp-hero">
                <div class="gscwp-hero-left">
                    <div class="gscwp-hero-mark"><?= self::googleMark() ?></div>
                    <div>
                        <h1><?php esc_html_e('GSC Plugin', 'gscwp'); ?>
                            <span style="font-size:12px;font-weight:600;background:rgba(255,255,255,.18);
                                         border:1px solid rgba(255,255,255,.35);border-radius:999px;
                                         padding:3px 10px;vertical-align:middle;">v<?= esc_html(GSCWP_VERSION) ?></span>
                        </h1>
                        <p><?php esc_html_e('Google Search Console for your WordPress site - verification, sitemap and search reports, wired up automatically.', 'gscwp'); ?></p>
                        <p style="margin-top:12px; display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
                            <span class="gscwp-trust"><?= self::googleMark(14) ?>
                                <?php esc_html_e('Google Official Integration', 'gscwp') ?>
                            </span>
                            <span class="gscwp-pill">
                                <i style="background:<?= $registered ? '#3ddc84' : '#ffb020' ?>;"></i>
                                <?= $registered
                                    ? esc_html__('Connected to dashboard', 'gscwp')
                                    : esc_html__('Not connected yet', 'gscwp') ?>
                            </span>
                        </p>
                    </div>
                </div>

                <div class="gscwp-hero-illus">
                    <?= self::connectorIllustration($registered) ?>
                </div>

                <div class="gscwp-hero-right">
                    <?php if ($registered): ?>
                        <a class="gscwp-cta" href="<?= esc_url($connectUrl) ?>" target="_blank" rel="noopener">
                            <?php esc_html_e('Connect to Dashboard', 'gscwp'); ?>
                            <span class="arr" style="font-size:16px;line-height:1;">&rarr;</span>
                        </a>
                    <?php else: ?>
                        <form method="post">
                            <input type="hidden" name="gscwp_action" value="register">
                            <input type="hidden" name="gscwp_nonce" value="<?= esc_attr($nonce) ?>">
                            <button type="submit" class="gscwp-cta">
                                <?php esc_html_e('Register this site', 'gscwp'); ?>
                                <span class="arr" style="font-size:16px;line-height:1;">&rarr;</span>
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($registered): ?>
                <!-- live status cards -->
                <h2 class="gscwp-sec-title">
                    <span class="dashicons dashicons-analytics"></span>
                    <?php esc_html_e('Right now, on this site', 'gscwp'); ?>
                </h2>
                <div class="gscwp-grid">
                    <?php
                    if (GSCWP_Verify::seoPluginActive()) {
                        echo self::statusCard(
                            '<span class="dashicons dashicons-yes-alt" style="color:#652ec3;"></span>',
                            'rgba(101,46,195,.10)',
                            __('Verification', 'gscwp'),
                            esc_html__('SEO plugin active', 'gscwp'),
                            __('Your SEO plugin owns the verification tag - this plugin stays out of the way.', 'gscwp'),
                            true
                        );
                    } elseif ($token !== '') {
                        echo self::statusCard(
                            '<span class="dashicons dashicons-yes-alt" style="color:#00a32a;"></span>',
                            'rgba(0,163,42,.10)',
                            __('Verification', 'gscwp'),
                            esc_html__('Verification tag is live', 'gscwp'),
                            __('The Google meta tag is injected into your site head automatically.', 'gscwp'),
                            true
                        );
                    } else {
                        echo self::statusCard(
                            '<span class="dashicons dashicons-clock" style="color:#dba617;"></span>',
                            'rgba(219,166,23,.12)',
                            __('Verification', 'gscwp'),
                            esc_html__('Waiting for the token', 'gscwp'),
                            __('Complete the dashboard wizard and the tag lands on your site automatically.', 'gscwp'),
                            false
                        );
                    }

                    if ($map !== '') {
                        echo self::statusCard(
                            '<span class="dashicons dashicons-website" style="color:#3b5bfd;"></span>',
                            'rgba(59,91,253,.10)',
                            __('Sitemap', 'gscwp'),
                            '<code style="font-size:12.5px;word-break:break-all;">' . esc_html($map) . '</code>',
                            __('Detected on your site and reported to the dashboard.', 'gscwp'),
                            true
                        );
                    } else {
                        echo self::statusCard(
                            '<span class="dashicons dashicons-website" style="color:#3b5bfd;"></span>',
                            'rgba(59,91,253,.10)',
                            __('Sitemap', 'gscwp'),
                            esc_html__('Not detected yet', 'gscwp'),
                            __('Synced from your site on the next dashboard visit.', 'gscwp'),
                            false
                        );
                    }

                    $pings = (int) GSCWP_Ping::todayCount();
                    echo self::statusCard(
                        '<span class="dashicons dashicons-cloud" style="color:#0ea5e9;"></span>',
                        'rgba(14,165,233,.10)',
                        __('Content pings today', 'gscwp'),
                        esc_html((string) $pings) . ' <span style="color:#667085;font-weight:500;">/ ' . esc_html((string) GSCWP_Ping::FREE_DAILY_LIMIT) . '</span>',
                        __('Times Google was notified about updated content today.', 'gscwp'),
                        true
                    );
                    ?>
                </div>
            <?php else: ?>
                <?= self::setupStepper() ?>
            <?php endif; ?>

            <!-- value strip: always visible -->
            <h2 class="gscwp-sec-title">
                <span class="dashicons dashicons-star-filled"></span>
                <?php esc_html_e("Everything the plugin takes care of", 'gscwp'); ?>
            </h2>
            <div class="gscwp-features">
                <?php
                echo self::featureTile(
                    '<span class="dashicons dashicons-shield" ></span>',
                    'rgba(0,163,42,.10)', '#00a32a',
                    __('Auto Verification', 'gscwp'),
                    __('The Google meta tag injects itself into your site - no theme edits, no code.', 'gscwp')
                );
                echo self::featureTile(
                    '<span class="dashicons dashicons-website"></span>',
                    'rgba(59,91,253,.10)', '#3b5bfd',
                    __('Sitemap Automation', 'gscwp'),
                    __('Your sitemap is detected, reported and monitored on the dashboard.', 'gscwp')
                );
                echo self::featureTile(
                    '<span class="dashicons dashicons-cloud"></span>',
                    'rgba(14,165,233,.10)', '#0ea5e9',
                    __('Content Pings', 'gscwp'),
                    __('Every time you publish or update, Google is notified to crawl sooner.', 'gscwp')
                );
                echo self::featureTile(
                    '<span class="dashicons dashicons-chart-line"></span>',
                    'rgba(101,46,195,.10)', '#652ec3',
                    __('Search Reports', 'gscwp'),
                    __('Clicks, queries, pages and positions - live on your GSC dashboard.', 'gscwp')
                );
                ?>
            </div>

            <!-- connection panel -->
            <div class="gscwp-panel">
                <div>
                    <h2><?php esc_html_e('Connection details', 'gscwp'); ?></h2>
                    <div class="gscwp-meta">
                        <?php esc_html_e('Site URL', 'gscwp'); ?>: <code><?= esc_html(home_url()) ?></code>
                        <?php if ($registered): ?>
                            <br><?php esc_html_e('Instance ID', 'gscwp'); ?>: <code><?= esc_html((string) $state['instance_id']) ?></code>
                            <?php if (!empty($state['registered_at'])): ?>
                                <br><?php esc_html_e('Registered on', 'gscwp'); ?> <?= esc_html((string) $state['registered_at']) ?>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
                <form method="post">
                    <input type="hidden" name="gscwp_action" value="register">
                    <input type="hidden" name="gscwp_nonce" value="<?= esc_attr($nonce) ?>">
                    <button type="submit" class="button" style="border-radius:8px;">
                        <?= $registered
                            ? esc_html__('Re-register this site', 'gscwp')
                            : esc_html__('Register this site', 'gscwp') ?>
                    </button>
                </form>
            </div>

            <!-- footer links -->
            <div class="gscwp-footer">
                <span>
                    <a href="<?= esc_url(GSCWP_COMPANY_URL) ?>" target="_blank" rel="noopener">makkpressapps.com</a>
                    <span class="sep">&middot;</span>
                    <a href="<?= esc_url(GSCWP_MCP_URL) ?>" target="_blank" rel="noopener">orgalytics.ai (MCP Connector)</a>
                </span>
                <span style="margin-left:auto;">
                    <?= esc_html__('By', 'gscwp') ?> <strong>MakkPress Apps</strong>
                </span>
            </div>
        </div>
        <?php
    }
}
