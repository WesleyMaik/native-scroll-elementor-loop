<?php

declare(strict_types=1);

namespace NativeScrollLoop;

if (! defined('ABSPATH') && PHP_SAPI !== 'cli') {
    exit;
}

final class Assets
{
    public const SCRIPT_HANDLE = 'native-scroll-loop';
    public const STYLE_HANDLE = 'native-scroll-loop';

    public function register(): void
    {
        $this->register_script();
        $this->register_style();
    }

    public function register_script(): void
    {
        wp_register_script(
            self::SCRIPT_HANDLE,
            NATIVE_SCROLL_LOOP_URL . 'assets/js/native-scroll-loop.js',
            ['elementor-frontend'],
            NATIVE_SCROLL_LOOP_VERSION,
            true
        );
    }

    public function register_style(): void
    {
        wp_register_style(
            self::STYLE_HANDLE,
            NATIVE_SCROLL_LOOP_URL . 'assets/css/native-scroll-loop.css',
            ['elementor-frontend'],
            NATIVE_SCROLL_LOOP_VERSION
        );
    }

    public function enqueue(): void
    {
        wp_enqueue_script(self::SCRIPT_HANDLE);
        wp_enqueue_style(self::STYLE_HANDLE);
    }

    public function enqueue_style(): void
    {
        wp_enqueue_style(self::STYLE_HANDLE);
    }
}
