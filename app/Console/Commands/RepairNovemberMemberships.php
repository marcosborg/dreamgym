<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\MembershipReconciliationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RepairNovemberMemberships extends Command
{
    protected $signature = 'support:repair-november {--apply} {--output=}';

    protected $description = 'Audit and remove verified membership carry-over without overwriting unexplained balances';

    public function handle(MembershipReconciliationService $service): int
    {
        $rows = [];
        User::whereHas('payments', fn ($q) => $q->whereNull('booking_id')->where('product_type', 'membership')->where('status', 'paid'))
            ->eachById(function ($user) use ($service, &$rows) {
                $rows[] = DB::transaction(function () use ($user, $service) {
                    $user = User::lockForUpdate()->findOrFail($user->id);
                    $old = $service->reconcile($user, false, false, 12, false);
                    $new = $service->reconcile($user, false, false);
                    $row = ['user_id' => $user->id, 'before' => $user->membership_credits, 'before_expires_at' => $user->membership_expires_at?->toIso8601String(),
                        'legacy' => $old['credits'], 'corrected' => $new['credits'], 'uncovered' => $new['uncovered'], 'status' => 'unchanged'];
                    if ($user->membership_credits === $old['credits']) {
                        $row['status'] = $this->option('apply') ? 'applied' : 'preview';
                        if ($this->option('apply')) {
                            $service->reconcile($user);
                            $row['after_expires_at'] = $user->fresh()->membership_expires_at?->toIso8601String();
                        }
                    } elseif ($user->membership_credits !== $new['credits']) {
                        $row['status'] = 'needs_review';
                    }

                    return $row;
                });
            });
        $json = json_encode(['at' => now()->toIso8601String(), 'applied' => (bool) $this->option('apply'), 'users' => $rows], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
        if ($path = $this->option('output')) {
            file_put_contents($path, $json);
            chmod($path, 0600);
        }
        $this->line($json);

        return self::SUCCESS;
    }
}
