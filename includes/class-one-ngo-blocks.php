<?php

if (!defined('ABSPATH')) {
    exit;
}

final class One_Ngo_Blocks
{
    public static function init(): void
    {
        add_action('init', [self::class, 'register']);
    }

    public static function register(): void
    {
        wp_register_script(
            'one-ngo-block',
            ONE_NGO_PLUGIN_URL . 'assets/block.js',
            ['wp-blocks', 'wp-element', 'wp-components', 'wp-i18n', 'wp-block-editor', 'wp-api-fetch'],
            ONE_NGO_VERSION,
            true
        );

        wp_set_script_translations('one-ngo-block', 'one-ngo-fundraising');

        register_block_type('one-ngo/embed', [
            'api_version' => 3,
            'title' => __('1 NGO embed', 'one-ngo-fundraising'),
            'description' => __('Donate page, campaigns, events, or stories from 1 NGO.', 'one-ngo-fundraising'),
            'category' => 'widgets',
            'icon' => 'heart',
            'textdomain' => 'one-ngo-fundraising',
            'editor_script' => 'one-ngo-block',
            'attributes' => [
                'type' => ['type' => 'string', 'default' => 'donate'],
                'slug' => ['type' => 'string', 'default' => ''],
                'limit' => ['type' => 'number', 'default' => 3],
            ],
            'render_callback' => [self::class, 'render'],
        ]);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public static function render(array $attributes): string
    {
        return One_Ngo_Shortcodes::render([
            'type' => (string) ($attributes['type'] ?? 'donate'),
            'slug' => (string) ($attributes['slug'] ?? ''),
            'limit' => (string) ($attributes['limit'] ?? '3'),
        ]);
    }
}
