<?php

declare(strict_types=1);

namespace AI_Translation_SEO;

final class Deactivator
{
    public static function deactivate(): void
    {
        // Czyści wszystkie zaplanowane wystąpienia hooka.
        wp_clear_scheduled_hook(Plugin::CRON_HOOK);

        // Opcjonalnie flush tylko podczas DEAKTYWACJI.
        flush_rewrite_rules(false);
    }
}
