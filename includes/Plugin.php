<?php

declare(strict_types=1);

namespace AI_Translation_SEO;

use AI_Translation_SEO\Admin\Settings_Page;

final class Plugin
{
    public const OPTION_SCHEMA_VERSION = 'ai_translation_seo_schema_version';
    public const OPTION_HARD_DELETE = 'ai_translation_seo_hard_delete';
    public const SCHEMA_VERSION = '1.0.0';
    public const CRON_HOOK = 'ai_translation_seo_cron_event';
    public const TABLE_SLUG = 'ai_translation_seo_jobs';

    public function run(): void
    {
        add_action('init', [$this, 'loadTextdomain']);
        add_action(self::CRON_HOOK, [$this, 'handleCron']);

        if (is_admin()) {
            $settingsPage = new Settings_Page();
            $settingsPage->register();
        }
    }

    public function loadTextdomain(): void
    {
        load_plugin_textdomain(
            'ai-translation-seo',
            false,
            dirname(plugin_basename(AI_TRANSLATION_SEO_FILE)) . '/languages'
        );
    }

    /**
     * Placeholder pod zadania cron.
     */
    public function handleCron(): void
    {
        // Tutaj docelowo można umieścić logikę kolejkowania/odświeżania tłumaczeń.
    }

    public static function tableName(): string
    {
        global $wpdb;

        return $wpdb->prefix . self::TABLE_SLUG;
    }
}
