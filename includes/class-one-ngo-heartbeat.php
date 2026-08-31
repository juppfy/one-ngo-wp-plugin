<?php

if (!defined('ABSPATH')) {
    exit;
}

final class One_Ngo_Heartbeat
{
    public static function init(): void
    {
        add_action('admin_init', [self::class, 'maybe_sync']);
        add_action('update_option_' . One_Ngo_Api::OPTION_ORG_ID, [self::class, 'sync'], 20, 0);
        add_action('update_option_' . One_Ngo_Api::OPTION_TOKEN, [self::class, 'sync'], 20, 0);
        add_action('update_option_' . One_Ngo_Api::OPTION_API_BASE, [self::class, 'sync'], 20, 0);
        add_action('update_option_' . One_Ngo_Api::OPTION_ROUTES, [self::class, 'sync'], 20, 0);
    }

    public static function maybe_sync(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }
        if (!One_Ngo_Api::is_configured()) {
            return;
        }
        if (get_transient('one_ngo_heartbeat')) {
            return;
        }
        self::sync();
        set_transient('one_ngo_heartbeat', '1', HOUR_IN_SECONDS);
    }

    public static function sync(): void
    {
        if (!One_Ngo_Api::is_configured()) {
            return;
        }
        One_Ngo_Api::post('/org-api/v1/wordpress/site', [
            'site_url' => home_url('/'),
            'routes' => One_Ngo_Routes::all(),
        ]);
    }
}
