<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Room;
use App\Services\AvailabilityService;
use App\Services\ExpiredBookingService;
use App\Services\SandboxPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ExpiredBookingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_expired_hold_releases_capacity_and_cancels_once_without_issuing_access(): void
    {
        Mail::fake();
        $this->travelTo(now()->startOfMinute());
        $room = Room::create(['name' => 'Gym', 'capacity' => 1, 'slot_price_cents' => 800, 'currency' => 'EUR', 'is_active' => true]);
        $booking = Booking::create(['room_id' => $room->id, 'customer_name' => 'Test', 'customer_email' => 'test@example.test', 'starts_at' => now()->addDay(), 'ends_at' => now()->addDay()->addHour(), 'booking_type' => 'single_hour', 'status' => 'pending', 'payment_status' => 'pending', 'price_cents' => 800, 'currency' => 'EUR', 'seats_reserved' => 1, 'payment_expires_at' => now()->addDays(2)]);
        $payment = app(SandboxPaymentService::class)->createPayment($booking);
        $availability = app(AvailabilityService::class);
        $this->assertTrue($availability->hasConflict($room, $booking->starts_at, $booking->ends_at));
        $this->travel(15)->minutes();
        $this->assertTrue($booking->paymentHoldExpired());
        $this->assertFalse($availability->hasConflict($room, $booking->starts_at, $booking->ends_at));
        $this->assertSame(1, app(ExpiredBookingService::class)->expire());
        $this->assertSame(0, app(ExpiredBookingService::class)->expire());
        $this->assertSame('cancelled', $booking->fresh()->status);
        // Late money must be recorded for review, never reactivate the released booking.
        app(SandboxPaymentService::class)->complete($payment);
        $this->assertTrue($payment->fresh()->metadata['requires_review']);
        $this->assertSame('paid', $payment->fresh()->status);
        $this->assertDatabaseCount('access_codes', 0);
        Mail::assertNothingSent();
    }

    public function test_confirmed_bookings_are_never_expired(): void
    {
        $room = Room::create(['name' => 'Gym', 'capacity' => 1, 'slot_price_cents' => 800, 'currency' => 'EUR', 'is_active' => true]);
        $booking = Booking::create(['room_id' => $room->id, 'customer_name' => 'Test', 'customer_email' => 'test@example.test', 'starts_at' => now()->addDay(), 'ends_at' => now()->addDay()->addHour(), 'status' => 'confirmed', 'payment_status' => 'paid', 'price_cents' => 800, 'currency' => 'EUR', 'payment_expires_at' => now()->subHour()]);
        $this->artisan('bookings:expire')->assertSuccessful();
        $this->assertSame('confirmed', $booking->fresh()->status);
    }
}
