<?php

declare(strict_types=1);

namespace AI_Translation_SEO\Admin;

use AI_Translation_SEO\Plugin;

final class Settings_Page
{
    public function register(): void
    {
        add_action('admin_menu', [$this, 'addMenu']);
        add_action('admin_post_ai_translation_seo_save_settings', [$this, 'save']);
    }

    public function addMenu(): void
    {
        add_options_page(
            __('AI Translation SEO', 'ai-translation-seo'),
            __('AI Translation SEO', 'ai-translation-seo'),
            'manage_options',
            'ai-translation-seo',
            [$this, 'render']
        );
    }

    public function render(): void
    {
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('You are not allowed to access this page.', 'ai-translation-seo'));
        }

        $hardDelete = get_option(Plugin::OPTION_HARD_DELETE, '0') === '1';
        $enMode = (string) get_option(Plugin::OPTION_EN_URL_MODE, Plugin::EN_MODE_SUBDIRECTORY);
        $enSubdirectory = (string) get_option(Plugin::OPTION_EN_SUBDIRECTORY, 'en');
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('AI Translation SEO Settings', 'ai-translation-seo'); ?></h1>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="ai_translation_seo_save_settings">
                <?php wp_nonce_field('ai_translation_seo_save_settings_action', 'ai_translation_seo_nonce'); ?>

                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row">
                            <label for="ai_translation_seo_hard_delete">
                                <?php echo esc_html__('Hard delete on uninstall', 'ai-translation-seo'); ?>
                            </label>
                        </th>
                        <td>
                            <input
                                type="checkbox"
                                id="ai_translation_seo_hard_delete"
                                name="ai_translation_seo_hard_delete"
                                value="1"
                                <?php checked($hardDelete); ?>
                            >
                            <p class="description">
                                <?php echo esc_html__('If enabled, uninstall.php removes plugin tables as well.', 'ai-translation-seo'); ?>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="ai_translation_seo_en_url_mode">
                                <?php echo esc_html__('English URL strategy', 'ai-translation-seo'); ?>
                            </label>
                        </th>
                        <td>
                            <select id="ai_translation_seo_en_url_mode" name="ai_translation_seo_en_url_mode">
                                <option value="subdirectory" <?php selected($enMode, Plugin::EN_MODE_SUBDIRECTORY); ?>>
                                    <?php echo esc_html__('/ oraz /en/ (subdirectory)', 'ai-translation-seo'); ?>
                                </option>
                                <option value="subdomain" <?php selected($enMode, Plugin::EN_MODE_SUBDOMAIN); ?>>
                                    <?php echo esc_html__('en.example.com (subdomain)', 'ai-translation-seo'); ?>
                                </option>
                            </select>
                            <p class="description">
                                <?php echo esc_html__('Najpierw możesz używać subkatalogów / i /en/, a później przełączyć na subdomenę en.', 'ai-translation-seo'); ?>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="ai_translation_seo_en_subdirectory">
                                <?php echo esc_html__('EN subdirectory slug', 'ai-translation-seo'); ?>
                            </label>
                        </th>
                        <td>
                            <input
                                type="text"
                                class="regular-text"
                                id="ai_translation_seo_en_subdirectory"
                                name="ai_translation_seo_en_subdirectory"
                                value="<?php echo esc_attr($enSubdirectory); ?>"
                                placeholder="en"
                            >
                        </td>
                    </tr>
                </table>

                <?php submit_button(__('Save changes', 'ai-translation-seo')); ?>
            </form>
        </div>
        <?php
    }

    public function save(): void
    {
        // Security: capability check for admin-only operation.
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('You are not allowed to perform this action.', 'ai-translation-seo'));
        }

        // Security: nonce verification against CSRF.
        check_admin_referer('ai_translation_seo_save_settings_action', 'ai_translation_seo_nonce');

        $hardDelete = isset($_POST['ai_translation_seo_hard_delete']) ? '1' : '0';
        update_option(Plugin::OPTION_HARD_DELETE, $hardDelete);

        $enModeInput = isset($_POST['ai_translation_seo_en_url_mode'])
            ? sanitize_text_field((string) wp_unslash($_POST['ai_translation_seo_en_url_mode']))
            : Plugin::EN_MODE_SUBDIRECTORY;

        $enMode = in_array($enModeInput, [Plugin::EN_MODE_SUBDIRECTORY, Plugin::EN_MODE_SUBDOMAIN], true)
            ? $enModeInput
            : Plugin::EN_MODE_SUBDIRECTORY;

        $enSubdirectory = isset($_POST['ai_translation_seo_en_subdirectory'])
            ? sanitize_title((string) wp_unslash($_POST['ai_translation_seo_en_subdirectory']))
            : 'en';

        if ($enSubdirectory === '') {
            $enSubdirectory = 'en';
        }

        update_option(Plugin::OPTION_EN_URL_MODE, $enMode);
        update_option(Plugin::OPTION_EN_SUBDIRECTORY, $enSubdirectory);

        wp_safe_redirect(
            add_query_arg(
                [
                    'page' => 'ai-translation-seo',
                    'updated' => 'true',
                ],
                admin_url('options-general.php')
            )
        );
        exit;
    }
}
