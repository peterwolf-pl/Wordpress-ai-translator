<?php

declare(strict_types=1);

namespace AI_Translation_SEO;

final class Activator
{
    public static function activate(): void
    {
        self::installSchema();
        self::setDefaultOptions();
        self::scheduleCron();
        Capabilities_Manager::addCaps();
    }

    private static function installSchema(): void
    {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $tableName = Plugin::tableName();
        $charsetCollate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$tableName} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            source_post_id BIGINT UNSIGNED NOT NULL,
            source_lang VARCHAR(10) NOT NULL,
            target_lang VARCHAR(10) NOT NULL,
            translated_title TEXT NULL,
            translated_content LONGTEXT NULL,
            seo_title TEXT NULL,
            seo_description TEXT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            KEY source_post_id (source_post_id),
            KEY status (status)
        ) {$charsetCollate};";

        dbDelta($sql);

        update_option(Plugin::OPTION_SCHEMA_VERSION, Plugin::SCHEMA_VERSION);
    }

    private static function setDefaultOptions(): void
    {
        $defaults = [
            Plugin::OPTION_HARD_DELETE => '0',
            Plugin::OPTION_CONSENT_EXTERNAL_AI => '0',
            Plugin::OPTION_CONSENT_EXTERNAL_AI_AT => '',
            Plugin::OPTION_DEFAULT_TARGET_LANGUAGE => 'en', // en-GB|en-US|en (nieokreślone)
            Plugin::OPTION_URL_STRATEGY => 'subdir', // subdir|subdomain|domain (nieokreślone)
            Plugin::OPTION_EN_URL_MODE => Plugin::EN_MODE_SUBDIRECTORY,
            Plugin::OPTION_EN_SUBDIRECTORY => 'en',
            Plugin::OPTION_PROVIDER => 'OpenAI',
            Plugin::OPTION_PROVIDER_RATE_LIMIT_MODE => 'balanced',
            Plugin::OPTION_PROVIDER_BATCH_MODE => '0',
            Plugin::OPTION_WORKFLOW_AUTO_TRANSLATE_ON_PUBLISH => '0',
            Plugin::OPTION_WORKFLOW_TRANSLATE_ON_UPDATE => '0',
            Plugin::OPTION_WORKFLOW_ALWAYS_DRAFT => '1',
            Plugin::OPTION_WORKFLOW_SCORE_MIN => 80,
            Plugin::OPTION_WORKFLOW_GLOSSARY_VIOLATIONS_ALLOWED => 0,
        ];

        foreach ($defaults as $key => $value) {
            if (get_option($key, null) === null) {
                add_option($key, $value);
            }
        }
    }

    private static function scheduleCron(): void
    {
        if (! wp_next_scheduled(Plugin::CRON_HOOK)) {
            wp_schedule_event(time() + MINUTE_IN_SECONDS, 'hourly', Plugin::CRON_HOOK);
        }
    }
}
