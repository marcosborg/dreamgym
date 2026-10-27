<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class AuditOctoberSupport extends Command
{
    protected $signature = 'support:audit-october {--output=} {--test-mail}';

    protected $description = 'Read-only checks for October support reports (no customer data changes)';

    public function handle(): int
    {
        $users = User::where(fn ($q) => $q->where('name', 'like', '%<%')->orWhere('name', 'like', '%>%'))->get();
        $result = ['checked_at' => now()->toIso8601String(), 'suspicious_accounts' => $users->map(fn ($u) => [
            'user_id' => $u->id, 'created_at' => $u->created_at->toIso8601String(), 'is_admin' => (bool) $u->is_admin,
            'payments' => Payment::where('user_id', $u->id)->get(['id', 'status', 'product_type', 'amount_cents'])->toArray(),
            'bookings' => Booking::where('user_id', $u->id)->with('accessCode')->get()->map(fn ($b) => ['id' => $b->id, 'status' => $b->status, 'payment_status' => $b->payment_status, 'has_access' => (bool) $b->accessCode])->all(),
        ])->all(), 'cancellations_12_to_24_hours' => Booking::where('status', 'cancelled')->where('paid_with', 'membership')->whereNotNull('cancelled_at')->whereRaw('TIMESTAMPDIFF(SECOND, cancelled_at, starts_at) >= 43200 AND TIMESTAMPDIFF(SECOND, cancelled_at, starts_at) < 86400')->get(['id', 'user_id', 'starts_at', 'cancelled_at'])->toArray()];
        if ($this->option('test-mail')) {
            try {
                Mail::raw('Teste técnico Dream Gym: o envio de avisos de novos registos e reservas está configurado para este endereço. Este teste não cria reservas nem altera créditos.', fn ($m) => $m->to('info@dreamgym.pt')->subject('Dream Gym — teste de notificações 27/10'));
                $result['mail_test'] = 'accepted_by_transport';
            } catch (\Throwable $e) {
                $result['mail_test'] = 'failed';
                $result['mail_error_class'] = get_class($e);
            }
        }
        $json = json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        if ($this->option('output')) {
            file_put_contents($this->option('output'), $json);
        }
        $this->line($json);

        return self::SUCCESS;
    }
}
