<?php

declare(strict_types=1);

namespace AI_Translation_SEO;

use AI_Translation_SEO\Admin\Settings_Page;

final class Plugin
{
    public const OPTION_SCHEMA_VERSION = 'ai_translation_seo_schema_version';
    public const OPTION_HARD_DELETE = 'ai_translation_seo_hard_delete';
    public const OPTION_CONSENT_EXTERNAL_AI = 'ai_translation_seo_consent_external_ai';
    public const OPTION_CONSENT_EXTERNAL_AI_AT = 'ai_translation_seo_consent_external_ai_at';
    public const OPTION_DEFAULT_TARGET_LANGUAGE = 'ai_translation_seo_default_target_language';
    public const OPTION_URL_STRATEGY = 'ai_translation_seo_url_strategy';
    public const OPTION_EN_URL_MODE = 'ai_translation_seo_en_url_mode';
    public const OPTION_EN_SUBDIRECTORY = 'ai_translation_seo_en_subdirectory';

    public const OPTION_PROVIDER = 'ai_translation_seo_provider';
    public const OPTION_PROVIDER_RATE_LIMIT_MODE = 'ai_translation_seo_provider_rate_limit_mode';
    public const OPTION_PROVIDER_BATCH_MODE = 'ai_translation_seo_provider_batch_mode';

    public const OPTION_WORKFLOW_AUTO_TRANSLATE_ON_PUBLISH = 'ai_translation_seo_wf_auto_on_publish';
    public const OPTION_WORKFLOW_TRANSLATE_ON_UPDATE = 'ai_translation_seo_wf_translate_on_update';
    public const OPTION_WORKFLOW_ALWAYS_DRAFT = 'ai_translation_seo_wf_always_draft';
    public const OPTION_WORKFLOW_SCORE_MIN = 'ai_translation_seo_wf_score_min';
    public const OPTION_WORKFLOW_GLOSSARY_VIOLATIONS_ALLOWED = 'ai_translation_seo_wf_glossary_violations_allowed';

    public const SCHEMA_VERSION = '1.1.0';
    public const CRON_HOOK = 'ai_translation_seo_cron_event';
    public const TABLE_SLUG = 'ai_translation_seo_jobs';

    public const EN_MODE_SUBDIRECTORY = 'subdirectory';
    public const EN_MODE_SUBDOMAIN = 'subdomain';

    public const CAP_MANAGE_SETTINGS = 'ait_manage_settings';
    public const CAP_TRANSLATE_CONTENT = 'ait_translate_content';
    public const CAP_REVIEW_TRANSLATIONS = 'ait_review_translations';
    public const CAP_VIEW_LOGS = 'ait_view_logs';
    public const CAP_MANAGE_GLOSSARY = 'ait_manage_glossary';

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

    public function handleCron(): void
    {
        // Placeholder for async translation queue processing.
    }

    public static function tableName(): string
    {
        global $wpdb;

        return $wpdb->prefix . self::TABLE_SLUG;
    }
}
