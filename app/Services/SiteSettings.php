<?php

namespace App\Services;

use App\Models\Setting;

class SiteSettings
{
    public const MAINTENANCE_ENABLED = 'maintenance_enabled';

    public const MAINTENANCE_ALLOWED_IPS = 'maintenance_allowed_ips';

    public const EQUIPMENT_GROUPS = 'equipment_groups';

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

    public function equipmentGroups(): array
    {
        return Setting::getValue(self::EQUIPMENT_GROUPS, [
            [
                'title_pt' => 'Functional',
                'title_en' => 'Functional',
                'items' => [
                    ['name_pt' => 'Espaldar e acessórios', 'name_en' => 'Wall bars and accessories'],
                    ['name_pt' => 'Bandas e acessórios', 'name_en' => 'Bands and accessories'],
                    ['name_pt' => 'Wall Ball / Medicine Balls', 'name_en' => 'Wall Ball / Medicine Balls'],
                    ['name_pt' => 'Kettlebells', 'name_en' => 'Kettlebells'],
                    ['name_pt' => 'Sled, discos e Battle Rope', 'name_en' => 'Sled, plates and Battle Rope'],
                    ['name_pt' => 'Caixa pliométrica', 'name_en' => 'Plyometric box'],
                ],
            ],
            [
                'title_pt' => 'Força',
                'title_en' => 'Strength',
                'items' => [
                    ['name_pt' => 'Smith Machine', 'name_en' => 'Smith Machine'],
                    ['name_pt' => 'Máquina funcional / Polia dupla', 'name_en' => 'Functional trainer / Dual pulley'],
                    ['name_pt' => '2 bancos reguláveis', 'name_en' => '2 adjustable benches'],
                    ['name_pt' => 'Rack para 12 pares de halteres', 'name_en' => 'Rack with 12 pairs of dumbbells'],
                    ['name_pt' => 'Halteres de 2,5 kg a 30 kg', 'name_en' => 'Dumbbells from 2.5 kg to 30 kg'],
                    ['name_pt' => 'Discos olímpicos de uretano', 'name_en' => 'Urethane Olympic plates'],
                ],
            ],
            [
                'title_pt' => 'Cardio',
                'title_en' => 'Cardio',
                'items' => [
                    ['name_pt' => 'Passadeira', 'name_en' => 'Treadmill'],
                    ['name_pt' => 'Elíptica', 'name_en' => 'Elliptical'],
                    ['name_pt' => 'Bicicleta', 'name_en' => 'Exercise bike'],
                ],
            ],
        ]);
    }
}
