<?php
/**
 * One place that talks to the hosted panel.
 *
 * Everything goes through here so timeouts, SSL verification and error
 * handling stay consistent. Uses the WP HTTP API - no curl direct calls.
 */

if (!defined('ABSPATH')) {
    exit;
}

class GSCWP_ApiClient
{
    /** Seconds before we give up on the panel. Kept short: the call
     *  happens on admin page loads and must never hang the dashboard. */
    const TIMEOUT = 10;

    /**
     * POST JSON to a panel endpoint.
     *
     * @param string $path Path under the panel URL, e.g. 'api/register-site.php'.
     * @param array  $body Payload encoded as JSON.
     * @return array{ok:bool,status:int,body:array}
     */
    public static function post(string $path, array $body): array
    {
        $url  = untrailingslashit(GSCWP_PANEL_URL_FINAL) . '/' . ltrim($path, '/');

        $response = wp_remote_post($url, [
            'timeout'     => self::TIMEOUT,
            'redirection' => 0,
            'sslverify'   => true,
            'headers'     => [
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json',
                'X-GSCWP-Plugin-Version' => GSCWP_VERSION,
            ],
            'body'        => wp_json_encode($body),
        ]);

        if (is_wp_error($response)) {
            return ['ok' => false, 'status' => 0, 'body' => [], 'error' => $response->get_error_message()];
        }

        $status = (int) wp_remote_retrieve_response_code($response);
        $raw    = (string) wp_remote_retrieve_body($response);
        $parsed = json_decode($raw, true);

        if (!is_array($parsed)) {
            return ['ok' => false, 'status' => $status, 'body' => [], 'error' => 'invalid JSON from panel'];
        }

        return ['ok' => ($status >= 200 && $status < 300), 'status' => $status, 'body' => $parsed];
    }

    /**
     * The absolute URL of a panel page, for the Connect button.
     */
    public static function panelUrl(string $path = ''): string
    {
        return untrailingslashit(GSCWP_PANEL_URL_FINAL) . '/' . ltrim($path, '/');
    }
}
