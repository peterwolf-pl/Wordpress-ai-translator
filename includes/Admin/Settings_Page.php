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
