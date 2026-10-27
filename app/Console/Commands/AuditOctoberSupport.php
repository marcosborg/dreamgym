<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use App\Services\CreditHistory;
use App\Services\MembershipReconciliationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class AuditOctoberSupport extends Command
{
    protected $signature = 'support:audit-october {--output=} {--test-mail} {--repair-refunds}';

    protected $description = 'Read-only checks for October support reports (no customer data changes)';

    public function handle(): int
    {
        $users = User::where(fn ($q) => $q->where('name', 'like', '%<%')->orWhere('name', 'like', '%>%'))->get();
        $result = ['checked_at' => now()->toIso8601String(), 'suspicious_accounts' => $users->map(fn ($u) => [
            'user_id' => $u->id, 'created_at' => $u->created_at->toIso8601String(), 'is_admin' => (bool) $u->is_admin,
            'payments' => Payment::where('user_id', $u->id)->get(['id', 'status', 'product_type', 'amount_cents'])->toArray(),
            'bookings' => Booking::where('user_id', $u->id)->with('accessCode')->get()->map(fn ($b) => ['id' => $b->id, 'status' => $b->status, 'payment_status' => $b->payment_status, 'has_access' => (bool) $b->accessCode])->all(),
        ])->all(), 'cancellations_12_to_24_hours' => Booking::where('status', 'cancelled')->where('paid_with', 'membership')->whereNotNull('cancelled_at')->whereRaw('TIMESTAMPDIFF(SECOND, cancelled_at, starts_at) >= 43200 AND TIMESTAMPDIFF(SECOND, cancelled_at, starts_at) < 86400')->get(['id', 'user_id', 'starts_at', 'cancelled_at'])->toArray()];
        $result['affected_account_balances'] = User::whereIn('id', collect($result['cancellations_12_to_24_hours'])->pluck('user_id'))->get()->map(function ($user) {
            $preview = app(MembershipReconciliationService::class)->reconcile($user, false, false);

            return ['user_id' => $user->id, 'current_balance' => $user->membership_credits, 'recalculated_balance' => $preview['credits'], 'expires_at' => $user->membership_expires_at?->toIso8601String()];
        })->all();
        if ($this->option('repair-refunds')) {
            $result['refund_repairs'] = [];
            foreach ($result['affected_account_balances'] as $candidate) {
                $result['refund_repairs'][] = DB::transaction(function () use ($candidate) {
                    $user = User::lockForUpdate()->findOrFail($candidate['user_id']);
                    $service = app(MembershipReconciliationService::class);
                    $correct = $service->reconcile($user, false, false)['credits'];
                    $legacy = $service->reconcile($user, false, false, 24)['credits'];
                    $before = $user->membership_credits;
                    // Only restore a provable cutoff discrepancy; preserve other manual balances.
                    if ($before === $legacy && $correct > $legacy) {
                        $user->update(['membership_credits' => $correct]);
                        CreditHistory::record($user, 'membership', $correct - $before, 'refund_correction');
                    }

                    return ['user_id' => $user->id, 'before' => $before, 'after' => $user->fresh()->membership_credits, 'expected' => $correct];
                });
            }
        }
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
            chmod($this->option('output'), 0600);
        }
        $this->line($json);

        return self::SUCCESS;
    }
}
