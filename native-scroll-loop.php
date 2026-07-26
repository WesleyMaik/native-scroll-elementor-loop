<?php
/**
 * Plugin Name: Native Scroll Loop
 * Description: Adds an optional native horizontal carousel mode to Elementor Pro Loop Grid.
 * Version: 1.0.0
 * Requires at least: 6.6
 * Requires PHP: 7.4
 * Requires Plugins: elementor
 * Author: Wesley Maik
 * Text Domain: native-scroll-loop
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

define('NATIVE_SCROLL_LOOP_VERSION', '1.0.0');
define('NATIVE_SCROLL_LOOP_FILE', __FILE__);
define('NATIVE_SCROLL_LOOP_PATH', plugin_dir_path(__FILE__));
define('NATIVE_SCROLL_LOOP_URL', plugin_dir_url(__FILE__));

require_once NATIVE_SCROLL_LOOP_PATH . 'includes/class-settings.php';
require_once NATIVE_SCROLL_LOOP_PATH . 'includes/class-assets.php';
require_once NATIVE_SCROLL_LOOP_PATH . 'includes/class-loop-grid-controls.php';
require_once NATIVE_SCROLL_LOOP_PATH . 'includes/class-loop-grid-render.php';
require_once NATIVE_SCROLL_LOOP_PATH . 'includes/class-plugin.php';

add_action(
    'plugins_loaded',
    static function (): void {
        load_plugin_textdomain('native-scroll-loop', false, dirname(plugin_basename(NATIVE_SCROLL_LOOP_FILE)) . '/languages');
        \NativeScrollLoop\Plugin::instance()->boot();
    },
    20
);
