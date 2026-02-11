<?php

declare(strict_types=1);

namespace AI_Translation_SEO;

final class Activator
{
    public static function activate(): bool
    {
        self::installSchema();
        self::setDefaultOptions();
        self::scheduleCron();

        return true;
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
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY source_post_id (source_post_id),
            KEY status (status)
        ) {$charsetCollate};";

        dbDelta($sql);

        update_option(Plugin::OPTION_SCHEMA_VERSION, Plugin::SCHEMA_VERSION);
    }

    private static function setDefaultOptions(): void
    {
        if (get_option(Plugin::OPTION_HARD_DELETE, null) === null) {
            add_option(Plugin::OPTION_HARD_DELETE, '0');
        }
    }

    private static function scheduleCron(): void
    {
        if (! wp_next_scheduled(Plugin::CRON_HOOK)) {
            wp_schedule_event(time() + MINUTE_IN_SECONDS, 'hourly', Plugin::CRON_HOOK);
        }
    }
}
