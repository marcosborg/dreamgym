<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\MembershipReconciliationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ReconcileMemberships extends Command
{
    protected $signature = 'memberships:reconcile {--apply : Persist the reviewed reconstruction} {--user= : Limit to one user ID}';

    protected $description = 'Preview or rebuild membership balances from dated payments and eligible historical bookings';

    public function handle(MembershipReconciliationService $service): int
    {
        $users = User::query()->whereHas('payments', fn ($q) => $q->where('product_type', 'membership')->where('status', 'paid'));
        if ($this->option('user')) {
            $users->whereKey($this->option('user'));
        }
        $rows = [];
        DB::beginTransaction();
        try {
            foreach ($users->get() as $user) {
                $rows[] = array_values($service->reconcile($user, true));
            }
            $this->table(['User ID', 'Payments', 'Debited bookings', 'Remaining credits', 'Uncovered / review'], $rows);
            if ($this->option('apply')) {
                DB::commit();
                $this->info('Reconciliation saved. Uncovered bookings require manual review; no extra payment was charged.');
            } else {
                DB::rollBack();
                $this->info('Preview only. Review the results, then use --apply to save.');
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return self::SUCCESS;
    }
}
