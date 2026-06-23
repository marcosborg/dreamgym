<?php

namespace Tests\Unit;

use App\Models\AccessCode;
use App\Models\Booking;
use App\Models\Room;
use App\Services\Locks\ManualIhrLockProvider;
use App\Services\Locks\SimulatedLockProvider;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LockProviderTest extends TestCase
{
    use RefreshDatabase;

    public function test_simulated_provider_marks_code_as_provisioned(): void
    {
        Carbon::setTestNow('2026-06-05 12:00:00');
        $accessCode = $this->accessCode();

        $updated = app(SimulatedLockProvider::class)->provisionTemporaryPin($accessCode);

        $this->assertSame(AccessCode::PROVISIONED, $updated->provision_status);
        $this->assertSame('simulated', $updated->lock_response_log['driver']);
        $this->assertNotNull($updated->provisioned_at);

        Carbon::setTestNow();
    }

    public function test_manual_ihr_provider_marks_code_as_pending_manual_with_payload(): void
    {
        Carbon::setTestNow('2026-06-05 12:00:00');
        $accessCode = $this->accessCode();

        $updated = app(ManualIhrLockProvider::class)->provisionTemporaryPin($accessCode);

        $this->assertSame(AccessCode::PENDING_MANUAL, $updated->provision_status);
        $this->assertSame('manual_ihr', $updated->lock_response_log['driver']);
        $this->assertSame($accessCode->code, $updated->lock_response_log['code']);
        $this->assertSame($accessCode->valid_from->toIso8601String(), $updated->lock_response_log['valid_from']);
        $this->assertNotEmpty($updated->lock_response_log['instructions']);
        $this->assertNull($updated->provisioned_at);

        Carbon::setTestNow();
    }

    private function accessCode(): AccessCode
    {
        $room = Room::create([
            'name' => 'Dream Gym Private Room',
            'capacity' => 1,
            'slot_price_cents' => 1200,
            'currency' => 'EUR',
            'is_active' => true,
        ]);

        $booking = Booking::create([
            'room_id' => $room->id,
            'customer_name' => 'Lock Customer',
            'customer_email' => 'lock@example.test',
            'locale' => 'pt',
            'starts_at' => '2026-06-08 10:00:00',
            'ends_at' => '2026-06-08 11:00:00',
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'price_cents' => 1200,
            'currency' => 'EUR',
        ]);

        return AccessCode::create([
            'booking_id' => $booking->id,
            'code' => '123456',
            'valid_from' => '2026-06-08 09:55:00',
            'valid_until' => '2026-06-08 11:05:00',
            'provision_status' => AccessCode::PENDING,
        ]);
    }
}
