<?php
/**
 * Injects the GSC verification meta tag into wp_head.
 *
 * Skipped on purpose when a known SEO plugin is active: Yoast,
 * RankMath and AIOSEO print their own google-site-verification meta,
 * and a second one is worse than none - it means two sources of truth
 * for the same setting.
 */

if (!defined('ABSPATH')) {
    exit;
}

class GSCWP_Verify
{
    /**
     * Is another SEO plugin handling verification?
     */
    public static function seoPluginActive(): bool
    {
        return defined('WPSEO_VERSION')          // Yoast
            || defined('RANK_MATH_VERSION')      // RankMath
            || defined('AIOSEO_VERSION');        // All in One SEO
    }

    /**
     * The verification token, from the last panel sync.
     */
    public static function token(): string
    {
        $meta = GSCWP_Sync::meta();
        return preg_match('/^[A-Za-z0-9_-]+$/', (string) ($meta['verify_token'] ?? ''))
            ? (string) $meta['verify_token']
            : '';
    }

    public static function printMeta(): void
    {
        if (self::seoPluginActive()) {
            return; // the other plugin owns the head
        }

        $token = self::token();
        if ($token === '') {
            return;
        }

        echo '<meta name="google-site-verification" content="' . esc_attr($token) . '">' . "\n";
    }
}
