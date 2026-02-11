<?php

declare(strict_types=1);

namespace AI_Translation_SEO;

final class Deactivator
{
    public static function deactivate(): void
    {
        $timestamp = wp_next_scheduled(Plugin::CRON_HOOK);

        if ($timestamp !== false) {
            wp_unschedule_event($timestamp, Plugin::CRON_HOOK);
        }

        // Opcjonalnie flush tylko podczas DEAKTYWACJI, nie przy każdym uruchomieniu.
        flush_rewrite_rules(false);
    }
}
