<?php

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('\Elementor\Widget_Base')) {
    return;
}

final class One_Ngo_Elementor_Widget extends \Elementor\Widget_Base
{
    public function get_name(): string
    {
        return 'one-ngo-embed';
    }

    public function get_title(): string
    {
        return __('1 NGO embed', 'one-ngo-fundraising');
    }

    public function get_icon(): string
    {
        return 'eicon-heart';
    }

    public function get_categories(): array
    {
        return ['general'];
    }

    protected function register_controls(): void
    {
        $this->start_controls_section('content', ['label' => __('Embed', 'one-ngo-fundraising')]);
        $this->add_control('type', [
            'label' => __('Type', 'one-ngo-fundraising'),
            'type' => \Elementor\Controls_Manager::SELECT,
            'default' => 'donate',
            'options' => [
                'donate' => __('Donate page', 'one-ngo-fundraising'),
                'campaigns' => __('Campaigns', 'one-ngo-fundraising'),
                'campaign' => __('Single campaign', 'one-ngo-fundraising'),
                'events' => __('Events', 'one-ngo-fundraising'),
                'event' => __('Single event', 'one-ngo-fundraising'),
                'stories' => __('Stories', 'one-ngo-fundraising'),
                'story' => __('Single story', 'one-ngo-fundraising'),
            ],
        ]);
        $this->add_control('slug', [
            'label' => __('Slug', 'one-ngo-fundraising'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'condition' => ['type' => ['campaign', 'event', 'story']],
        ]);
        $this->add_control('limit', [
            'label' => __('Limit', 'one-ngo-fundraising'),
            'type' => \Elementor\Controls_Manager::NUMBER,
            'default' => 3,
            'min' => 1,
            'max' => 12,
            'condition' => ['type' => ['campaigns', 'events', 'stories']],
        ]);
        $this->end_controls_section();
    }

    protected function render(): void
    {
        $settings = $this->get_settings_for_display();
        $type = sanitize_key((string) ($settings['type'] ?? 'donate'));
        $slug = sanitize_title((string) ($settings['slug'] ?? ''));
        $limit = (string) max(1, min(12, absint($settings['limit'] ?? 3)));
        $html = One_Ngo_Shortcodes::render([
            'type' => $type,
            'slug' => $slug,
            'limit' => $limit,
        ]);
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- markup is escaped in One_Ngo_Public.
        echo $html;
    }
}
