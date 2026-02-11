<?php

declare(strict_types=1);

namespace AI_Translation_SEO;

final class Deactivator
{
    public static function deactivate(): void
    {
        wp_clear_scheduled_hook(Plugin::CRON_HOOK);
        Capabilities_Manager::removeCaps();

        // Optional flush only on deactivation.
        flush_rewrite_rules(false);
    }
}
