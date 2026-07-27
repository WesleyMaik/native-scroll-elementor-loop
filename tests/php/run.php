<?php

declare(strict_types=1);

namespace Elementor {
    class Controls_Manager
    {
        public const TAB_CONTENT = 'content';
        public const TAB_STYLE = 'style';
        public const SWITCHER = 'switcher';
        public const SELECT = 'select';
        public const NUMBER = 'number';
        public const TEXT = 'text';
        public const ICONS = 'icons';
        public const SLIDER = 'slider';
        public const COLOR = 'color';
        public const HEADING = 'heading';
        public const ALERT = 'alert';
    }

    class Controls_Stack
    {
    }

    class Icons_Manager
    {
        /**
         * @param array<string, string> $icon
         * @param array<string, string> $attributes
         */
        public static function render_icon(array $icon, array $attributes = [], string $tag = 'i'): void
        {
            echo '<svg aria-hidden="true"></svg>';
        }
    }
}

namespace ElementorPro\Modules\LoopBuilder\Widgets {
    class Loop_Grid
    {
    }
}

namespace {
    const TEST_ROOT = __DIR__ . '/../..';
    const NATIVE_SCROLL_LOOP_FILE = TEST_ROOT . '/native-scroll-loop.php';
    const NATIVE_SCROLL_LOOP_URL = 'https://example.test/native-scroll-loop/';
    const NATIVE_SCROLL_LOOP_VERSION = '1.1.1';
    const ELEMENTOR_VERSION = '4.0.7';
    const ELEMENTOR_PRO_VERSION = '4.0.4.2';

    $GLOBALS['nsl_test_enqueued'] = [];
    $GLOBALS['nsl_test_hooks'] = [];

    function esc_html__(string $text, string $domain = ''): string
    {
        return $text;
    }

    function esc_attr(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }

    function esc_html(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }

    /**
     * @param mixed $value
     */
    function wp_json_encode($value, int $flags = 0, int $depth = 512): string
    {
        return (string) json_encode($value, $flags, $depth);
    }

    /** @param string[] $dependencies */
    function wp_register_script(string $handle, string $source, array $dependencies, string $version, bool $footer): void
    {
    }

    /** @param string[] $dependencies */
    function wp_register_style(string $handle, string $source, array $dependencies, string $version): void
    {
    }

    function wp_enqueue_script(string $handle): void
    {
        $GLOBALS['nsl_test_enqueued'][] = $handle;
    }

    function wp_enqueue_style(string $handle): void
    {
        $GLOBALS['nsl_test_enqueued'][] = $handle;
    }

    /** @param callable|array{object|string, string} $callback */
    function add_action(string $hook, $callback, int $priority = 10, int $accepted_arguments = 1): void
    {
        $GLOBALS['nsl_test_hooks']['actions'][$hook][] = [$callback, $priority, $accepted_arguments];
    }

    /** @param callable|array{object|string, string} $callback */
    function add_filter(string $hook, $callback, int $priority = 10, int $accepted_arguments = 1): void
    {
        $GLOBALS['nsl_test_hooks']['filters'][$hook][] = [$callback, $priority, $accepted_arguments];
    }

    /**
     * @param mixed $actual
     * @param mixed $expected
     */
    function assert_same($actual, $expected, string $message): void
    {
        if ($actual === $expected) {
            return;
        }

        fwrite(STDERR, sprintf("FAIL: %s\nExpected: %s\nActual: %s\n", $message, var_export($expected, true), var_export($actual, true)));
        exit(1);
    }

    function assert_contains(string $needle, string $haystack, string $message): void
    {
        assert_same(str_contains($haystack, $needle), true, $message);
    }

    final class FakeLoopGrid extends \Elementor\Controls_Stack
    {
        /** @var array<string, mixed> */
        private array $settings;

        /** @var array<string, array<string, mixed>> */
        public array $attributes = [];

        /** @var string[] */
        public array $controlIds = [];

        /** @var array<string, array<string, mixed>> */
        public array $controlArguments = [];

        /** @param array<string, mixed> $settings */
        public function __construct(array $settings = [])
        {
            $this->settings = $settings;
        }

        public function get_name(): string
        {
            return 'loop-grid';
        }

        /** @return array<string, mixed> */
        public function get_settings_for_display(): array
        {
            return $this->settings;
        }

        /** @param string|string[]|array<string, mixed> $value */
        public function add_render_attribute(string $element, string $key, $value = null): void
        {
            $this->attributes[$element][$key] = $value;
        }

        /** @param array<string, mixed> $arguments */
        public function start_controls_section(string $id, array $arguments): void
        {
            $this->controlIds[] = $id;
        }

        public function end_controls_section(): void
        {
        }

        /** @param array<string, mixed> $arguments */
        public function add_control(string $id, array $arguments): void
        {
            $this->controlIds[] = $id;
            $this->controlArguments[$id] = $arguments;
        }

        /** @param array<string, mixed> $arguments */
        public function add_responsive_control(string $id, array $arguments): void
        {
            $this->controlIds[] = $id;
            $this->controlArguments[$id] = $arguments;
        }
    }

    $required_files = [
        TEST_ROOT . '/includes/class-settings.php',
        TEST_ROOT . '/includes/class-assets.php',
        TEST_ROOT . '/includes/class-loop-grid-controls.php',
        TEST_ROOT . '/includes/class-loop-grid-render.php',
        TEST_ROOT . '/includes/class-plugin.php',
    ];

    foreach ($required_files as $required_file) {
        assert_same(is_file($required_file), true, basename($required_file) . ' exists.');
        require_once $required_file;
    }

    use NativeScrollLoop\Assets;
    use NativeScrollLoop\Loop_Grid_Controls;
    use NativeScrollLoop\Loop_Grid_Render;
    use NativeScrollLoop\Plugin;
    use NativeScrollLoop\Settings;

    assert_same(Settings::is_enabled([]), false, 'Carousel is disabled by default.');
    assert_same(Settings::is_enabled(['nsl_enabled' => 'yes']), true, 'Carousel can be enabled.');
    assert_same(
        Settings::is_enabled(['nsl_enabled' => 'yes', 'masonry' => 'yes']),
        false,
        'Masonry prevents carousel activation.'
    );
    assert_same(
        Settings::is_enabled([
            'nsl_enabled' => 'yes',
            'alternate_template' => 'yes',
            'alternate_templates' => [
                ['column_span' => 2],
            ],
        ]),
        false,
        'Multi-column alternate templates prevent carousel activation.'
    );

    $config = Settings::to_frontend_config([
        'nsl_enabled' => 'yes',
        'nsl_snap_enabled' => 'yes',
        'nsl_snap_strictness' => 'invalid',
        'nsl_scroll_behavior' => 'invalid',
        'nsl_arrow_advance' => 'invalid',
        'nsl_autoplay_interval' => 100,
        'nsl_resume_delay' => 999999,
        'nsl_autoplay_end_behavior' => 'invalid',
    ]);

    assert_same($config['enabled'], true, 'Frontend configuration preserves enabled state.');
    assert_same($config['snapEnabled'], true, 'Frontend configuration normalizes switches.');
    assert_same($config['snapStrictness'], 'mandatory', 'Invalid snap strictness uses the safe default.');
    assert_same($config['scrollBehavior'], 'smooth', 'Invalid scroll behavior uses the safe default.');
    assert_same($config['arrowAdvance'], 'item', 'Invalid arrow advance uses the safe default.');
    assert_same($config['autoplayInterval'], 1500, 'Autoplay interval is clamped to its minimum.');
    assert_same($config['resumeDelay'], 60000, 'Resume delay is clamped to its maximum.');
    assert_same($config['autoplayEndBehavior'], 'rewind', 'Invalid autoplay end behavior uses the safe default.');

    $controls_widget = new FakeLoopGrid();
    (new Loop_Grid_Controls())->register($controls_widget);

    foreach ([
        'section_native_scroll_loop',
        'nsl_enabled',
        'nsl_snap_enabled',
        'nsl_show_arrows',
        'nsl_show_progress',
        'nsl_autoplay',
        'nsl_mobile_item_width',
        'section_native_scroll_loop_style',
        'nsl_arrow_size',
        'nsl_progress_height',
    ] as $control_id) {
        assert_same(in_array($control_id, $controls_widget->controlIds, true), true, $control_id . ' is registered.');
    }

    foreach (['nsl_enabled', 'nsl_snap_enabled', 'nsl_show_arrows', 'nsl_arrow_position', 'nsl_show_progress'] as $rerender_control_id) {
        assert_same(
            $controls_widget->controlArguments[$rerender_control_id]['render_type'] ?? '',
            'template',
            $rerender_control_id . ' rerenders the editor preview.'
        );
    }

    $assets = new Assets();
    $renderer = new Loop_Grid_Render($assets);
    $disabled_widget = new FakeLoopGrid();
    assert_same($renderer->filter_content('<div>original</div>', $disabled_widget), '<div>original</div>', 'Disabled widget content is unchanged.');

    $enabled_widget = new FakeLoopGrid([
        'nsl_enabled' => 'yes',
        'nsl_show_arrows' => 'yes',
        'nsl_show_progress' => 'yes',
        'nsl_arrow_position' => 'top-right',
        'nsl_progress_placement' => 'below',
        'nsl_previous_icon' => ['value' => 'eicon-chevron-left', 'library' => 'eicons'],
        'nsl_next_icon' => ['value' => 'eicon-chevron-right', 'library' => 'eicons'],
    ]);

    $renderer->before_render($enabled_widget);
    $rendered = $renderer->filter_content('<div class="elementor-loop-container">original</div>', $enabled_widget);

    assert_same($enabled_widget->attributes['_wrapper']['data-native-scroll-loop'], 'true', 'Enabled wrapper receives its data marker.');
    assert_contains('native-scroll-loop__navigation', $rendered, 'Enabled content receives navigation.');
    assert_contains('native-scroll-loop__progress', $rendered, 'Enabled content receives progress.');
    assert_contains('elementor-loop-container', $rendered, 'Original Loop Grid content is preserved.');
    assert_same(in_array(Assets::SCRIPT_HANDLE, $GLOBALS['nsl_test_enqueued'], true), true, 'Frontend script is enqueued only for enabled widgets.');

    $masonry_widget = new FakeLoopGrid(['nsl_enabled' => 'yes', 'masonry' => 'yes']);
    assert_same($renderer->filter_content('original', $masonry_widget), 'original', 'Masonry content is unchanged.');

    $plugin = new Plugin();
    $plugin->initialize();

    foreach ([
        'elementor/frontend/after_register_scripts',
        'elementor/frontend/after_register_styles',
        'elementor/frontend/after_enqueue_styles',
        'elementor/preview/enqueue_styles',
        'elementor/preview/enqueue_scripts',
        'elementor/element/loop-grid/section_additional_options/after_section_end',
        'elementor/frontend/widget/before_render',
    ] as $hook) {
        assert_same(isset($GLOBALS['nsl_test_hooks']['actions'][$hook]), true, $hook . ' action is registered.');
    }

    assert_same(isset($GLOBALS['nsl_test_hooks']['filters']['elementor/widget/render_content']), true, 'Widget content filter is registered.');
    assert_same(is_file(TEST_ROOT . '/native-scroll-loop.php'), true, 'Plugin bootstrap exists.');
    assert_contains('Version: 1.1.1', (string) file_get_contents(TEST_ROOT . '/native-scroll-loop.php'), 'Plugin version invalidates cached frontend assets.');
    assert_same(is_file(TEST_ROOT . '/uninstall.php'), true, 'Uninstall entry point exists.');

    $stylesheet_file = TEST_ROOT . '/assets/css/native-scroll-loop.css';
    assert_same(is_file($stylesheet_file), true, 'Carousel stylesheet exists.');
    $stylesheet = is_file($stylesheet_file) ? (string) file_get_contents($stylesheet_file) : '';

    foreach ([
        '.elementor-widget-loop-grid.native-scroll-loop .elementor-loop-container.elementor-grid',
        'overflow-x: auto',
        'overscroll-behavior-inline: contain',
        'scroll-snap-type: x mandatory',
        '-webkit-overflow-scrolling: touch',
        '> .e-loop-item',
        'scroll-snap-align: start',
        '.native-scroll-loop--initialized .native-scroll-loop__controls',
        '.native-scroll-loop .native-scroll-loop__arrow:focus',
        '@media (prefers-reduced-motion: reduce)',
    ] as $css_requirement) {
        assert_contains($css_requirement, $stylesheet, $css_requirement . ' is present in the stylesheet.');
    }

    fwrite(STDOUT, "PHP tests passed.\n");
}
