<?php

namespace App\Services;

use App\Models\Setting;

class SiteSettings
{
    public const MAINTENANCE_ENABLED = 'maintenance_enabled';

    public const MAINTENANCE_ALLOWED_IPS = 'maintenance_allowed_ips';

    public function maintenanceEnabled(): bool
    {
        return (bool) Setting::getValue(self::MAINTENANCE_ENABLED, false);
    }

    /**
     * @return array<int, string>
     */
    public function maintenanceAllowedIps(): array
    {
        $ips = Setting::getValue(self::MAINTENANCE_ALLOWED_IPS, []);

        if (! is_array($ips)) {
            return [];
        }

        return array_values(array_filter(array_map(
            fn (mixed $ip): string => trim((string) $ip),
            $ips,
        )));
    }

    public function canBypassMaintenance(?string $ip): bool
    {
        if (! $ip) {
            return false;
        }

        return in_array($ip, $this->maintenanceAllowedIps(), true);
    }
}
