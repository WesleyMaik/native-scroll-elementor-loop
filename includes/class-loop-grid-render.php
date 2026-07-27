<?php

declare(strict_types=1);

namespace NativeScrollLoop;

use Elementor\Controls_Stack;
use Elementor\Icons_Manager;

if (! defined('ABSPATH') && PHP_SAPI !== 'cli') {
    exit;
}

final class Loop_Grid_Render
{
    private Assets $assets;

    public function __construct(Assets $assets)
    {
        $this->assets = $assets;
    }

    public function before_render(Controls_Stack $widget): void
    {
        if (! $this->supports($widget)) {
            return;
        }

        $settings = $widget->get_settings_for_display();

        if (! Settings::is_enabled($settings)) {
            return;
        }

        $config = Settings::to_frontend_config($settings);

        $widget->add_render_attribute('_wrapper', 'class', [
            'native-scroll-loop',
            'native-scroll-loop--' . $config['arrowPosition'],
            'native-scroll-loop--progress-' . $config['progressMode'],
        ]);
        $widget->add_render_attribute('_wrapper', 'data-native-scroll-loop', 'true');
        $widget->add_render_attribute('_wrapper', 'data-native-scroll-settings', wp_json_encode($config));

        $this->assets->enqueue();
    }

    public function filter_content(string $content, Controls_Stack $widget): string
    {
        if (! $this->supports($widget)) {
            return $content;
        }

        $settings = $widget->get_settings_for_display();

        if (! Settings::is_enabled($settings)) {
            return $content;
        }

        $config = Settings::to_frontend_config($settings);
        $config_marker = sprintf(
            '<span hidden data-native-scroll-loop-config data-native-scroll-settings="%s"></span>',
            esc_attr(wp_json_encode($config))
        );
        $navigation = $config['showArrows'] ? $this->render_navigation($settings) : '';
        $progress = $config['showProgress'] ? $this->render_progress((string) $config['progressMode']) : '';
        $before = '';
        $after = '';

        if ('' !== $navigation && 'top-right' === $config['arrowPosition']) {
            $before = $this->render_controls($navigation, 'top', 'beside-navigation' === $config['progressPlacement'] ? $progress : '');
        } elseif ('' !== $navigation && 'split-sides' === $config['arrowPosition']) {
            $before = $this->render_controls($navigation, 'split');
        } elseif ('' !== $navigation) {
            $after .= $this->render_controls($navigation, 'bottom', 'beside-navigation' === $config['progressPlacement'] ? $progress : '');
        }

        $progress_has_navigation = '' !== $navigation
            && 'beside-navigation' === $config['progressPlacement']
            && 'split-sides' !== $config['arrowPosition'];

        if ('' !== $progress && ! $progress_has_navigation) {
            $after .= $this->render_controls('', 'progress', $progress);
        }

        return $config_marker . $before . $content . $after;
    }

    private function supports(Controls_Stack $widget): bool
    {
        return method_exists($widget, 'get_name') && 'loop-grid' === $widget->get_name();
    }

    /**
     * @param array<string, mixed> $settings
     */
    private function render_navigation(array $settings): string
    {
        $previous_icon = is_array($settings['nsl_previous_icon'] ?? null) ? $settings['nsl_previous_icon'] : [];
        $next_icon = is_array($settings['nsl_next_icon'] ?? null) ? $settings['nsl_next_icon'] : [];

        return sprintf(
            '<nav class="native-scroll-loop__navigation" aria-label="%1$s"><button class="native-scroll-loop__arrow native-scroll-loop__arrow--previous" type="button" data-native-scroll-loop-previous aria-label="%2$s">%3$s</button><button class="native-scroll-loop__arrow native-scroll-loop__arrow--next" type="button" data-native-scroll-loop-next aria-label="%4$s">%5$s</button></nav>',
            esc_attr(esc_html__('Carousel navigation', 'native-scroll-loop')),
            esc_attr(esc_html__('Previous items', 'native-scroll-loop')),
            $this->render_icon($previous_icon, '&larr;'),
            esc_attr(esc_html__('Next items', 'native-scroll-loop')),
            $this->render_icon($next_icon, '&rarr;')
        );
    }

    private function render_progress(string $mode): string
    {
        return sprintf(
            '<div class="native-scroll-loop__progress native-scroll-loop__progress--%1$s" data-native-scroll-loop-progress role="progressbar" aria-label="%2$s" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"><span class="native-scroll-loop__progress-bar" data-native-scroll-loop-progress-bar></span></div>',
            esc_attr($mode),
            esc_attr(esc_html__('Carousel scroll progress', 'native-scroll-loop'))
        );
    }

    private function render_controls(string $navigation, string $position, string $progress = ''): string
    {
        return sprintf(
            '<div class="native-scroll-loop__controls native-scroll-loop__controls--%1$s">%2$s%3$s</div>',
            esc_attr($position),
            $progress,
            $navigation
        );
    }

    /**
     * @param array<string, mixed> $icon
     */
    private function render_icon(array $icon, string $fallback): string
    {
        if (empty($icon['value'])) {
            return '<span aria-hidden="true">' . $fallback . '</span>';
        }

        ob_start();
        Icons_Manager::render_icon($icon, ['aria-hidden' => 'true']);

        return (string) ob_get_clean();
    }
}
