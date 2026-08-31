<?php

if (!defined('ABSPATH')) {
    exit;
}

final class One_Ngo_Rest
{
    public static function init(): void
    {
        add_action('rest_api_init', [self::class, 'register']);
    }

    public static function register(): void
    {
        register_rest_route('one-ngo/v1', '/status', [
            'methods' => 'GET',
            'permission_callback' => [self::class, 'can_edit'],
            'callback' => [self::class, 'status'],
        ]);
        register_rest_route('one-ngo/v1', '/campaigns', [
            'methods' => 'GET',
            'permission_callback' => [self::class, 'can_edit'],
            'callback' => [self::class, 'campaigns'],
        ]);
        register_rest_route('one-ngo/v1', '/events', [
            'methods' => 'GET',
            'permission_callback' => [self::class, 'can_edit'],
            'callback' => [self::class, 'events'],
        ]);
        register_rest_route('one-ngo/v1', '/stories', [
            'methods' => 'GET',
            'permission_callback' => [self::class, 'can_edit'],
            'callback' => [self::class, 'stories'],
        ]);
    }

    public static function can_edit(): bool
    {
        return current_user_can('edit_posts');
    }

    public static function status(): WP_REST_Response|WP_Error
    {
        $me = One_Ngo_Api::me();
        if (is_wp_error($me)) {
            return $me;
        }
        return new WP_REST_Response($me, 200);
    }

    public static function campaigns(): WP_REST_Response|WP_Error
    {
        return self::list_items('one_ngo_campaigns', '/org-api/v1/campaigns', 'campaigns');
    }

    public static function events(): WP_REST_Response|WP_Error
    {
        return self::list_items('one_ngo_events', '/org-api/v1/events', 'events');
    }

    public static function stories(): WP_REST_Response|WP_Error
    {
        return self::list_items('one_ngo_stories', '/org-api/v1/stories', 'stories');
    }

    private static function list_items(string $transient, string $path, string $key): WP_REST_Response|WP_Error
    {
        $cached = get_transient($transient);
        if (is_array($cached)) {
            return new WP_REST_Response($cached, 200);
        }
        $payload = One_Ngo_Api::get($path, ['status' => 'published']);
        if (is_wp_error($payload)) {
            return $payload;
        }
        $items = [];
        foreach (($payload[$key] ?? []) as $row) {
            if (!is_array($row)) {
                continue;
            }
            $items[] = [
                'id' => (string) ($row['id'] ?? ''),
                'slug' => (string) ($row['slug'] ?? ''),
                'title' => (string) ($row['title'] ?? ''),
            ];
        }
        $body = ['items' => $items];
        set_transient($transient, $body, 5 * MINUTE_IN_SECONDS);
        return new WP_REST_Response($body, 200);
    }
}
