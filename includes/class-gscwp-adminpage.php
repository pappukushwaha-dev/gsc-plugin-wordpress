<?php
/**
 * The settings page inside WP admin (Settings > GSC Plugin).
 *
 * Shows the registration state, the Connect button into the hosted
 * dashboard, and a manual re-register action. Every action is nonce
 * checked and capability checked; every value printed is escaped.
 */

if (!defined('ABSPATH')) {
    exit;
}

class GSCWP_AdminPage
{
    const CAP = 'manage_options';

    public static function init(): void
    {
        add_action('admin_menu', [self::class, 'addMenu']);
        add_action('admin_init', [self::class, 'handleActions']);
    }

    public static function addMenu(): void
    {
        add_options_page(
            __('GSC Plugin', 'gscwp'),
            __('GSC Plugin', 'gscwp'),
            self::CAP,
            'gscwp',
            [self::class, 'render']
        );
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
            wp_safe_redirect(admin_url('options-general.php?page=gscwp'));
            exit;
        }
    }

    public static function render(): void
    {
        $state = GSCWP_Register::getState();
        $registered = ($state['instance_id'] ?? '') !== '';
        $nonce = wp_create_nonce('gscwp_action');
        $connectUrl = $registered
            ? GSCWP_ApiClient::panelUrl('setup-wizard.php')
                . '?instance_id=' . rawurlencode((string) $state['instance_id'])
                . '&site=' . rawurlencode((string) home_url())
            : '';
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('GSC Plugin', 'gscwp'); ?></h1>

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

            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><?php esc_html_e('Site URL', 'gscwp'); ?></th>
                    <td><code><?php echo esc_html(home_url()); ?></code></td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Status', 'gscwp'); ?></th>
                    <td>
                        <?php if ($registered): ?>
                            <span style="color:#00a32a;font-weight:600;">
                                <?php esc_html_e('Connected to the GSC dashboard', 'gscwp'); ?>
                            </span><br>
                            <code><?php echo esc_html((string) $state['instance_id']); ?></code>
                            <p class="description">
                                <?php esc_html_e('Registered on', 'gscwp'); ?>
                                <?php echo esc_html((string) ($state['registered_at'] ?? '')); ?>
                            </p>
                        <?php else: ?>
                            <span style="color:#d63638;font-weight:600;">
                                <?php esc_html_e('Not connected yet', 'gscwp'); ?>
                            </span>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>

            <form method="post" style="margin-top:1rem;">
                <input type="hidden" name="gscwp_action" value="register">
                <input type="hidden" name="gscwp_nonce" value="<?php echo esc_attr($nonce); ?>">
                <button type="submit" class="button button-secondary">
                    <?php echo $registered
                        ? esc_html__('Re-register this site', 'gscwp')
                        : esc_html__('Register this site', 'gscwp'); ?>
                </button>
            </form>

            <?php if ($registered): ?>
                <p style="margin-top:1.5rem;">
                    <a class="button button-primary button-hero"
                       href="<?php echo esc_url($connectUrl); ?>"
                       target="_blank" rel="noopener">
                        <?php esc_html_e('Connect to Dashboard', 'gscwp'); ?>
                    </a>
                </p>
                <p class="description">
                    <?php esc_html_e('Opens the GSC setup wizard on the dashboard - verification, sitemap and reports live there.', 'gscwp'); ?>
                </p>
            <?php endif; ?>
        </div>
        <?php
    }
}
