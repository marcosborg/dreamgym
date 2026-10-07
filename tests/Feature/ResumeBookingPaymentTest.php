<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Room;
use App\Models\User;
use App\Services\SandboxPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ResumeBookingPaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_resuming_each_payment_method_reuses_payment_and_deadline(): void
    {
        config(['payments.provider' => 'ifthenpay']);
        Http::preventStrayRequests();
        $this->travelTo(now()->startOfMinute());
        foreach ([null, 'multibanco', 'mbway'] as $method) {
            $booking = $this->booking();
            $payment = app(SandboxPaymentService::class)->createPayment($booking);
            $metadata = $method ? ['payment_method' => $method, 'ifthenpay' => ['transactionId' => 'TEST', 'entity' => '12345', 'reference' => '123456789', 'expireDate' => now()->addMinutes(4)->toDateTimeString()]] : [];
            $payment->update(['provider' => 'ifthenpay', 'metadata' => $metadata]);
            $metadata = $payment->fresh()->metadata;
            $deadline = $booking->paymentDeadline()->toDateTimeString();
            $count = Booking::count();
            $this->actingAs($booking->user);
            foreach (['pt', 'en'] as $locale) {
                $response = $this->withSession(['locale' => $locale])->get(route('account.dashboard'))->assertOk();
                $this->assertSame(2, substr_count($response->getContent(), 'data-resume-payment="'.$booking->id.'"'));
                $response->assertSee($locale === 'pt' ? 'Concluir pagamento' : 'Complete payment');
                $response->assertSee($booking->paymentDeadline()->format('H:i'));
            }
            for ($i = 0; $i < 2; $i++) {
                $response = $this->get(route('checkout.show', $booking))->assertOk();
                if ($method === null) {
                    $response->assertSee('name="payment_method"', false);
                } elseif ($method === 'multibanco') {
                    $response->assertSee('123456789');
                } else {
                    $response->assertSee(__('site.mbway_request_sent'));
                }
            }
            $this->assertSame($count, Booking::count());
            $this->assertSame(1, $booking->payment()->count());
            $this->assertSame($deadline, $booking->fresh()->paymentDeadline()->toDateTimeString());
            $this->assertSame($metadata, $payment->fresh()->metadata);
        }
        Http::assertNothingSent();
    }

    public function test_ineligible_bookings_have_no_resume_link_and_other_users_cannot_access(): void
    {
        foreach ([['status' => 'confirmed', 'payment_status' => 'paid'], ['status' => 'cancelled'], ['payment_expires_at' => now()], ['payment_status' => 'paid']] as $changes) {
            $booking = $this->booking($changes);
            $this->actingAs($booking->user)->get(route('account.dashboard'))->assertOk()->assertDontSee('data-resume-payment=', false);
        }
        $booking = $this->booking();
        $this->actingAs(User::factory()->create())->get(route('checkout.show', $booking))->assertForbidden();
    }

    public function test_a_link_opened_after_expiry_cannot_resume_payment(): void
    {
        $booking = $this->booking();
        $this->actingAs($booking->user)->get(route('account.dashboard'))->assertSee('data-resume-payment=', false);
        $this->travel(15)->minutes();
        $this->get(route('checkout.show', $booking))->assertOk()->assertSee(__('site.payment_hold_expired'))->assertDontSee('name="payment_method"', false);
        $this->get(route('account.dashboard'))->assertDontSee('data-resume-payment=', false);
        $this->assertSame('cancelled', $booking->fresh()->status);
    }

    private function booking(array $changes = []): Booking
    {
        $user = User::factory()->create();
        $room = Room::firstOrCreate(['name' => 'Test Gym'], ['capacity' => 5, 'slot_price_cents' => 800, 'currency' => 'EUR', 'is_active' => true]);

        return Booking::create(array_merge(['room_id' => $room->id, 'user_id' => $user->id, 'customer_name' => $user->name, 'customer_email' => $user->email, 'booking_type' => 'single_hour', 'starts_at' => now()->addDay(), 'ends_at' => now()->addDay()->addHour(), 'status' => 'pending', 'payment_status' => 'pending', 'price_cents' => 800, 'currency' => 'EUR', 'payment_expires_at' => now()->addMinutes(15)], $changes));
    }
}
