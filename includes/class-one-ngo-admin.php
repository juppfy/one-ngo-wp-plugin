<?php

if (!defined('ABSPATH')) {
    exit;
}

final class One_Ngo_Admin
{
    public static function init(): void
    {
        add_action('admin_menu', [self::class, 'menu']);
        add_action('admin_init', [self::class, 'register']);
        add_action('admin_init', [self::class, 'handle_create_page']);
        add_action('admin_init', [self::class, 'handle_disconnect']);
        add_action('admin_enqueue_scripts', [self::class, 'assets']);
        add_action('update_option_' . One_Ngo_Api::OPTION_ORG_ID, [self::class, 'bust_cache'], 10, 0);
        add_action('update_option_' . One_Ngo_Api::OPTION_TOKEN, [self::class, 'bust_cache'], 10, 0);
        add_action('update_option_' . One_Ngo_Api::OPTION_API_BASE, [self::class, 'bust_cache'], 10, 0);
        add_action('add_option_' . One_Ngo_Api::OPTION_TOKEN, [self::class, 'disable_token_autoload'], 10, 0);
        add_action('update_option_' . One_Ngo_Api::OPTION_TOKEN, [self::class, 'disable_token_autoload'], 20, 0);
        add_filter('plugin_action_links_' . plugin_basename(ONE_NGO_PLUGIN_FILE), [self::class, 'action_links']);
        add_filter('plugin_row_meta', [self::class, 'row_meta'], 10, 2);
    }

    public static function bust_cache(): void
    {
        One_Ngo_Api::clear_cache();
    }

    public static function disable_token_autoload(): void
    {
        if (function_exists('wp_set_option_autoload')) {
            wp_set_option_autoload(One_Ngo_Api::OPTION_TOKEN, false);
        }
    }

    /**
     * @param array<int, string> $links
     * @return array<int, string>
     */
    public static function action_links(array $links): array
    {
        $settings = sprintf(
            '<a href="%s">%s</a>',
            esc_url(admin_url('options-general.php?page=one-ngo')),
            esc_html__('Settings', 'one-ngo-fundraising')
        );
        array_unshift($links, $settings);
        return $links;
    }

    /**
     * @param array<int, string> $links
     * @return array<int, string>
     */
    public static function row_meta(array $links, string $file): array
    {
        if ($file !== plugin_basename(ONE_NGO_PLUGIN_FILE)) {
            return $links;
        }
        $links[] = '<a href="' . esc_url(ONE_NGO_DOCS_URL) . '" target="_blank" rel="noopener noreferrer">' . esc_html__('Docs', 'one-ngo-fundraising') . '</a>';
        $links[] = '<a href="' . esc_url(ONE_NGO_PRIVACY_URL) . '" target="_blank" rel="noopener noreferrer">' . esc_html__('Privacy', 'one-ngo-fundraising') . '</a>';
        $links[] = '<a href="' . esc_url(ONE_NGO_TERMS_URL) . '" target="_blank" rel="noopener noreferrer">' . esc_html__('Terms', 'one-ngo-fundraising') . '</a>';
        return $links;
    }

    public static function assets(string $hook): void
    {
        if ($hook !== 'settings_page_one-ngo') {
            return;
        }
        wp_enqueue_style('one-ngo-admin', ONE_NGO_PLUGIN_URL . 'assets/admin.css', [], ONE_NGO_VERSION);
        wp_enqueue_script('one-ngo-admin', ONE_NGO_PLUGIN_URL . 'assets/admin.js', [], ONE_NGO_VERSION, true);
    }

    public static function menu(): void
    {
        add_options_page(
            __('1 NGO', 'one-ngo-fundraising'),
            __('1 NGO', 'one-ngo-fundraising'),
            'manage_options',
            'one-ngo',
            [self::class, 'render']
        );
    }

    public static function register(): void
    {
        register_setting('one_ngo', One_Ngo_Api::OPTION_ORG_ID, [
            'type' => 'string',
            'sanitize_callback' => [self::class, 'sanitize_org_id'],
            'show_in_rest' => false,
            'default' => '',
        ]);
        register_setting('one_ngo', One_Ngo_Api::OPTION_TOKEN, [
            'type' => 'string',
            'sanitize_callback' => [self::class, 'sanitize_token'],
            'show_in_rest' => false,
            'default' => '',
        ]);
        register_setting('one_ngo', One_Ngo_Api::OPTION_API_BASE, [
            'type' => 'string',
            'sanitize_callback' => [self::class, 'sanitize_api_base'],
            'show_in_rest' => false,
            'default' => '',
        ]);
        register_setting('one_ngo', One_Ngo_Api::OPTION_ROUTES, [
            'type' => 'array',
            'sanitize_callback' => [One_Ngo_Routes::class, 'sanitize'],
            'show_in_rest' => false,
            'default' => One_Ngo_Routes::defaults(),
        ]);
    }

    public static function handle_create_page(): void
    {
        if (!isset($_POST['one_ngo_create_page'])) {
            return;
        }
        if (!current_user_can('manage_options')) {
            return;
        }
        check_admin_referer('one_ngo_create_page');
        $kind = sanitize_key((string) ($_POST['one_ngo_page_kind'] ?? ''));
        $result = One_Ngo_Pages::create_index($kind);
        if (is_wp_error($result)) {
            add_settings_error('one_ngo', 'page', $result->get_error_message());
            return;
        }
        add_settings_error('one_ngo', 'page', __('WordPress page created. The parent slug is now locked for future items.', 'one-ngo-fundraising'), 'updated');
    }

    public static function handle_disconnect(): void
    {
        if (!isset($_POST['one_ngo_disconnect'])) {
            return;
        }
        if (!current_user_can('manage_options')) {
            return;
        }
        check_admin_referer('one_ngo_disconnect');
        delete_option(One_Ngo_Api::OPTION_TOKEN);
        One_Ngo_Api::clear_cache();
        add_settings_error('one_ngo', 'disconnect', __('Saved token removed. Pages and slugs were kept.', 'one-ngo-fundraising'), 'updated');
    }

    public static function sanitize_org_id($value): string
    {
        $value = strtolower(trim((string) $value));
        if ($value === '') {
            return '';
        }
        if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $value)) {
            add_settings_error('one_ngo', 'org_id', __('Organization ID must be the UUID shown in 1 NGO → Integrations → WordPress.', 'one-ngo-fundraising'));
            return One_Ngo_Api::organization_id();
        }
        return $value;
    }

    public static function sanitize_token($value): string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return One_Ngo_Api::api_token();
        }
        if (!str_starts_with($value, '1ngo_') || strlen($value) < 20) {
            add_settings_error('one_ngo', 'token', __('Token must be the read-only WordPress plugin token from 1 NGO (starts with 1ngo_).', 'one-ngo-fundraising'));
            return One_Ngo_Api::api_token();
        }
        return $value;
    }

    public static function sanitize_api_base($value): string
    {
        $value = untrailingslashit(esc_url_raw(trim((string) $value)));
        if ($value === '') {
            return '';
        }
        if (!preg_match('#^https://#i', $value) && !preg_match('#^http://(localhost|127\\.0\\.0\\.1)(:\\d+)?$#i', $value)) {
            add_settings_error('one_ngo', 'api_base', __('API URL must be https (or http://localhost for development).', 'one-ngo-fundraising'));
            return One_Ngo_Api::api_base();
        }
        return $value;
    }

    public static function render(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $me = One_Ngo_Api::is_configured() ? One_Ngo_Api::me() : null;
        $connected = is_array($me) && !empty($me['ok']);
        $routes = One_Ngo_Routes::all();
        $has_token = One_Ngo_Api::api_token() !== '';
        $shortcodes = [
            ['label' => __('Donate page', 'one-ngo-fundraising'), 'code' => '[1ngo donate]'],
            ['label' => __('Campaigns index', 'one-ngo-fundraising'), 'code' => '[1ngo campaigns limit="3"]'],
            ['label' => __('Single campaign', 'one-ngo-fundraising'), 'code' => '[1ngo campaign slug="your-campaign-slug"]'],
            ['label' => __('Events index', 'one-ngo-fundraising'), 'code' => '[1ngo events limit="3"]'],
            ['label' => __('Single event', 'one-ngo-fundraising'), 'code' => '[1ngo event slug="your-event-slug"]'],
            ['label' => __('Stories index', 'one-ngo-fundraising'), 'code' => '[1ngo stories limit="3"]'],
            ['label' => __('Single story', 'one-ngo-fundraising'), 'code' => '[1ngo story slug="your-story-slug"]'],
        ];
        $page_kinds = [
            'donate' => __('Add donation page', 'one-ngo-fundraising'),
            'campaigns' => __('Add campaigns page', 'one-ngo-fundraising'),
            'events' => __('Add events page', 'one-ngo-fundraising'),
            'stories' => __('Add stories page', 'one-ngo-fundraising'),
        ];
        ?>
        <div class="wrap one-ngo-admin">
            <div class="one-ngo-admin-header">
                <img class="one-ngo-admin-logo" src="<?php echo esc_url(ONE_NGO_PLUGIN_URL . 'assets/logo.svg'); ?>" alt="" width="48" height="48" />
                <div>
                    <h1><?php echo esc_html__('1 NGO', 'one-ngo-fundraising'); ?></h1>
                    <p><?php echo esc_html__('Paste credentials from 1 NGO → Integrations → WordPress. No 1 NGO DNS records are required — this plugin serves donate, campaigns, events, and stories on this WordPress site.', 'one-ngo-fundraising'); ?></p>
                </div>
            </div>
            <p class="one-ngo-admin-links">
                <a href="<?php echo esc_url(ONE_NGO_DOCS_URL); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html__('Documentation', 'one-ngo-fundraising'); ?></a>
                <a href="<?php echo esc_url(ONE_NGO_PRIVACY_URL); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html__('Privacy policy', 'one-ngo-fundraising'); ?></a>
                <a href="<?php echo esc_url(ONE_NGO_TERMS_URL); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html__('Terms of use', 'one-ngo-fundraising'); ?></a>
            </p>
            <?php settings_errors('one_ngo'); ?>
            <?php if ($connected) : ?>
                <div class="notice notice-success"><p>
                    <?php
                    echo esc_html(sprintf(
                        /* translators: %s organization name */
                        __('Connected to %s.', 'one-ngo-fundraising'),
                        (string) ($me['organization']['name'] ?? '1 NGO')
                    ));
                    ?>
                </p></div>
            <?php elseif (is_wp_error($me)) : ?>
                <div class="notice notice-error"><p><?php echo esc_html($me->get_error_message()); ?></p></div>
            <?php endif; ?>

            <form action="options.php" method="post">
                <?php settings_fields('one_ngo'); ?>
                <h2><?php echo esc_html__('Connection', 'one-ngo-fundraising'); ?></h2>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="one_ngo_organization_id"><?php echo esc_html__('Organization ID', 'one-ngo-fundraising'); ?></label></th>
                        <td>
                            <input class="regular-text code" type="text" id="one_ngo_organization_id" name="<?php echo esc_attr(One_Ngo_Api::OPTION_ORG_ID); ?>" value="<?php echo esc_attr(One_Ngo_Api::organization_id()); ?>" autocomplete="off" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="one_ngo_api_token"><?php echo esc_html__('Read-only token', 'one-ngo-fundraising'); ?></label></th>
                        <td>
                            <input class="regular-text" type="password" id="one_ngo_api_token" name="<?php echo esc_attr(One_Ngo_Api::OPTION_TOKEN); ?>" value="" autocomplete="new-password" placeholder="<?php echo $has_token ? esc_attr__('Token saved — leave blank to keep it', 'one-ngo-fundraising') : ''; ?>" />
                            <p class="description"><?php echo $has_token ? esc_html__('A token is already saved. Leave this blank unless you are replacing it.', 'one-ngo-fundraising') : esc_html__('Paste the read-only WordPress plugin token from 1 NGO.', 'one-ngo-fundraising'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="one_ngo_api_base"><?php echo esc_html__('API URL', 'one-ngo-fundraising'); ?></label></th>
                        <td>
                            <input class="regular-text code" type="url" id="one_ngo_api_base" name="<?php echo esc_attr(One_Ngo_Api::OPTION_API_BASE); ?>" value="<?php echo esc_attr(One_Ngo_Api::api_base()); ?>" placeholder="https://your-railway-host" required />
                            <p class="description"><?php echo esc_html__('Required. Copy API URL from 1 NGO → Integrations → WordPress.', 'one-ngo-fundraising'); ?></p>
                        </td>
                    </tr>
                </table>

                <h2><?php echo esc_html__('Public URL map', 'one-ngo-fundraising'); ?></h2>
                <p><?php echo esc_html__('Lock parent slugs on this WordPress site. Future campaigns, events, and stories published in 1 NGO are served under these prefixes. Changing a locked slug later will break existing links.', 'one-ngo-fundraising'); ?></p>
                <table class="form-table" role="presentation">
                    <?php foreach (['donate' => __('Donation page', 'one-ngo-fundraising'), 'campaigns' => __('Campaigns', 'one-ngo-fundraising'), 'events' => __('Events', 'one-ngo-fundraising'), 'stories' => __('Stories', 'one-ngo-fundraising')] as $kind => $label) : ?>
                        <tr>
                            <th scope="row"><label for="one_ngo_route_<?php echo esc_attr($kind); ?>"><?php echo esc_html($label); ?></label></th>
                            <td>
                                <code><?php echo esc_html(home_url('/')); ?></code>
                                <input class="regular-text code" type="text" id="one_ngo_route_<?php echo esc_attr($kind); ?>" name="<?php echo esc_attr(One_Ngo_Api::OPTION_ROUTES); ?>[<?php echo esc_attr($kind); ?>]" value="<?php echo esc_attr($routes[$kind]); ?>" />
                                <p class="description">
                                    <?php
                                    echo esc_html($kind === 'donate'
                                        ? sprintf(/* translators: %s example URL */ __('Visitors donate at %s', 'one-ngo-fundraising'), One_Ngo_Routes::url('donate'))
                                        : sprintf(
                                            /* translators: 1: index URL, 2: parent slug */
                                            __('Index at %1$s — singles at /%2$s/{slug}', 'one-ngo-fundraising'),
                                            One_Ngo_Routes::url($kind),
                                            $routes[$kind]
                                        ));
                                    ?>
                                </p>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
                <?php submit_button(); ?>
            </form>

            <?php if ($has_token) : ?>
                <form method="post" class="one-ngo-disconnect">
                    <?php wp_nonce_field('one_ngo_disconnect'); ?>
                    <button class="button" type="submit" name="one_ngo_disconnect" value="1">
                        <?php echo esc_html__('Remove saved token', 'one-ngo-fundraising'); ?>
                    </button>
                </form>
            <?php endif; ?>

            <h2><?php echo esc_html__('Add WordPress pages', 'one-ngo-fundraising'); ?></h2>
            <p><?php echo esc_html__('Creates a published WordPress page at the slug above. That page uses the full 1 NGO layout in the content area (theme header and footer stay). Use this instead of pointing DNS at 1 NGO.', 'one-ngo-fundraising'); ?></p>
            <div class="one-ngo-page-actions">
                <?php foreach ($page_kinds as $kind => $label) : ?>
                    <form method="post">
                        <?php wp_nonce_field('one_ngo_create_page'); ?>
                        <input type="hidden" name="one_ngo_page_kind" value="<?php echo esc_attr($kind); ?>" />
                        <?php
                        $page_id = One_Ngo_Pages::page_id($kind);
                        $exists = $page_id && get_post($page_id);
                        ?>
                        <button class="button <?php echo $exists ? '' : 'button-primary'; ?>" type="submit" name="one_ngo_create_page" value="1" <?php disabled($exists); ?>>
                            <?php echo $exists ? esc_html__('Page already created', 'one-ngo-fundraising') : esc_html($label); ?>
                        </button>
                        <?php if ($exists) : ?>
                            <a href="<?php echo esc_url(get_edit_post_link($page_id)); ?>"><?php echo esc_html__('Edit page', 'one-ngo-fundraising'); ?></a>
                            <a href="<?php echo esc_url(get_permalink($page_id)); ?>"><?php echo esc_html__('View', 'one-ngo-fundraising'); ?></a>
                        <?php endif; ?>
                    </form>
                <?php endforeach; ?>
            </div>

            <h2><?php echo esc_html__('Shortcodes', 'one-ngo-fundraising'); ?></h2>
            <p><?php echo esc_html__('Widgets for any other page. Dedicated 1 NGO pages above already use the full layout — these shortcodes stay compact. Also available as a Gutenberg block or Elementor widget.', 'one-ngo-fundraising'); ?></p>
            <div class="one-ngo-shortcodes">
                <?php foreach ($shortcodes as $row) : ?>
                    <div class="one-ngo-shortcode-row">
                        <code data-one-ngo-copy="<?php echo esc_attr($row['code']); ?>"><?php echo esc_html($row['code']); ?></code>
                        <button class="button" type="button" data-one-ngo-copy-btn="<?php echo esc_attr($row['code']); ?>" aria-label="<?php echo esc_attr(sprintf(/* translators: %s shortcode label */ __('Copy %s', 'one-ngo-fundraising'), $row['label'])); ?>">
                            <?php echo esc_html__('Copy', 'one-ngo-fundraising'); ?>
                        </button>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }
}
