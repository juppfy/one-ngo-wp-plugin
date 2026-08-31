<?php

if (!defined('ABSPATH')) {
    exit;
}

final class One_Ngo_Shortcodes
{
    public static function init(): void
    {
        add_shortcode('1ngo', [self::class, 'render']);
        add_shortcode('one_ngo', [self::class, 'render']);
    }

    /**
     * @param array<string, string>|string $atts
     */
    public static function render($atts): string
    {
        $atts = is_array($atts) ? $atts : [];
        $positional = isset($atts[0]) && is_string($atts[0]) ? sanitize_key($atts[0]) : '';
        $atts = shortcode_atts([
            'type' => $positional !== '' ? $positional : 'donate',
            'slug' => '',
            'limit' => '12',
            'title' => '',
        ], $atts, '1ngo');

        $kind = sanitize_key((string) $atts['type']);
        $slug = sanitize_title((string) $atts['slug']);
        $limit = max(1, min(12, (int) $atts['limit']));

        if (in_array($kind, ['campaign', 'event', 'story'], true) && $slug === '') {
            return is_user_logged_in()
                ? '<p>' . esc_html__('This 1 NGO shortcode needs a slug.', 'one-ngo-fundraising') . '</p>'
                : '';
        }

        return One_Ngo_Public::render_kind($kind, $slug, $limit);
    }
}
