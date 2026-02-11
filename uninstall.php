<?php
/**
 * Uninstall handler for WP-ai-translation-seo.
 */

declare(strict_types=1);

if (! defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

$options = [
    'ai_translation_seo_schema_version',
    'ai_translation_seo_hard_delete',
    'ai_translation_seo_consent_external_ai',
    'ai_translation_seo_consent_external_ai_at',
    'ai_translation_seo_default_target_language',
    'ai_translation_seo_url_strategy',
    'ai_translation_seo_en_url_mode',
    'ai_translation_seo_en_subdirectory',
    'ai_translation_seo_provider',
    'ai_translation_seo_provider_rate_limit_mode',
    'ai_translation_seo_provider_batch_mode',
    'ai_translation_seo_wf_auto_on_publish',
    'ai_translation_seo_wf_translate_on_update',
    'ai_translation_seo_wf_always_draft',
    'ai_translation_seo_wf_score_min',
    'ai_translation_seo_wf_glossary_violations_allowed',
];

$tableSlug = 'ai_translation_seo_jobs';
$hardDelete = get_option('ai_translation_seo_hard_delete', '0') === '1';

$cleanupCurrentSite = static function () use ($options, $hardDelete, $tableSlug): void {
    foreach ($options as $option) {
        delete_option($option);
    }

    if ($hardDelete) {
        global $wpdb;
        $tableName = $wpdb->prefix . $tableSlug;
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- table name is internally controlled.
        $wpdb->query("DROP TABLE IF EXISTS {$tableName}");
    }
};

if (is_multisite()) {
    $siteIds = get_sites(['fields' => 'ids', 'number' => 0]);

    foreach ($siteIds as $siteId) {
        switch_to_blog((int) $siteId);
        $cleanupCurrentSite();
    }

    restore_current_blog();
} else {
    $cleanupCurrentSite();
}

foreach ($options as $option) {
    delete_site_option($option);
}
