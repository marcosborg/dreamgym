<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Older MySQL servers implicitly give the first TIMESTAMP an ON UPDATE clause.
        // A business deadline must never change when its remaining balance changes.
        Schema::table('session_credit_lots', function (Blueprint $table) {
            $table->dateTime('expires_at')->change();
        });

        DB::transaction(function () {
            foreach (DB::table('session_credit_lots')->lockForUpdate()->get() as $lot) {
                $paidAt = $lot->payment_id
                    ? DB::table('payments')->where('id', $lot->payment_id)->value('paid_at')
                    : null;
                if ($paidAt) {
                    DB::table('session_credit_lots')->where('id', $lot->id)->update([
                        'expires_at' => Carbon::parse($paidAt)->addDays(90),
                    ]);
                }
            }

            // Production audit: the first pack lost five credits to the implicit timestamp.
            // Guard all identifying facts and the ledger, preserving any later customer activity.
            $user = DB::table('users')->where('id', 64)->lockForUpdate()->first();
            $lot = DB::table('session_credit_lots')->where('id', 1)->first();
            $expiry = DB::table('credit_movements')->where('id', 177)->first();
            if ($user && $lot && $expiry
                && (int) $lot->user_id === 64 && (int) $lot->payment_id === 160
                && (int) $lot->credits_granted === 6 && (int) $lot->remaining_credits === 0
                && (int) $user->session_credits === 0
                && (int) $expiry->user_id === 64 && $expiry->kind === 'session'
                && $expiry->reason === 'expiry' && (int) $expiry->amount === -5
                && ! DB::table('credit_movements')->where('user_id', 64)->where('id', '>', 177)->where('kind', 'session')->exists()) {
                DB::table('session_credit_lots')->where('id', 1)->update(['remaining_credits' => 5]);
                DB::table('users')->where('id', 64)->update(['session_credits' => 5]);
                DB::table('credit_movements')->insert([
                    'user_id' => 64, 'kind' => 'session', 'amount' => 5, 'balance' => 5,
                    'reason' => 'manual:Correção de expiração antecipada do pack de 07/11/2026',
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        });
    }

    public function down(): void
    {
        // Keep the fixed deadline and the audited refund when rolling back application code.
    }
};
