<?php

if (!defined('ABSPATH')) {
    exit;
}

final class One_Ngo_Elementor
{
    public static function init(): void
    {
        add_action('elementor/widgets/register', [self::class, 'register']);
    }

    public static function register($widgets_manager): void
    {
        if (!is_object($widgets_manager) || !method_exists($widgets_manager, 'register')) {
            return;
        }
        require_once ONE_NGO_PLUGIN_DIR . 'includes/class-one-ngo-elementor-widget.php';
        $widgets_manager->register(new One_Ngo_Elementor_Widget());
    }
}
