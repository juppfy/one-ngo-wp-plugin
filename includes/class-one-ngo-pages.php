<?php

if (!defined('ABSPATH')) {
    exit;
}

final class One_Ngo_Pages
{
    /**
     * @return array<string, int>
     */
    public static function ids(): array
    {
        $saved = get_option(One_Ngo_Api::OPTION_PAGE_IDS, []);
        if (!is_array($saved)) {
            return [];
        }
        $out = [];
        foreach ($saved as $kind => $id) {
            $out[sanitize_key((string) $kind)] = (int) $id;
        }
        return $out;
    }

    public static function page_id(string $kind): int
    {
        $ids = self::ids();
        return (int) ($ids[$kind] ?? 0);
    }

    public static function create_index(string $kind): int|WP_Error
    {
        $kind = sanitize_key($kind);
        if (!in_array($kind, One_Ngo_Routes::KINDS, true)) {
            return new WP_Error('one_ngo_kind', __('Unknown page type.', 'one-ngo-fundraising'));
        }

        $slug = One_Ngo_Routes::slug($kind);
        $existing_id = self::page_id($kind);
        if ($existing_id && get_post($existing_id)) {
            return $existing_id;
        }

        $shortcodes = [
            'donate' => '[1ngo donate]',
            'campaigns' => '[1ngo campaigns limit="12"]',
            'events' => '[1ngo events limit="12"]',
            'stories' => '[1ngo stories limit="12"]',
        ];
        $titles = [
            'donate' => __('Donate', 'one-ngo-fundraising'),
            'campaigns' => __('Campaigns', 'one-ngo-fundraising'),
            'events' => __('Events', 'one-ngo-fundraising'),
            'stories' => __('Stories', 'one-ngo-fundraising'),
        ];

        $page_id = wp_insert_post([
            'post_title' => $titles[$kind],
            'post_name' => $slug,
            'post_status' => 'publish',
            'post_type' => 'page',
            'post_content' => $shortcodes[$kind],
        ], true);

        if (is_wp_error($page_id)) {
            return $page_id;
        }

        $ids = self::ids();
        $ids[$kind] = (int) $page_id;
        update_option(One_Ngo_Api::OPTION_PAGE_IDS, $ids, false);
        One_Ngo_Heartbeat::sync();
        return (int) $page_id;
    }
}
