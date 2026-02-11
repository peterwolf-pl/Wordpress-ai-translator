<?php
/**
 * Uninstall handler for WP-ai-translation-seo.
 */

declare(strict_types=1);

if (! defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

$optionSchemaVersion = 'ai_translation_seo_schema_version';
$optionHardDelete = 'ai_translation_seo_hard_delete';
$optionEnUrlMode = 'ai_translation_seo_en_url_mode';
$optionEnSubdirectory = 'ai_translation_seo_en_subdirectory';
$tableSlug = 'ai_translation_seo_jobs';

$hardDelete = get_option($optionHardDelete, '0') === '1';

$deleteOptions = static function () use (
    $optionSchemaVersion,
    $optionHardDelete,
    $optionEnUrlMode,
    $optionEnSubdirectory
): void {
    delete_option($optionSchemaVersion);
    delete_option($optionHardDelete);
    delete_option($optionEnUrlMode);
    delete_option($optionEnSubdirectory);
};

if (is_multisite()) {
    $siteIds = get_sites([
        'fields' => 'ids',
        'number' => 0,
    ]);

    foreach ($siteIds as $siteId) {
        switch_to_blog((int) $siteId);
        $deleteOptions();

        if ($hardDelete) {
            global $wpdb;
            $tableName = $wpdb->prefix . $tableSlug;
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- table name is controlled internally.
            $wpdb->query("DROP TABLE IF EXISTS {$tableName}");
        }
    }

    restore_current_blog();
} else {
    $deleteOptions();

    if ($hardDelete) {
        global $wpdb;
        $tableName = $wpdb->prefix . $tableSlug;

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- table name is controlled internally.
        $wpdb->query("DROP TABLE IF EXISTS {$tableName}");
    }
}

// Porządek także na poziomie sieci (network options).
delete_site_option($optionSchemaVersion);
delete_site_option($optionHardDelete);
delete_site_option($optionEnUrlMode);
delete_site_option($optionEnSubdirectory);
