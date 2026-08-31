<?php

if (!defined('ABSPATH')) {
    exit;
}

final class One_Ngo_Routes
{
    public const KINDS = ['donate', 'campaigns', 'events', 'stories'];

    /**
     * @return array<string, string>
     */
    public static function defaults(): array
    {
        return [
            'donate' => 'donate',
            'campaigns' => 'campaigns',
            'events' => 'events',
            'stories' => 'blogs',
        ];
    }

    public static function sanitize_slug(string $value, string $fallback): string
    {
        $slug = sanitize_title($value);
        if ($slug === '' || strlen($slug) > 80) {
            return $fallback;
        }
        return $slug;
    }

    /**
     * @param mixed $value
     * @return array<string, string>
     */
    public static function sanitize($value): array
    {
        $defaults = self::defaults();
        $input = is_array($value) ? $value : [];
        $out = [];
        foreach ($defaults as $kind => $fallback) {
            $out[$kind] = self::sanitize_slug((string) ($input[$kind] ?? $fallback), $fallback);
        }
        $unique = array_unique(array_values($out));
        if (count($unique) !== count($out)) {
            add_settings_error(
                'one_ngo',
                'routes',
                __('Donate, campaigns, events, and stories slugs must each be unique.', 'one-ngo-fundraising')
            );
            return $defaults;
        }
        return $out;
    }

    /**
     * @return array<string, string>
     */
    public static function all(): array
    {
        $defaults = self::defaults();
        $saved = get_option(One_Ngo_Api::OPTION_ROUTES, []);
        if (!is_array($saved)) {
            return $defaults;
        }
        $out = [];
        foreach ($defaults as $kind => $fallback) {
            $out[$kind] = self::sanitize_slug((string) ($saved[$kind] ?? $fallback), $fallback);
        }
        if (count(array_unique(array_values($out))) !== count($out)) {
            return $defaults;
        }
        return $out;
    }

    public static function slug(string $kind): string
    {
        $routes = self::all();
        return $routes[$kind] ?? '';
    }

    public static function url(string $kind, string $item_slug = ''): string
    {
        $parent = self::slug($kind);
        if ($parent === '') {
            return home_url('/');
        }
        $path = $item_slug !== '' ? $parent . '/' . $item_slug : $parent;
        return home_url('/' . $path);
    }

    public static function init(): void
    {
        add_action('init', [self::class, 'register']);
        add_filter('query_vars', [self::class, 'query_vars']);
        add_action('template_redirect', [self::class, 'template_redirect']);
        add_filter('redirect_canonical', [self::class, 'disable_canonical'], 10, 2);
        add_action('update_option_' . One_Ngo_Api::OPTION_ROUTES, [self::class, 'flush'], 10, 0);
    }

    public static function register(): void
    {
        add_rewrite_tag('%one_ngo_kind%', '([^&]+)');
        add_rewrite_tag('%one_ngo_slug%', '([^&]+)');

        $map = [
            'campaigns' => 'campaign',
            'events' => 'event',
            'stories' => 'story',
        ];
        foreach ($map as $route => $kind) {
            $parent = self::slug($route);
            if ($parent === '') {
                continue;
            }
            add_rewrite_rule(
                '^' . preg_quote($parent, '/') . '/([^/]+)/?$',
                'index.php?one_ngo_kind=' . $kind . '&one_ngo_slug=$matches[1]',
                'top'
            );
        }
    }

    /**
     * @param array<int, string> $vars
     * @return array<int, string>
     */
    public static function query_vars(array $vars): array
    {
        $vars[] = 'one_ngo_kind';
        $vars[] = 'one_ngo_slug';
        return $vars;
    }

    public static function disable_canonical($redirect, $requested)
    {
        if (get_query_var('one_ngo_kind')) {
            return false;
        }
        return $redirect;
    }

    public static function flush(): void
    {
        self::register();
        flush_rewrite_rules();
        One_Ngo_Heartbeat::sync();
    }

    public static function template_redirect(): void
    {
        $kind = sanitize_key((string) get_query_var('one_ngo_kind'));
        $slug = sanitize_title((string) get_query_var('one_ngo_slug'));
        if ($kind === '' || $slug === '') {
            return;
        }

        status_header(200);
        nocache_headers();
        global $wp_query;
        if ($wp_query instanceof WP_Query) {
            $wp_query->is_404 = false;
        }

        get_header();
        $iframe = One_Ngo_Public::full_page_iframe(One_Ngo_Public::page_path($kind, $slug));
        echo '<main class="one-ngo-main" id="one-ngo-content">';
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- iframe HTML is escaped in One_Ngo_Public::full_page_iframe().
        echo $iframe;
        echo '</main>';
        get_footer();
        exit;
    }
}
