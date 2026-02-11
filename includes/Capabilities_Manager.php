<?php

declare(strict_types=1);

namespace AI_Translation_SEO;

final class Capabilities_Manager
{
    public static function addCaps(): void
    {
        $admin = get_role('administrator');
        if ($admin) {
            self::grantAll($admin);
        }

        $editor = get_role('editor');
        if ($editor) {
            $editor->add_cap(Plugin::CAP_TRANSLATE_CONTENT);
            $editor->add_cap(Plugin::CAP_REVIEW_TRANSLATIONS);
        }

        // Author capability is optional / nieokreślone per requirements.
        $authorCanTranslateOwn = (bool) apply_filters('ai_translation_seo_author_can_translate_own', false);
        if ($authorCanTranslateOwn) {
            $author = get_role('author');
            if ($author) {
                $author->add_cap(Plugin::CAP_TRANSLATE_CONTENT);
            }
        }
    }

    public static function removeCaps(): void
    {
        $roles = ['administrator', 'editor', 'author'];
        $caps = [
            Plugin::CAP_MANAGE_SETTINGS,
            Plugin::CAP_TRANSLATE_CONTENT,
            Plugin::CAP_REVIEW_TRANSLATIONS,
            Plugin::CAP_VIEW_LOGS,
            Plugin::CAP_MANAGE_GLOSSARY,
        ];

        foreach ($roles as $roleName) {
            $role = get_role($roleName);
            if (! $role) {
                continue;
            }

            foreach ($caps as $cap) {
                $role->remove_cap($cap);
            }
        }
    }

    private static function grantAll(\WP_Role $role): void
    {
        $role->add_cap(Plugin::CAP_MANAGE_SETTINGS);
        $role->add_cap(Plugin::CAP_TRANSLATE_CONTENT);
        $role->add_cap(Plugin::CAP_REVIEW_TRANSLATIONS);
        $role->add_cap(Plugin::CAP_VIEW_LOGS);
        $role->add_cap(Plugin::CAP_MANAGE_GLOSSARY);
    }
}
