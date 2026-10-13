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

    /** Google "G" mark for the hero, inline so nothing external loads. */
    private static function googleMark(): string
    {
        return '<svg viewBox="0 0 48 48" width="30" height="30" aria-hidden="true">'
            . '<path fill="#FFC107" d="M43.6 20.5H42V20H24v8h11.3C33.7 32.7 29.3 36 24 36c-6.6 0-12-5.4-12-12s5.4-12 12-12c3.1 0 5.9 1.2 8 3l5.7-5.7C34.3 6.1 29.4 4 24 4 13 4 4 13 4 24s9 20 20 20 20-9 20-20c0-1.2-.1-2.4-.4-3.5z"/>'
            . '<path fill="#FF3D00" d="M6.3 14.7l6.6 4.8C14.7 15.1 19 12 24 12c3.1 0 5.9 1.2 8 3l5.7-5.7C34.3 6.1 29.4 4 24 4 16.3 4 9.7 8.3 6.3 14.7z"/>'
            . '<path fill="#4CAF50" d="M24 44c5.2 0 9.9-2 13.4-5.2l-6.2-5.2C29.2 35.1 26.7 36 24 36c-5.3 0-9.7-3.3-11.3-8l-6.5 5C9.5 39.6 16.2 44 24 44z"/>'
            . '<path fill="#1976D2" d="M43.6 20.5H42V20H24v8h11.3c-.8 2.3-2.3 4.3-4.1 5.7l6.2 5.2C36.9 39.2 44 34 44 24c0-1.2-.1-2.4-.4-3.5z"/>'
            . '</svg>';
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

    public static function render(): void
    {
        $state      = GSCWP_Register::getState();
        $registered = ($state['instance_id'] ?? '') !== '';
        $nonce      = wp_create_nonce('gscwp_action');
        $connectUrl = $registered
            ? GSCWP_ApiClient::panelUrl('setup-wizard.php')
                . '?instance_id=' . rawurlencode((string) $state['instance_id'])
                . '&site=' . rawurlencode((string) home_url())
            : '';

        $token = $registered ? GSCWP_Verify::token() : '';
        $map   = $registered ? (string) (GSCWP_Sync::meta()['sitemap_url'] ?? '') : '';
        ?>
        <div class="wrap gscwp-wrap">
            <style>
                .gscwp-wrap { max-width: 1080px; }
                .gscwp-hero {
                    display: flex; align-items: center; justify-content: space-between;
                    gap: 24px; flex-wrap: wrap;
                    margin-top: 16px; padding: 30px 34px; border-radius: 16px;
                    background: linear-gradient(120deg, #4c2bd4 0%, #652ec3 48%, #3b5bfd 100%);
                    color: #fff; box-shadow: 0 14px 40px rgba(76, 43, 212, .28);
                }
                .gscwp-hero-left { display: flex; align-items: center; gap: 18px; min-width: 0; }
                .gscwp-hero-mark {
                    width: 58px; height: 58px; border-radius: 14px; flex: none;
                    background: rgba(255, 255, 255, .16);
                    display: flex; align-items: center; justify-content: center;
                }
                .gscwp-hero h1 {
                    margin: 0; color: #fff; font-size: 24px; line-height: 1.25;
                    display: flex; align-items: center; gap: 10px;
                }
                .gscwp-hero p { margin: 4px 0 0; font-size: 13.5px; color: rgba(255,255,255,.85); max-width: 520px; }
                .gscwp-hero-right { display: flex; flex-direction: column; align-items: flex-end; gap: 12px; }
                .gscwp-pill {
                    display: inline-flex; align-items: center; gap: 8px;
                    padding: 6px 14px; border-radius: 999px; font-size: 12.5px; font-weight: 600;
                    background: rgba(255,255,255,.15); border: 1px solid rgba(255,255,255,.35);
                }
                .gscwp-pill i { width: 8px; height: 8px; border-radius: 50%; display: inline-block; }
                .gscwp-cta {
                    display: inline-flex; align-items: center; gap: 9px;
                    background: #fff; color: #3d2a8c !important; text-decoration: none;
                    font-size: 14.5px; font-weight: 700;
                    padding: 12px 26px; border-radius: 10px; border: 0; cursor: pointer;
                    box-shadow: 0 6px 18px rgba(0,0,0,.18);
                    transition: transform .15s ease, box-shadow .15s ease;
                }
                .gscwp-cta:hover { transform: translateY(-1px); box-shadow: 0 10px 24px rgba(0,0,0,.22); }
                .gscwp-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; margin-top: 18px; }
                .gscwp-card {
                    background: #fff; border: 1px solid #e2e6f0; border-radius: 12px;
                    padding: 18px 20px; box-shadow: 0 1px 2px rgba(16,24,40,.04);
                }
                .gscwp-card-head { display: flex; align-items: center; gap: 10px; }
                .gscwp-ic {
                    width: 34px; height: 34px; border-radius: 9px; flex: none;
                    display: flex; align-items: center; justify-content: center;
                }
                .gscwp-ic .dashicons { font-size: 19px; width: 19px; height: 19px; }
                .gscwp-card-label { font-size: 12px; font-weight: 700; letter-spacing: .04em;
                                    text-transform: uppercase; color: #667085; flex: 1; }
                .gscwp-dot { width: 9px; height: 9px; border-radius: 50%; flex: none; }
                .gscwp-card-value { margin-top: 12px; font-size: 14.5px; font-weight: 650; color: #101828; }
                .gscwp-card-hint { margin-top: 4px; font-size: 12.5px; color: #667085; line-height: 1.5; }
                .gscwp-panel {
                    margin-top: 18px; background: #fff; border: 1px solid #e2e6f0;
                    border-radius: 12px; padding: 20px 24px;
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
                    .gscwp-grid { grid-template-columns: 1fr; }
                    .gscwp-hero { padding: 24px; }
                    .gscwp-hero-right { align-items: flex-start; }
                }
            </style>

            <?php
            $notice = get_transient('gscwp_admin_notice');
            if (is_array($notice)) {
                printf(
                    '<div class="notice notice-%1$s is-dismissible"><p>%2$s</p></div>',
                    $notice['type'] === 'error' ? 'error' : 'success',
                    esc_html((string) $notice['msg'])
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
                    </div>
                </div>
                <div class="gscwp-hero-right">
                    <span class="gscwp-pill">
                        <i style="background:<?= $registered ? '#3ddc84' : '#ffb020' ?>;"></i>
                        <?= $registered
                            ? esc_html__('Connected to dashboard', 'gscwp')
                            : esc_html__('Not connected yet', 'gscwp') ?>
                    </span>
                    <?php if ($registered): ?>
                        <a class="gscwp-cta" href="<?= esc_url($connectUrl) ?>" target="_blank" rel="noopener">
                            <?php esc_html_e('Connect to Dashboard', 'gscwp'); ?>
                            <span style="font-size:16px;line-height:1;">&rarr;</span>
                        </a>
                    <?php else: ?>
                        <form method="post">
                            <input type="hidden" name="gscwp_action" value="register">
                            <input type="hidden" name="gscwp_nonce" value="<?= esc_attr($nonce) ?>">
                            <button type="submit" class="gscwp-cta">
                                <?php esc_html_e('Register this site', 'gscwp'); ?>
                                <span style="font-size:16px;line-height:1;">&rarr;</span>
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($registered): ?>
                <!-- status cards -->
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
            <?php endif; ?>

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
