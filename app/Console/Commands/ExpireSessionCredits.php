<?php

namespace App\Console\Commands;

use App\Models\SessionCreditLot;
use App\Models\User;
use App\Services\SessionCreditService;
use Illuminate\Console\Command;

class ExpireSessionCredits extends Command
{
    protected $signature = 'credits:expire';

    protected $description = 'Remove expired session-pack credits from stored balances';

    public function handle(SessionCreditService $credits): int
    {
        $count = 0;
        User::whereIn('id', SessionCreditLot::select('user_id')->where('remaining_credits', '>', 0)->where('expires_at', '<=', now()))
            ->eachById(function (User $user) use ($credits, &$count) {
                $credits->summary($user);
                $count++;
            });
        $this->info('Credit balances checked: '.$count);

        return self::SUCCESS;
    }
}
