<?php
/**
 * Plugin Name: WP-ai-translation-seo
 * Plugin URI:  https://example.com/wp-ai-translation-seo
 * Description: AI translation helper with SEO-oriented storage and scheduling.
 * Version:     1.1.0
 * Author:      WP AI Translation Team
 * Text Domain: ai-translation-seo
 * Domain Path: /languages
 * Requires PHP: 8.0
 *
 * Jeśli docelowa wersja PHP w środowisku jest nieokreślona,
 * ustaw wymaganie zgodnie z polityką hostingu projektu.
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

const AI_TRANSLATION_SEO_VERSION = '1.1.0';
const AI_TRANSLATION_SEO_FILE = __FILE__;

/**
 * Prosty autoloader PSR-4-like (bez Composera).
 */
spl_autoload_register(static function (string $class): void {
    $prefix = 'AI_Translation_SEO\\';

    if (str_starts_with($class, $prefix) === false) {
        return;
    }

    $relativeClass = substr($class, strlen($prefix));
    $relativePath = str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass) . '.php';
    $file = plugin_dir_path(__FILE__) . 'includes/' . $relativePath;

    if (is_readable($file)) {
        require_once $file;
    }
});

register_activation_hook(__FILE__, ['AI_Translation_SEO\Activator', 'activate']);
register_deactivation_hook(__FILE__, ['AI_Translation_SEO\Deactivator', 'deactivate']);

add_action('plugins_loaded', static function (): void {
    $plugin = new AI_Translation_SEO\Plugin();
    $plugin->run();
});
