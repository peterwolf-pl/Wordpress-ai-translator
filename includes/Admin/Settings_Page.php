<?php

declare(strict_types=1);

namespace AI_Translation_SEO\Admin;

use AI_Translation_SEO\Plugin;

final class Settings_Page
{
    private const PAGE_SLUG = 'ai-translation-seo';
    private const OPTION_GROUP = 'ai_translation_seo_settings';

    public function register(): void
    {
        add_action('admin_menu', [$this, 'addMenu']);
        add_action('admin_init', [$this, 'registerSettings']);
    }

    public function addMenu(): void
    {
        add_menu_page(
            __('AI Translation SEO', 'ai-translation-seo'),
            __('AI Translation SEO', 'ai-translation-seo'),
            Plugin::CAP_MANAGE_SETTINGS,
            self::PAGE_SLUG,
            [$this, 'renderMainPanel'],
            'dashicons-translation',
            58
        );

        $tabs = [
            'general' => __('General', 'ai-translation-seo'),
            'languages' => __('Languages', 'ai-translation-seo'),
            'providers' => __('Providers', 'ai-translation-seo'),
            'workflow' => __('Workflow', 'ai-translation-seo'),
            'glossary' => __('Glossary', 'ai-translation-seo'),
            'tm' => __('Translation Memory', 'ai-translation-seo'),
            'jobs' => __('Jobs', 'ai-translation-seo'),
            'seo' => __('SEO', 'ai-translation-seo'),
            'logs' => __('Logs', 'ai-translation-seo'),
            'billing' => __('Billing', 'ai-translation-seo'),
        ];

        foreach ($tabs as $tab => $label) {
            add_submenu_page(
                self::PAGE_SLUG,
                $label,
                $label,
                Plugin::CAP_MANAGE_SETTINGS,
                self::PAGE_SLUG . '-' . $tab,
                function () use ($tab): void {
                    $this->renderMainPanel($tab);
                }
            );
        }
    }

    public function registerSettings(): void
    {
        register_setting(self::OPTION_GROUP, Plugin::OPTION_DEFAULT_TARGET_LANGUAGE, [$this, 'sanitizeTargetLanguage']);
        register_setting(self::OPTION_GROUP, Plugin::OPTION_URL_STRATEGY, [$this, 'sanitizeUrlStrategy']);
        register_setting(self::OPTION_GROUP, Plugin::OPTION_HARD_DELETE, [$this, 'sanitizeCheckbox']);
        register_setting(self::OPTION_GROUP, Plugin::OPTION_CONSENT_EXTERNAL_AI, [$this, 'sanitizeConsent']);
        register_setting(self::OPTION_GROUP, Plugin::OPTION_CONSENT_EXTERNAL_AI_AT, [$this, 'sanitizeConsentTimestamp']);

        register_setting(self::OPTION_GROUP, Plugin::OPTION_PROVIDER, [$this, 'sanitizeProvider']);
        register_setting(self::OPTION_GROUP, Plugin::OPTION_PROVIDER_RATE_LIMIT_MODE, [$this, 'sanitizeRateLimitMode']);
        register_setting(self::OPTION_GROUP, Plugin::OPTION_PROVIDER_BATCH_MODE, [$this, 'sanitizeCheckbox']);

        register_setting(self::OPTION_GROUP, Plugin::OPTION_WORKFLOW_AUTO_TRANSLATE_ON_PUBLISH, [$this, 'sanitizeCheckbox']);
        register_setting(self::OPTION_GROUP, Plugin::OPTION_WORKFLOW_TRANSLATE_ON_UPDATE, [$this, 'sanitizeCheckbox']);
        register_setting(self::OPTION_GROUP, Plugin::OPTION_WORKFLOW_ALWAYS_DRAFT, [$this, 'sanitizeCheckbox']);
        register_setting(self::OPTION_GROUP, Plugin::OPTION_WORKFLOW_SCORE_MIN, [$this, 'sanitizeScore']);
        register_setting(self::OPTION_GROUP, Plugin::OPTION_WORKFLOW_GLOSSARY_VIOLATIONS_ALLOWED, [$this, 'sanitizeViolationsAllowed']);

        add_settings_section('ait_general', __('General', 'ai-translation-seo'), null, self::PAGE_SLUG . '_general');
        add_settings_field('default_source_language', __('Default source language', 'ai-translation-seo'), [$this, 'fieldSourceLanguage'], self::PAGE_SLUG . '_general', 'ait_general');
        add_settings_field('default_target_language', __('Default target language', 'ai-translation-seo'), [$this, 'fieldTargetLanguage'], self::PAGE_SLUG . '_general', 'ait_general');
        add_settings_field('url_strategy', __('URL strategy', 'ai-translation-seo'), [$this, 'fieldUrlStrategy'], self::PAGE_SLUG . '_general', 'ait_general');
        add_settings_field('consent_external', __('Consent to contact external AI services', 'ai-translation-seo'), [$this, 'fieldConsent'], self::PAGE_SLUG . '_general', 'ait_general');
        add_settings_field('hard_delete', __('Hard delete on uninstall', 'ai-translation-seo'), [$this, 'fieldHardDelete'], self::PAGE_SLUG . '_general', 'ait_general');

        add_settings_section('ait_providers', __('Providers', 'ai-translation-seo'), null, self::PAGE_SLUG . '_providers');
        add_settings_field('provider', __('Provider', 'ai-translation-seo'), [$this, 'fieldProvider'], self::PAGE_SLUG . '_providers', 'ait_providers');
        add_settings_field('credentials', __('API credentials', 'ai-translation-seo'), [$this, 'fieldCredentials'], self::PAGE_SLUG . '_providers', 'ait_providers');
        add_settings_field('rate_limit_mode', __('Rate limit mode', 'ai-translation-seo'), [$this, 'fieldRateLimit'], self::PAGE_SLUG . '_providers', 'ait_providers');
        add_settings_field('batch_mode', __('Batch mode', 'ai-translation-seo'), [$this, 'fieldBatchMode'], self::PAGE_SLUG . '_providers', 'ait_providers');

        add_settings_section('ait_workflow', __('Workflow', 'ai-translation-seo'), null, self::PAGE_SLUG . '_workflow');
        add_settings_field('auto_publish', __('Auto-translate on publish', 'ai-translation-seo'), [$this, 'fieldWfAutoPublish'], self::PAGE_SLUG . '_workflow', 'ait_workflow');
        add_settings_field('on_update', __('Translate on update', 'ai-translation-seo'), [$this, 'fieldWfOnUpdate'], self::PAGE_SLUG . '_workflow', 'ait_workflow');
        add_settings_field('always_draft', __('Always create as draft', 'ai-translation-seo'), [$this, 'fieldWfAlwaysDraft'], self::PAGE_SLUG . '_workflow', 'ait_workflow');
        add_settings_field('score_min', __('score_total minimum', 'ai-translation-seo'), [$this, 'fieldWfScoreMin'], self::PAGE_SLUG . '_workflow', 'ait_workflow');
        add_settings_field('glossary_violations', __('glossary violations allowed', 'ai-translation-seo'), [$this, 'fieldWfViolations'], self::PAGE_SLUG . '_workflow', 'ait_workflow');
    }

    public function renderMainPanel(?string $forcedTab = null): void
    {
        if (! current_user_can(Plugin::CAP_MANAGE_SETTINGS)) {
            wp_die(esc_html__('You are not allowed to access this page.', 'ai-translation-seo'));
        }

        $tab = $forcedTab ?? $this->resolveTabFromRequest();

        echo '<div class="wrap"><h1>' . esc_html__('AI Translation SEO — Control Panel', 'ai-translation-seo') . '</h1>';
        $this->renderTabs($tab);

        if ($tab === 'dashboard') {
            $this->renderDashboard();
            echo '</div>';

            return;
        }

        echo '<form method="post" action="options.php">';
        settings_fields(self::OPTION_GROUP);

        if ($tab === 'general') {
            do_settings_sections(self::PAGE_SLUG . '_general');
        } elseif ($tab === 'providers') {
            do_settings_sections(self::PAGE_SLUG . '_providers');
        } elseif ($tab === 'workflow') {
            do_settings_sections(self::PAGE_SLUG . '_workflow');
        } else {
            echo '<p>' . esc_html__('Module panel ready. Detailed implementation for this tab is pending.', 'ai-translation-seo') . '</p>';
            $this->renderPlaceholderSpec($tab);
        }

        submit_button(__('Save changes', 'ai-translation-seo'));
        echo '</form></div>';
    }

    private function resolveTabFromRequest(): string
    {
        $page = isset($_GET['page']) ? sanitize_key((string) wp_unslash($_GET['page'])) : self::PAGE_SLUG;
        if (str_starts_with($page, self::PAGE_SLUG . '-')) {
            $tab = substr($page, strlen(self::PAGE_SLUG) + 1);
        } else {
            $tab = isset($_GET['tab']) ? sanitize_key((string) wp_unslash($_GET['tab'])) : 'dashboard';
        }

        $allowed = ['dashboard', 'general', 'languages', 'providers', 'workflow', 'glossary', 'tm', 'jobs', 'seo', 'logs', 'billing'];

        return in_array($tab, $allowed, true) ? $tab : 'dashboard';
    }

    private function renderTabs(string $activeTab): void
    {
        $tabs = [
            'dashboard' => 'Dashboard',
            'general' => 'General',
            'languages' => 'Languages',
            'providers' => 'Providers',
            'workflow' => 'Workflow',
            'glossary' => 'Glossary',
            'tm' => 'Translation Memory',
            'jobs' => 'Jobs',
            'seo' => 'SEO',
            'logs' => 'Logs',
            'billing' => 'Billing',
        ];

        echo '<nav class="nav-tab-wrapper" style="margin-bottom:16px">';
        foreach ($tabs as $tab => $label) {
            $class = $activeTab === $tab ? ' nav-tab-active' : '';
            $url = $tab === 'dashboard'
                ? admin_url('admin.php?page=' . self::PAGE_SLUG)
                : admin_url('admin.php?page=' . self::PAGE_SLUG . '-' . $tab);
            echo '<a href="' . esc_url($url) . '" class="nav-tab' . esc_attr($class) . '">' . esc_html($label) . '</a>';
        }
        echo '</nav>';
    }

    private function renderDashboard(): void
    {
        echo '<div style="display:grid;grid-template-columns:repeat(3,minmax(220px,1fr));gap:12px;max-width:1100px">';
        $cards = [
            __('General setup', 'ai-translation-seo') => __('Language defaults, URL strategy, consent, uninstall policy.', 'ai-translation-seo'),
            __('Providers', 'ai-translation-seo') => __('Provider selection, credentials, rate limits, batch mode.', 'ai-translation-seo'),
            __('Workflow', 'ai-translation-seo') => __('Automation flags and QA thresholds.', 'ai-translation-seo'),
            __('Glossary & TM', 'ai-translation-seo') => __('Term base and translation memory module.', 'ai-translation-seo'),
            __('Jobs', 'ai-translation-seo') => __('Batch queue, statuses and retries.', 'ai-translation-seo'),
            __('SEO, Logs, Billing', 'ai-translation-seo') => __('Metadata quality, audit trail and usage costs.', 'ai-translation-seo'),
        ];

        foreach ($cards as $title => $description) {
            echo '<div style="background:#fff;border:1px solid #ccd0d4;padding:14px">';
            echo '<h2 style="margin:0 0 8px 0;font-size:16px">' . esc_html($title) . '</h2>';
            echo '<p style="margin:0">' . esc_html($description) . '</p>';
            echo '</div>';
        }

        echo '</div>';
    }

    private function renderPlaceholderSpec(string $tab): void
    {
        $copy = [
            'languages' => __('Expected: source/target locale matrix and language-level routing.', 'ai-translation-seo'),
            'glossary' => __('Expected: term base CRUD and consistency validation.', 'ai-translation-seo'),
            'tm' => __('Expected: translation memory segment import/export and match score.', 'ai-translation-seo'),
            'jobs' => __('Expected: queue monitor, batch trigger and status transitions.', 'ai-translation-seo'),
            'seo' => __('Expected: per-language SEO templates and metadata checks.', 'ai-translation-seo'),
            'logs' => __('Expected: audit log with capability-gated read access.', 'ai-translation-seo'),
            'billing' => __('Expected: provider cost tracking and consumption limits.', 'ai-translation-seo'),
        ];

        if (isset($copy[$tab])) {
            echo '<p class="description">' . esc_html($copy[$tab]) . '</p>';
        }
    }

    public function fieldSourceLanguage(): void
    {
        echo '<input type="text" class="regular-text" value="pl-PL" readonly />';
    }

    public function fieldTargetLanguage(): void
    {
        $value = (string) get_option(Plugin::OPTION_DEFAULT_TARGET_LANGUAGE, 'en');
        echo '<select name="' . esc_attr(Plugin::OPTION_DEFAULT_TARGET_LANGUAGE) . '">';
        foreach (['en-GB', 'en-US', 'en'] as $lang) {
            echo '<option value="' . esc_attr($lang) . '" ' . selected($value, $lang, false) . '>' . esc_html($lang) . '</option>';
        }
        echo '</select>';
    }

    public function fieldUrlStrategy(): void
    {
        $value = (string) get_option(Plugin::OPTION_URL_STRATEGY, 'subdir');
        echo '<select name="' . esc_attr(Plugin::OPTION_URL_STRATEGY) . '">';
        foreach (['subdir', 'subdomain', 'domain'] as $strategy) {
            echo '<option value="' . esc_attr($strategy) . '" ' . selected($value, $strategy, false) . '>' . esc_html($strategy) . '</option>';
        }
        echo '</select>';
    }

    public function fieldConsent(): void
    {
        $consent = get_option(Plugin::OPTION_CONSENT_EXTERNAL_AI, '0') === '1';
        $timestamp = (string) get_option(Plugin::OPTION_CONSENT_EXTERNAL_AI_AT, '');
        echo '<label><input type="checkbox" name="' . esc_attr(Plugin::OPTION_CONSENT_EXTERNAL_AI) . '" value="1" ' . checked($consent, true, false) . ' /> ';
        echo esc_html__('I consent to contact external AI services.', 'ai-translation-seo') . '</label>';
        echo '<p class="description">' . esc_html__('Timestamp:', 'ai-translation-seo') . ' ' . esc_html($timestamp !== '' ? $timestamp : '—') . '</p>';
    }

    public function fieldHardDelete(): void
    {
        $hardDelete = get_option(Plugin::OPTION_HARD_DELETE, '0') === '1';
        echo '<label><input type="checkbox" name="' . esc_attr(Plugin::OPTION_HARD_DELETE) . '" value="1" ' . checked($hardDelete, true, false) . ' /> ' . esc_html__('Enable', 'ai-translation-seo') . '</label>';
    }

    public function fieldProvider(): void
    {
        $provider = (string) get_option(Plugin::OPTION_PROVIDER, 'OpenAI');
        $providers = ['OpenAI', 'DeepL', 'Google', 'AWS', 'Azure'];
        echo '<select name="' . esc_attr(Plugin::OPTION_PROVIDER) . '">';
        foreach ($providers as $item) {
            echo '<option value="' . esc_attr($item) . '" ' . selected($provider, $item, false) . '>' . esc_html($item) . '</option>';
        }
        echo '</select>';
    }

    public function fieldCredentials(): void
    {
        echo '<input type="password" class="regular-text" value="********" readonly /> ';
        echo '<button type="button" class="button">' . esc_html__('Test connection', 'ai-translation-seo') . '</button>';
    }

    public function fieldRateLimit(): void
    {
        $value = (string) get_option(Plugin::OPTION_PROVIDER_RATE_LIMIT_MODE, 'balanced');
        echo '<select name="' . esc_attr(Plugin::OPTION_PROVIDER_RATE_LIMIT_MODE) . '">';
        foreach (['conservative', 'balanced', 'aggressive'] as $mode) {
            echo '<option value="' . esc_attr($mode) . '" ' . selected($value, $mode, false) . '>' . esc_html($mode) . '</option>';
        }
        echo '</select>';
    }

    public function fieldBatchMode(): void
    {
        $enabled = get_option(Plugin::OPTION_PROVIDER_BATCH_MODE, '0') === '1';
        echo '<label><input type="checkbox" name="' . esc_attr(Plugin::OPTION_PROVIDER_BATCH_MODE) . '" value="1" ' . checked($enabled, true, false) . ' /> ' . esc_html__('Enabled', 'ai-translation-seo') . '</label>';
    }

    public function fieldWfAutoPublish(): void
    {
        $enabled = get_option(Plugin::OPTION_WORKFLOW_AUTO_TRANSLATE_ON_PUBLISH, '0') === '1';
        echo '<input type="checkbox" name="' . esc_attr(Plugin::OPTION_WORKFLOW_AUTO_TRANSLATE_ON_PUBLISH) . '" value="1" ' . checked($enabled, true, false) . ' />';
    }

    public function fieldWfOnUpdate(): void
    {
        $enabled = get_option(Plugin::OPTION_WORKFLOW_TRANSLATE_ON_UPDATE, '0') === '1';
        echo '<input type="checkbox" name="' . esc_attr(Plugin::OPTION_WORKFLOW_TRANSLATE_ON_UPDATE) . '" value="1" ' . checked($enabled, true, false) . ' />';
    }

    public function fieldWfAlwaysDraft(): void
    {
        $enabled = get_option(Plugin::OPTION_WORKFLOW_ALWAYS_DRAFT, '1') === '1';
        echo '<input type="checkbox" name="' . esc_attr(Plugin::OPTION_WORKFLOW_ALWAYS_DRAFT) . '" value="1" ' . checked($enabled, true, false) . ' />';
    }

    public function fieldWfScoreMin(): void
    {
        $value = (int) get_option(Plugin::OPTION_WORKFLOW_SCORE_MIN, 80);
        echo '<input type="number" min="0" max="100" name="' . esc_attr(Plugin::OPTION_WORKFLOW_SCORE_MIN) . '" value="' . esc_attr((string) $value) . '" />';
    }

    public function fieldWfViolations(): void
    {
        $value = (int) get_option(Plugin::OPTION_WORKFLOW_GLOSSARY_VIOLATIONS_ALLOWED, 0);
        echo '<input type="number" min="0" name="' . esc_attr(Plugin::OPTION_WORKFLOW_GLOSSARY_VIOLATIONS_ALLOWED) . '" value="' . esc_attr((string) $value) . '" />';
    }

    public function sanitizeCheckbox(mixed $value): string
    {
        return $value === '1' || $value === 1 ? '1' : '0';
    }

    public function sanitizeConsent(mixed $value): string
    {
        $consent = $this->sanitizeCheckbox($value);
        if ($consent === '1') {
            update_option(Plugin::OPTION_CONSENT_EXTERNAL_AI_AT, gmdate('c'));
        }

        return $consent;
    }

    public function sanitizeConsentTimestamp(mixed $value): string
    {
        return sanitize_text_field((string) $value);
    }

    public function sanitizeTargetLanguage(mixed $value): string
    {
        $allowed = ['en-GB', 'en-US', 'en'];
        $value = sanitize_text_field((string) $value);

        return in_array($value, $allowed, true) ? $value : 'en';
    }

    public function sanitizeUrlStrategy(mixed $value): string
    {
        $allowed = ['subdir', 'subdomain', 'domain'];
        $value = sanitize_text_field((string) $value);

        return in_array($value, $allowed, true) ? $value : 'subdir';
    }

    public function sanitizeProvider(mixed $value): string
    {
        $allowed = ['OpenAI', 'DeepL', 'Google', 'AWS', 'Azure'];
        $value = sanitize_text_field((string) $value);

        return in_array($value, $allowed, true) ? $value : 'OpenAI';
    }

    public function sanitizeRateLimitMode(mixed $value): string
    {
        $allowed = ['conservative', 'balanced', 'aggressive'];
        $value = sanitize_text_field((string) $value);

        return in_array($value, $allowed, true) ? $value : 'balanced';
    }

    public function sanitizeScore(mixed $value): int
    {
        return max(0, min(100, (int) $value));
    }

    public function sanitizeViolationsAllowed(mixed $value): int
    {
        return max(0, (int) $value);
    }
}
