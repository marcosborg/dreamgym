<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Models\LegalTermSection;
use App\Models\Payment;
use App\Services\SiteSettings;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;

class ProductionReadiness extends Command
{
    protected $signature = 'production:readiness {--send-test-email : Send a diagnostic email to the configured sender mailbox}';

    protected $description = 'Report non-sensitive production checks without creating bookings, payments or lock access';

    public function handle(): int
    {
        $report = [
            'checked_at' => now()->toIso8601String(),
            'environment' => app()->environment(),
            'debug' => (bool) config('app.debug'),
            'maintenance' => app(SiteSettings::class)->maintenanceEnabled(),
            'payment_provider' => config('payments.provider'),
            'payment_environment' => config('payments.ifthenpay.env'),
            'lock_provider' => config('lock.provider'),
            'mailer' => config('mail.default'),
            'booking_deadline_migration' => Schema::hasColumn('bookings', 'payment_expires_at'),
            'paid_ifthenpay_count' => Payment::where('provider', 'ifthenpay')->where('status', 'paid')->count(),
            'payments_requiring_review' => Payment::where('metadata->requires_review', true)->count(),
            'future_paid_bookings_without_email' => Booking::where('status', 'confirmed')->where('payment_status', 'paid')->where('starts_at', '>', now())->whereHas('accessCode', fn ($q) => $q->whereNull('access_notified_at'))->count(),
            'legal_sections_mentioning_24_hours' => LegalTermSection::active()->where(fn ($q) => $q->where('body_pt', 'like', '%24 horas%')->orWhere('body_en', 'like', '%24 hours%'))->pluck('id')->all(),
        ];
        if ($this->option('send-test-email')) {
            try {
                Mail::raw('Teste técnico Dream Gym em '.now()->toIso8601String().'. Confirmar a receção antes dos testes de pagamento e reserva.', fn ($message) => $message->to(config('mail.from.address'))->subject('Dream Gym — teste de entrega 2026-09-22'));
                $report['test_email'] = 'accepted_by_transport; inbox delivery still requires confirmation';
            } catch (\Throwable $exception) {
                $report['test_email'] = 'failed: '.get_class($exception);
            }
        }
        $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return self::SUCCESS;
    }
}
