<?php
/**
 * Content-update pings: when the site publishes or updates a public
 * post, the panel is told. The panel records it and its own crons
 * keep the GSC side fresh.
 *
 * The queue lives in an option (no custom tables), processed on the
 * next admin load or WP-Cron tick. The free plan is capped per day;
 * the paid plan lifts the cap (plan awareness lands with PHASE-5, the
 * cap is a constant until then). A hard server-side cap exists too.
 */

if (!defined('ABSPATH')) {
    exit;
}

class GSCWP_Ping
{
    const FREE_DAILY_LIMIT = 10;
    const QUEUE_MAX        = 100;

    public static function init(): void
    {
        // publish or update of a public post type
        add_action('save_post', [self::class, 'enqueueForPost'], 10, 2);
        add_action('gscwp_process_ping_queue', [self::class, 'processQueue']);
        add_action('admin_init', [self::class, 'processQueue']);
    }

    /**
     * save_post also fires for menus, revisions and autosaves - only
     * public, published, non-revision posts are worth a ping.
     */
    public static function enqueueForPost(int $postId, $post): void
    {
        if (wp_is_post_revision($postId) || wp_is_post_autosave($postId)) {
            return;
        }
        $post = get_post($postId);
        if (!$post || $post->post_status !== 'publish' || !is_post_publicly_viewable($postId)) {
            return;
        }

        $queue = get_option('gscwp_ping_queue', []);
        if (!is_array($queue)) {
            $queue = [];
        }

        $queue[$postId] = get_permalink($postId);

        // keep the queue bounded: newest wins, oldest dropped
        if (count($queue) > self::QUEUE_MAX) {
            $queue = array_slice($queue, -self::QUEUE_MAX, null, true);
        }

        update_option('gscwp_ping_queue', $queue, false);

        if (!wp_next_scheduled('gscwp_process_ping_queue')) {
            wp_schedule_single_event(time() + 30, 'gscwp_process_ping_queue');
        }
    }

    /**
     * Sends the queued pings within today's limit.
     */
    public static function processQueue(): void
    {
        $queue = get_option('gscwp_ping_queue', []);
        if (!is_array($queue) || empty($queue)) {
            return;
        }

        $state = GSCWP_Register::getState();
        if (($state['instance_id'] ?? '') === '' || ($state['secret'] ?? '') === '') {
            return;
        }

        $remaining = self::remainingToday();
        if ($remaining <= 0) {
            return;
        }

        $sent = 0;
        foreach ($queue as $postId => $url) {
            if ($sent >= $remaining) {
                break; // the rest wait for tomorrow's window
            }

            $response = GSCWP_ApiClient::post('api/content-ping.php', [
                'instance_id' => $state['instance_id'],
                'secret'      => $state['secret'],
                'url'         => $url,
            ]);

            if (!empty($response['ok']) && !empty($response['body']['success'])) {
                unset($queue[$postId]);
                $sent++;
                self::countToday();
            } elseif (($response['status'] ?? 0) === 429) {
                break; // server cap reached - stop for today
            }
        }

        update_option('gscwp_ping_queue', $queue, false);
    }

    private static function remainingToday(): int
    {
        $today = gmdate('Y-m-d');
        $count = get_option('gscwp_ping_count', ['date' => $today, 'n' => 0]);

        if (!is_array($count) || $count['date'] !== $today) {
            update_option('gscwp_ping_count', ['date' => $today, 'n' => 0], false);
            return self::FREE_DAILY_LIMIT;
        }

        return max(0, self::FREE_DAILY_LIMIT - (int) $count['n']);
    }

    private static function countToday(): void
    {
        $today = gmdate('Y-m-d');
        $count = get_option('gscwp_ping_count', ['date' => $today, 'n' => 0]);

        if (!is_array($count) || $count['date'] !== $today) {
            update_option('gscwp_ping_count', ['date' => $today, 'n' => 1], false);
            return;
        }

        $count['n'] = (int) $count['n'] + 1;
        update_option('gscwp_ping_count', $count, false);
    }

    /** For the settings page. */
    public static function todayCount(): int
    {
        $count = get_option('gscwp_ping_count', ['date' => gmdate('Y-m-d'), 'n' => 0]);
        return is_array($count) ? (int) $count['n'] : 0;
    }
}
