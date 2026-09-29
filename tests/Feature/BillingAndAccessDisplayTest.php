<?php

namespace Tests\Feature;

use App\Mail\BookingConfirmed;
use App\Models\AccessCode;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class BillingAndAccessDisplayTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['payments.provider' => 'sandbox', 'lock.provider' => 'simulated']);
        Mail::fake();
    }

    public function test_optional_nif_is_saved_per_purchase_and_cannot_be_changed_after_payment(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $first = $this->payment($user);
        $this->get(route('purchase.checkout', $first))->assertOk()->assertSee('name="billing_nif"', false);
        $this->post(route('purchase.complete', $first), $this->input(['billing_nif' => '123456789']))->assertRedirect(route('purchase.confirmed', $first));
        $this->assertSame('123456789', $first->fresh()->billing_nif);
        $this->assertSame('paid', $first->fresh()->status);
        $this->post(route('purchase.complete', $first), $this->input(['billing_nif' => '987654321']));
        $this->assertSame('123456789', $first->fresh()->billing_nif);
        $second = $this->payment($user);
        $this->post(route('purchase.complete', $second), $this->input())->assertRedirect(route('purchase.confirmed', $second));
        $this->assertNull($second->fresh()->billing_nif);
    }

    public function test_invalid_nif_and_other_users_cannot_change_payment(): void
    {
        $user = User::factory()->create();
        $payment = $this->payment($user);
        $this->actingAs($user)->post(route('purchase.complete', $payment), $this->input(['billing_nif' => 'abc123']))->assertSessionHasErrors('billing_nif');
        $this->assertSame('pending', $payment->fresh()->status);
        $this->actingAs(User::factory()->create())->post(route('purchase.complete', $payment), $this->input(['billing_nif' => '123456789']))->assertForbidden();
        $this->assertNull($payment->fresh()->billing_nif);
    }

    public function test_booking_payment_keeps_nif_and_customer_pin_has_hash_without_changing_lock_code(): void
    {
        $user = User::factory()->create();
        $room = Room::create(['name' => 'Gym', 'capacity' => 5, 'slot_price_cents' => 1200, 'currency' => 'EUR', 'is_active' => true]);
        $booking = Booking::create([
            'room_id' => $room->id, 'user_id' => $user->id, 'customer_name' => $user->name,
            'customer_email' => $user->email, 'locale' => 'pt', 'booking_type' => 'single_hour', 'seats_reserved' => 1,
            'starts_at' => now()->addDay(), 'ends_at' => now()->addDay()->addHour(),
            'status' => 'pending', 'payment_status' => 'pending', 'price_cents' => 1200, 'currency' => 'EUR',
            'payment_expires_at' => now()->addMinutes(15),
        ]);
        $this->actingAs($user)->get(route('checkout.show', $booking))->assertOk()->assertSee('name="billing_nif"', false);
        $this->post(route('checkout.complete', $booking), $this->input(['billing_nif' => '123456789']))->assertRedirect(route('booking.confirmed', $booking));
        $this->assertSame('123456789', $booking->payment->billing_nif);
        $code = $booking->fresh()->accessCode;
        $code->update(['provision_status' => AccessCode::PROVISIONED]);
        $raw = $code->code;
        $this->get(route('booking.confirmed', $booking))->assertOk()->assertSee($raw.'#');
        $this->get(route('account.dashboard'))->assertOk()->assertSee($raw.'#');
        $this->assertStringContainsString($raw.'#', (new BookingConfirmed($booking->fresh()->load('accessCode', 'room')))->render());
        $this->assertSame($raw, $code->fresh()->code);
        $this->assertStringNotContainsString('#', $raw);
    }

    private function payment(User $user): Payment
    {
        return Payment::create(['user_id' => $user->id, 'product_type' => 'session_pack', 'provider' => 'sandbox_mbway_placeholder', 'reference' => uniqid('TEST-'), 'amount_cents' => 2000, 'currency' => 'EUR', 'status' => 'pending', 'metadata' => ['credits' => 3]]);
    }

    private function input(array $extra = []): array
    {
        return array_merge(['terms_accepted' => '1', 'age_authorization_accepted' => '1'], $extra);
    }
}
