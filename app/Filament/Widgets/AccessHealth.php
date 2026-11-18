<?php

namespace App\Filament\Widgets;

use App\Models\AccessCode;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

class AccessHealth extends StatsOverviewWidget
{
    protected static ?int $sort = -1;

    public static function canView(): bool
    {
        return config('lock.provider') === 'ttlock';
    }

    protected function getStats(): array
    {
        $pending = AccessCode::where('valid_until', '>', now())->whereNull('revoked_at')
            ->whereHas('booking', fn ($q) => $q->where('status', 'confirmed')->where('payment_status', 'paid'))
            ->where(fn ($q) => $q->where('provision_status', '!=', AccessCode::PROVISIONED)
                ->orWhereNull('ttlock_passcode_id')->orWhereNull('access_notified_at'))->count();

        $lastSync = Cache::get('ttlock-last-sync');

        return [Stat::make('Acessos ou emails por concluir', $pending)
            ->description($pending ? 'Verifique as falhas em Access Codes. Se o gateway estiver offline, confirme alimentação e internet.' : 'Sem pendências nas reservas válidas.')
            ->color($pending ? 'danger' : 'success')->url('/admin/access-codes'),
            Stat::make('Última sincronização automática', $lastSync ? Carbon::parse($lastSync)->format('H:i:s') : 'Por executar')
                ->description($lastSync ? Carbon::parse($lastSync)->format('d/m/Y') : 'Ainda não registada')];
    }
}
