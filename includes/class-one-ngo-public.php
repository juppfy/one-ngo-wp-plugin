<?php

if (!defined('ABSPATH')) {
    exit;
}

final class One_Ngo_Public
{
    public static function init(): void
    {
        add_action('wp_enqueue_scripts', [self::class, 'assets']);
        add_filter('body_class', [self::class, 'body_class']);
        add_filter('the_content', [self::class, 'replace_managed_page'], 1);
        add_filter('the_title', [self::class, 'hide_managed_title'], 10, 2);
    }

    public static function assets(): void
    {
        wp_register_style(
            'one-ngo-public',
            ONE_NGO_PLUGIN_URL . 'assets/public.css',
            [],
            ONE_NGO_VERSION
        );
        wp_register_script(
            'one-ngo-public',
            ONE_NGO_PLUGIN_URL . 'assets/public.js',
            [],
            ONE_NGO_VERSION,
            true
        );
        wp_localize_script('one-ngo-public', 'oneNgoPublic', [
            'embedOrigin' => ONE_NGO_EMBED_ORIGIN,
        ]);
        if (self::is_full_page()) {
            wp_enqueue_style('one-ngo-public');
            wp_enqueue_script('one-ngo-public');
        }
    }

    /**
     * @param array<int, string> $classes
     * @return array<int, string>
     */
    public static function body_class(array $classes): array
    {
        if (self::is_full_page()) {
            $classes[] = 'one-ngo-full-page';
        }
        return $classes;
    }

    public static function hide_managed_title($title, $post_id = 0)
    {
        if (!self::is_full_page()) {
            return $title;
        }
        if (is_admin()) {
            return $title;
        }
        if (in_the_loop() && (int) $post_id === self::current_managed_page_id()) {
            return '';
        }
        return $title;
    }

    public static function replace_managed_page(string $content): string
    {
        if (!is_singular('page') || !in_the_loop() || !is_main_query()) {
            return $content;
        }
        $kind = self::managed_kind_for_page((int) get_the_ID());
        if ($kind === '') {
            return $content;
        }
        return self::full_page_iframe(self::page_path($kind));
    }

    public static function is_full_page(): bool
    {
        if (sanitize_key((string) get_query_var('one_ngo_kind')) !== '') {
            return true;
        }
        if (is_singular('page')) {
            return self::managed_kind_for_page((int) get_queried_object_id()) !== '';
        }
        return false;
    }

    public static function current_managed_page_id(): int
    {
        if (!is_singular('page')) {
            return 0;
        }
        $id = (int) get_queried_object_id();
        return self::managed_kind_for_page($id) !== '' ? $id : 0;
    }

    public static function managed_kind_for_page(int $page_id): string
    {
        if ($page_id <= 0) {
            return '';
        }
        foreach (One_Ngo_Pages::ids() as $kind => $id) {
            if ((int) $id === $page_id) {
                return sanitize_key((string) $kind);
            }
        }
        return '';
    }

    public static function page_path(string $kind, string $slug = ''): string
    {
        $map = [
            'donate' => '/donate',
            'campaigns' => '/campaigns',
            'campaign' => '/campaigns/' . rawurlencode($slug),
            'events' => '/events',
            'event' => '/events/' . rawurlencode($slug),
            'stories' => '/stories',
            'story' => '/stories/' . rawurlencode($slug),
        ];
        return $map[$kind] ?? '/donate';
    }

    private static function current_request_path(): string
    {
        if (!isset($_SERVER['REQUEST_URI'])) {
            return '/';
        }
        $raw = sanitize_text_field(wp_unslash((string) $_SERVER['REQUEST_URI']));
        return $raw !== '' ? $raw : '/';
    }

    public static function full_page_iframe(string $page_path): string
    {
        wp_enqueue_style('one-ngo-public');
        wp_enqueue_script('one-ngo-public');

        $org_slug = One_Ngo_Api::org_slug();
        if ($org_slug === '') {
            return self::notice(__('1 NGO is not connected.', 'one-ngo-fundraising'));
        }

        $request_uri = self::current_request_path();
        $return_to = home_url($request_uri);
        $src = ONE_NGO_EMBED_ORIGIN . '/embed/' . rawurlencode($org_slug) . '/pages' . $page_path;
        $src = add_query_arg('return_to', $return_to, $src);

        return sprintf(
            '<div class="one-ngo-page-wrap"><iframe class="one-ngo-page-frame" src="%s" title="%s" loading="eager" referrerpolicy="strict-origin-when-cross-origin" allow="payment *"></iframe></div>',
            esc_url($src),
            esc_attr__('1 NGO page', 'one-ngo-fundraising')
        );
    }

    public static function checkout_iframe(string $embed_path): string
    {
        $org_slug = One_Ngo_Api::org_slug();
        if ($org_slug === '') {
            return self::notice(__('1 NGO is not connected.', 'one-ngo-fundraising'));
        }

        $request_uri = self::current_request_path();
        $return_to = home_url($request_uri);
        $src = ONE_NGO_EMBED_ORIGIN . '/embed/' . rawurlencode($org_slug) . $embed_path;
        $src = add_query_arg('return_to', $return_to, $src);

        return sprintf(
            '<iframe class="one-ngo-checkout" src="%s" title="%s" loading="lazy" referrerpolicy="strict-origin-when-cross-origin" allow="payment *" style="border:0;width:100%%;max-width:100%%;min-height:720px;height:720px;display:block;"></iframe>',
            esc_url($src),
            esc_attr__('Donate', 'one-ngo-fundraising')
        );
    }

    public static function render_kind(string $kind, string $slug = '', int $limit = 12): string
    {
        wp_enqueue_style('one-ngo-public');
        switch ($kind) {
            case 'donate':
                return self::wrap(self::checkout_iframe('/donate/checkout'));
            case 'campaigns':
                return self::render_index('campaigns', '/org-api/v1/campaigns', 'campaigns', $limit);
            case 'campaign':
                return self::render_campaign($slug);
            case 'events':
                return self::render_index('events', '/org-api/v1/events', 'events', $limit);
            case 'event':
                return self::render_event($slug);
            case 'stories':
                return self::render_index('stories', '/org-api/v1/stories', 'stories', $limit);
            case 'story':
                return self::render_story($slug);
            default:
                return self::notice(__('Unknown 1 NGO block.', 'one-ngo-fundraising'));
        }
    }

    private static function wrap(string $inner): string
    {
        $brand = One_Ngo_Api::brand();
        $colors = is_array($brand['identity']['colors'] ?? null) ? $brand['identity']['colors'] : [];
        $style = sprintf(
            '--one-ngo-primary:%s;--one-ngo-accent:%s;--one-ngo-bg:%s;--one-ngo-text:%s;',
            esc_attr((string) ($colors['primary'] ?? '#184b3a')),
            esc_attr((string) ($colors['accent'] ?? '#c4a35a')),
            esc_attr((string) ($colors['background'] ?? '#f7faf8')),
            esc_attr((string) ($colors['text'] ?? '#1c2b24'))
        );
        return '<div class="one-ngo-root" style="' . $style . '">' . $inner . '</div>';
    }

    private static function notice(string $message): string
    {
        if (!is_user_logged_in()) {
            return '';
        }
        return '<p class="one-ngo-empty">' . esc_html($message) . '</p>';
    }

    private static function empty_state(): string
    {
        return '<p class="one-ngo-empty">' . esc_html__('Nothing published yet.', 'one-ngo-fundraising') . '</p>';
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function item_or_null(array|WP_Error $item): ?array
    {
        return is_wp_error($item) ? null : $item;
    }

    private static function is_live_on_wordpress(array $item): bool
    {
        if (array_key_exists('serve_on_wordpress', $item)) {
            return !empty($item['serve_on_wordpress']);
        }
        return (($item['status'] ?? '') === 'published');
    }

    private static function render_index(string $route_kind, string $path, string $payload_key, int $limit): string
    {
        $items = One_Ngo_Api::list_published($path, $payload_key);
        if (is_wp_error($items)) {
            return self::wrap(self::notice($items->get_error_message()));
        }
        $items = array_values(array_filter($items, [self::class, 'is_live_on_wordpress']));
        $items = array_slice($items, 0, max(1, min(12, $limit)));
        if ($items === []) {
            return self::wrap(self::empty_state());
        }

        $html = '<div class="one-ngo-grid">';
        foreach ($items as $item) {
            $slug = sanitize_title((string) ($item['slug'] ?? ''));
            $title = (string) ($item['title'] ?? '');
            $summary = (string) ($item['summary'] ?? '');
            $cover = (string) ($item['cover_image_url'] ?? '');
            $href = $slug !== '' ? One_Ngo_Routes::url($route_kind, $slug) : '';
            $html .= '<article class="one-ngo-card">';
            if ($cover !== '') {
                $html .= '<a class="one-ngo-cover" href="' . esc_url($href) . '"><img src="' . esc_url($cover) . '" alt="" /></a>';
            }
            $html .= '<div class="one-ngo-card-body">';
            $html .= '<h2><a href="' . esc_url($href) . '">' . esc_html($title) . '</a></h2>';
            if ($summary !== '') {
                $html .= '<p>' . esc_html($summary) . '</p>';
            }
            $html .= '<a class="one-ngo-cta" href="' . esc_url($href) . '">' . esc_html__('Read more', 'one-ngo-fundraising') . '</a>';
            $html .= '</div></article>';
        }
        $html .= '</div>';
        return self::wrap($html);
    }

    private static function render_campaign(string $slug): string
    {
        $item = self::item_or_null(One_Ngo_Api::get_published_item('/org-api/v1/campaigns', 'campaign', $slug));
        if (!$item || !self::is_live_on_wordpress($item)) {
            return self::wrap(self::empty_state());
        }
        $html = self::article_header($item);
        $overview = (string) ($item['overview'] ?? '');
        $story = (string) ($item['story'] ?? '');
        if ($overview !== '') {
            $html .= '<section class="one-ngo-prose">' . wp_kses_post($overview) . '</section>';
        }
        if ($story !== '') {
            $html .= '<section class="one-ngo-prose">' . wp_kses_post($story) . '</section>';
        }
        $html .= '</article>';
        $html .= self::checkout_iframe('/campaigns/' . rawurlencode($slug) . '/checkout');
        return self::wrap($html);
    }

    private static function render_event(string $slug): string
    {
        $item = self::item_or_null(One_Ngo_Api::get_published_item('/org-api/v1/events', 'event', $slug));
        if (!$item || !self::is_live_on_wordpress($item)) {
            return self::wrap(self::empty_state());
        }
        $html = self::article_header($item);
        $description = (string) ($item['description'] ?? '');
        if ($description !== '') {
            $html .= '<section class="one-ngo-prose">' . wp_kses_post($description) . '</section>';
        }
        $starts = (string) ($item['starts_at'] ?? '');
        if ($starts !== '') {
            $html .= '<p class="one-ngo-meta">' . esc_html(mysql2date(get_option('date_format') . ' ' . get_option('time_format'), $starts)) . '</p>';
        }
        $venue = (string) ($item['venue'] ?? '');
        if ($venue !== '') {
            $html .= '<p class="one-ngo-meta">' . esc_html($venue) . '</p>';
        }
        $html .= '</article>';
        return self::wrap($html);
    }

    private static function render_story(string $slug): string
    {
        $item = self::item_or_null(One_Ngo_Api::get_published_item('/org-api/v1/stories', 'story', $slug));
        if (!$item || !self::is_live_on_wordpress($item)) {
            return self::wrap(self::empty_state());
        }
        $html = self::article_header($item);
        $blocks = $item['blocks'] ?? [];
        if (is_array($blocks)) {
            $html .= self::render_story_blocks($blocks);
        }
        $html .= '</article>';
        return self::wrap($html);
    }

    /**
     * @param array<string, mixed> $item
     */
    private static function article_header(array $item): string
    {
        $title = (string) ($item['title'] ?? '');
        $summary = (string) ($item['summary'] ?? '');
        $cover = (string) ($item['cover_image_url'] ?? '');
        $html = '<article class="one-ngo-article">';
        $html .= '<h1>' . esc_html($title) . '</h1>';
        if ($summary !== '') {
            $html .= '<p class="one-ngo-lede">' . esc_html($summary) . '</p>';
        }
        if ($cover !== '') {
            $html .= '<p class="one-ngo-hero"><img src="' . esc_url($cover) . '" alt="" /></p>';
        }
        return $html;
    }

    /**
     * @param array<int, mixed> $blocks
     */
    private static function render_story_blocks(array $blocks): string
    {
        $html = '<div class="one-ngo-prose">';
        foreach ($blocks as $block) {
            if (!is_array($block)) {
                continue;
            }
            $type = (string) ($block['type'] ?? '');
            if ($type === 'heading') {
                $level = max(1, min(5, (int) ($block['level'] ?? 2)));
                $text = (string) ($block['text'] ?? '');
                if ($text !== '') {
                    $html .= '<h' . $level . '>' . esc_html($text) . '</h' . $level . '>';
                }
            } elseif ($type === 'paragraph') {
                $html .= wp_kses_post((string) ($block['html'] ?? ''));
            } elseif ($type === 'image' && !empty($block['url'])) {
                $html .= '<figure><img src="' . esc_url((string) $block['url']) . '" alt="" />';
                if (!empty($block['caption'])) {
                    $html .= '<figcaption>' . esc_html((string) $block['caption']) . '</figcaption>';
                }
                $html .= '</figure>';
            } elseif ($type === 'quote') {
                $html .= '<blockquote><p>' . esc_html((string) ($block['text'] ?? '')) . '</p>';
                if (!empty($block['attribution'])) {
                    $html .= '<cite>' . esc_html((string) $block['attribution']) . '</cite>';
                }
                $html .= '</blockquote>';
            } elseif ($type === 'embed_campaign') {
                $html .= '<p><a class="one-ngo-cta" href="' . esc_url(One_Ngo_Routes::url('campaigns')) . '">' . esc_html__('View campaigns', 'one-ngo-fundraising') . '</a></p>';
            } elseif ($type === 'embed_donation') {
                $html .= '<p><a class="one-ngo-cta" href="' . esc_url(One_Ngo_Routes::url('donate')) . '">' . esc_html__('Donate', 'one-ngo-fundraising') . '</a></p>';
            } elseif ($type === 'embed_event') {
                $html .= '<p><a class="one-ngo-cta" href="' . esc_url(One_Ngo_Routes::url('events')) . '">' . esc_html__('View events', 'one-ngo-fundraising') . '</a></p>';
            }
        }
        $html .= '</div>';
        return $html;
    }
}
