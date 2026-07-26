<?php

declare(strict_types=1);

namespace NativeScrollLoop;

if (! defined('ABSPATH') && PHP_SAPI !== 'cli') {
    exit;
}

final class Settings
{
    public const ENABLE_CONTROL = 'nsl_enabled';

    /**
     * @param array<string, mixed> $settings
     */
    public static function is_enabled(array $settings): bool
    {
        return 'yes' === ($settings[self::ENABLE_CONTROL] ?? '')
            && 'yes' !== ($settings['masonry'] ?? '')
            && ! self::has_multi_column_alternate_template($settings);
    }

    /**
     * Elementor renders alternate templates with grid spans. A horizontal flex-like
     * track cannot preserve spans greater than one without changing Loop Grid output.
     *
     * @param array<string, mixed> $settings
     */
    private static function has_multi_column_alternate_template(array $settings): bool
    {
        if ('yes' !== ($settings['alternate_template'] ?? '')) {
            return false;
        }

        $templates = $settings['alternate_templates'] ?? [];

        if (! is_array($templates)) {
            return false;
        }

        foreach ($templates as $template) {
            if (! is_array($template)) {
                continue;
            }

            foreach (['column_span', 'column_span_tablet', 'column_span_mobile'] as $span_key) {
                if (is_numeric($template[$span_key] ?? null) && 1 < (int) $template[$span_key]) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param array<string, mixed> $settings
     * @return array<string, bool|int|string>
     */
    public static function to_frontend_config(array $settings): array
    {
        return [
            'enabled' => self::is_enabled($settings),
            'snapEnabled' => self::switch_value($settings, 'nsl_snap_enabled', true),
            'snapStrictness' => self::allowed_value($settings, 'nsl_snap_strictness', ['mandatory', 'proximity'], 'mandatory'),
            'snapStop' => self::switch_value($settings, 'nsl_snap_stop', false),
            'scrollBehavior' => self::allowed_value($settings, 'nsl_scroll_behavior', ['smooth', 'instant'], 'smooth'),
            'arrowAdvance' => self::allowed_value($settings, 'nsl_arrow_advance', ['item', 'group'], 'item'),
            'showArrows' => self::switch_value($settings, 'nsl_show_arrows', true),
            'arrowPosition' => self::allowed_value($settings, 'nsl_arrow_position', ['top-right', 'bottom-right', 'split-sides'], 'top-right'),
            'hideArrowsMobile' => self::switch_value($settings, 'nsl_hide_arrows_mobile', true),
            'disableUnavailableArrows' => self::switch_value($settings, 'nsl_disable_unavailable_arrows', true),
            'showProgress' => self::switch_value($settings, 'nsl_show_progress', true),
            'progressMode' => self::allowed_value($settings, 'nsl_progress_mode', ['bar', 'thumb'], 'bar'),
            'progressPlacement' => self::allowed_value($settings, 'nsl_progress_placement', ['below', 'beside-navigation'], 'below'),
            'autoplay' => self::switch_value($settings, 'nsl_autoplay', false),
            'autoplayInterval' => self::integer_value($settings, 'nsl_autoplay_interval', 5000, 1500, 60000),
            'pauseOnHover' => self::switch_value($settings, 'nsl_pause_on_hover', true),
            'pauseOnFocus' => self::switch_value($settings, 'nsl_pause_on_focus', true),
            'pauseOnInteraction' => self::switch_value($settings, 'nsl_pause_on_interaction', true),
            'pauseWhenHidden' => self::switch_value($settings, 'nsl_pause_when_hidden', true),
            'resumeDelay' => self::integer_value($settings, 'nsl_resume_delay', 1200, 0, 60000),
            'autoplayEndBehavior' => self::allowed_value($settings, 'nsl_autoplay_end_behavior', ['rewind', 'stop'], 'rewind'),
            'ariaLabel' => self::text_value($settings, 'nsl_aria_label', 'Scrollable items'),
        ];
    }

    /**
     * @param array<string, mixed> $settings
     */
    private static function switch_value(array $settings, string $key, bool $default): bool
    {
        if (! array_key_exists($key, $settings)) {
            return $default;
        }

        return 'yes' === $settings[$key];
    }

    /**
     * @param array<string, mixed> $settings
     * @param string[] $allowed
     */
    private static function allowed_value(array $settings, string $key, array $allowed, string $default): string
    {
        $value = is_string($settings[$key] ?? null) ? $settings[$key] : '';

        return in_array($value, $allowed, true) ? $value : $default;
    }

    /**
     * @param array<string, mixed> $settings
     */
    private static function integer_value(array $settings, string $key, int $default, int $minimum, int $maximum): int
    {
        $value = is_numeric($settings[$key] ?? null) ? (int) $settings[$key] : $default;

        return max($minimum, min($maximum, $value));
    }

    /**
     * @param array<string, mixed> $settings
     */
    private static function text_value(array $settings, string $key, string $default): string
    {
        $value = is_string($settings[$key] ?? null) ? trim($settings[$key]) : '';

        if ('' === $value) {
            return $default;
        }

        return function_exists('sanitize_text_field') ? sanitize_text_field($value) : strip_tags($value);
    }
}
