<?php

if (!defined('ABSPATH')) {
    exit;
}

final class One_Ngo_Api
{
    public const OPTION_ORG_ID = 'one_ngo_organization_id';
    public const OPTION_TOKEN = 'one_ngo_api_token';
    public const OPTION_API_BASE = 'one_ngo_api_base';
    public const OPTION_ROUTES = 'one_ngo_routes';
    public const OPTION_PAGE_IDS = 'one_ngo_page_ids';
    public const OPTION_LIST_TRANSIENTS = 'one_ngo_list_transient_keys';

    public static function organization_id(): string
    {
        return trim((string) get_option(self::OPTION_ORG_ID, ''));
    }

    public static function api_token(): string
    {
        return trim((string) get_option(self::OPTION_TOKEN, ''));
    }

    public static function api_base(): string
    {
        $saved = trim((string) get_option(self::OPTION_API_BASE, ''));
        if ($saved !== '') {
            return untrailingslashit($saved);
        }
        $default = trim((string) ONE_NGO_DEFAULT_API_BASE);
        if ($default !== '' && !str_contains($default, '__ONE_NGO_API_BASE__')) {
            return untrailingslashit($default);
        }
        return '';
    }

    public static function is_configured(): bool
    {
        return self::organization_id() !== '' && self::api_token() !== '' && self::api_base() !== '';
    }

    /**
     * @param array<string, string> $headers
     * @return array<string, mixed>|WP_Error
     */
    private static function request(string $method, string $path, array $query = [], ?array $body = null)
    {
        if (!self::is_configured()) {
            return new WP_Error(
                'one_ngo_not_configured',
                __('Add your Organization ID, read-only token, and API URL in Settings → 1 NGO.', 'one-ngo-fundraising')
            );
        }

        $url = self::api_base() . '/' . ltrim($path, '/');
        if ($query) {
            $url = add_query_arg($query, $url);
        }

        $args = [
            'timeout' => 15,
            'method' => $method,
            'headers' => [
                'Authorization' => 'Bearer ' . self::api_token(),
                'X-1NGO-Organization-Id' => self::organization_id(),
                'X-1NGO-Site-Url' => home_url('/'),
                'Accept' => 'application/json',
            ],
        ];
        if ($body !== null) {
            $args['headers']['Content-Type'] = 'application/json';
            $args['body'] = wp_json_encode($body);
        }

        $response = wp_remote_request($url, $args);
        if (is_wp_error($response)) {
            return $response;
        }

        $code = (int) wp_remote_retrieve_response_code($response);
        $decoded = json_decode((string) wp_remote_retrieve_body($response), true);
        if ($code < 200 || $code >= 300 || !is_array($decoded)) {
            $message = is_array($decoded) && isset($decoded['message'])
                ? (string) $decoded['message']
                : __('Could not reach 1 NGO. Check the Organization ID and token.', 'one-ngo-fundraising');
            return new WP_Error('one_ngo_http', $message, ['status' => $code]);
        }

        return $decoded;
    }

    /**
     * @return array<string, mixed>|WP_Error
     */
    public static function get(string $path, array $query = [])
    {
        return self::request('GET', $path, $query);
    }

    /**
     * @param array<string, mixed> $body
     * @return array<string, mixed>|WP_Error
     */
    public static function post(string $path, array $body)
    {
        return self::request('POST', $path, [], $body);
    }

    /**
     * @return array<string, mixed>|WP_Error
     */
    public static function me()
    {
        $cached = get_transient('one_ngo_me');
        if (is_array($cached)) {
            return $cached;
        }
        $payload = self::get('/org-api/v1/me');
        if (is_wp_error($payload)) {
            return $payload;
        }
        set_transient('one_ngo_me', $payload, 10 * MINUTE_IN_SECONDS);
        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    public static function brand(): array
    {
        $cached = get_transient('one_ngo_brand');
        if (is_array($cached)) {
            return $cached;
        }
        $payload = self::get('/org-api/v1/brand');
        if (is_wp_error($payload) || !isset($payload['brand']) || !is_array($payload['brand'])) {
            return [];
        }
        set_transient('one_ngo_brand', $payload['brand'], 10 * MINUTE_IN_SECONDS);
        return $payload['brand'];
    }

    public static function org_slug(): string
    {
        $me = self::me();
        if (is_wp_error($me)) {
            return '';
        }
        $slug = $me['organization']['slug'] ?? '';
        return is_string($slug) ? $slug : '';
    }

    /**
     * @return array<int, array<string, mixed>>|WP_Error
     */
    public static function list_published(string $path, string $key)
    {
        $transient = 'one_ngo_list_' . md5($path . $key);
        $cached = get_transient($transient);
        if (is_array($cached)) {
            return $cached;
        }
        $payload = self::get($path, ['status' => 'published']);
        if (is_wp_error($payload)) {
            return $payload;
        }
        $items = [];
        foreach (($payload[$key] ?? []) as $row) {
            if (is_array($row)) {
                $items[] = $row;
            }
        }
        set_transient($transient, $items, 5 * MINUTE_IN_SECONDS);
        self::remember_list_transient($transient);
        return $items;
    }

    /**
     * @return array<string, mixed>|WP_Error
     */
    public static function get_published_item(string $path, string $key, string $slug)
    {
        $payload = self::get($path . '/' . rawurlencode($slug));
        if (is_wp_error($payload)) {
            return $payload;
        }
        $item = $payload[$key] ?? null;
        if (!is_array($item) || (($item['status'] ?? '') !== 'published')) {
            return new WP_Error('one_ngo_not_found', __('That item is not published.', 'one-ngo-fundraising'), ['status' => 404]);
        }
        return $item;
    }

    public static function clear_cache(): void
    {
        delete_transient('one_ngo_me');
        delete_transient('one_ngo_brand');
        delete_transient('one_ngo_campaigns');
        delete_transient('one_ngo_events');
        delete_transient('one_ngo_stories');
        $keys = get_option(self::OPTION_LIST_TRANSIENTS, []);
        if (is_array($keys)) {
            foreach ($keys as $key) {
                if (is_string($key) && str_starts_with($key, 'one_ngo_list_')) {
                    delete_transient($key);
                }
            }
        }
        delete_option(self::OPTION_LIST_TRANSIENTS);
    }

    private static function remember_list_transient(string $key): void
    {
        $keys = get_option(self::OPTION_LIST_TRANSIENTS, []);
        if (!is_array($keys)) {
            $keys = [];
        }
        if (in_array($key, $keys, true)) {
            return;
        }
        $keys[] = $key;
        update_option(self::OPTION_LIST_TRANSIENTS, $keys, false);
    }
}
