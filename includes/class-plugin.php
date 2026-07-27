<?php

declare(strict_types=1);

namespace NativeScrollLoop;

if (! defined('ABSPATH') && PHP_SAPI !== 'cli') {
    exit;
}

final class Plugin
{
    private static ?self $instance = null;

    private Assets $assets;
    private Loop_Grid_Controls $controls;
    private Loop_Grid_Render $renderer;

    public function __construct()
    {
        $this->assets = new Assets();
        $this->controls = new Loop_Grid_Controls();
        $this->renderer = new Loop_Grid_Render($this->assets);
    }

    public static function instance(): self
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function boot(): void
    {
        add_action('elementor/init', [$this, 'initialize'], 20);
        add_action('admin_notices', [$this, 'render_dependency_notice']);
    }

    public function initialize(): void
    {
        if (! $this->dependencies_available()) {
            return;
        }

        add_action('elementor/frontend/after_register_scripts', [$this->assets, 'register_script']);
        add_action('elementor/frontend/after_register_styles', [$this->assets, 'register_style']);
        add_action('elementor/frontend/after_enqueue_styles', [$this->assets, 'enqueue_style']);
        add_action('elementor/preview/enqueue_styles', [$this->assets, 'enqueue_style']);
        add_action('elementor/preview/enqueue_scripts', [$this->assets, 'enqueue_script']);
        add_action(
            'elementor/element/loop-grid/section_additional_options/after_section_end',
            [$this->controls, 'register']
        );
        add_action('elementor/frontend/widget/before_render', [$this->renderer, 'before_render']);
        add_filter('elementor/widget/render_content', [$this->renderer, 'filter_content'], 10, 2);
    }

    public function render_dependency_notice(): void
    {
        if ($this->dependencies_available()) {
            return;
        }

        if (function_exists('current_user_can') && ! current_user_can('activate_plugins')) {
            return;
        }

        $message = esc_html__(
            'Native Scroll Loop requires Elementor and a compatible Elementor Pro implementation with Loop Grid support.',
            'native-scroll-loop'
        );

        echo '<div class="notice notice-error"><p>' . esc_html($message) . '</p></div>';
    }

    private function dependencies_available(): bool
    {
        return defined('ELEMENTOR_VERSION')
            && defined('ELEMENTOR_PRO_VERSION')
            && class_exists('\Elementor\Controls_Manager')
            && class_exists('\ElementorPro\Modules\LoopBuilder\Widgets\Loop_Grid');
    }
}
