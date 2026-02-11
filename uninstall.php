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
$tableSlug = 'ai_translation_seo_jobs';

$hardDelete = get_option($optionHardDelete, '0') === '1';

delete_option($optionSchemaVersion);
delete_option($optionHardDelete);

delete_site_option($optionSchemaVersion);
delete_site_option($optionHardDelete);

if ($hardDelete) {
    global $wpdb;
    $tableName = $wpdb->prefix . $tableSlug;

    // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- table name is controlled internally.
    $wpdb->query("DROP TABLE IF EXISTS {$tableName}");
}
