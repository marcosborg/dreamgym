<?php

namespace Tests\Feature;

use App\Mail\BookingConfirmed;
use App\Models\AccessCode;
use App\Models\Booking;
use App\Models\Room;
use App\Models\TtlockConnection;
use App\Services\AccessCodeService;
use App\Services\Locks\LockProvisioningService;
use App\Services\Locks\TtlockClient;
use App\Services\Locks\TtlockLockProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Tests\TestCase;

class TtlockIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['lock.provider' => 'ttlock']);
        Http::preventStrayRequests();
        Mail::fake();
        TtlockConnection::forceCreate(['id' => 1, 'credentials' => ['base_url' => 'https://euapi.ttlock.com', 'client_id' => 'client', 'client_secret' => 'secret', 'access_token' => 'token', 'refresh_token' => 'refresh', 'expires_at' => now()->addDay()->timestamp]]);
    }

    private function code(): AccessCode
    {
        $room = Room::create(['name' => 'Gym', 'capacity' => 1, 'slot_price_cents' => 1200, 'currency' => 'EUR', 'ttlock_lock_id' => 123]);
        $booking = Booking::create(['room_id' => $room->id, 'customer_name' => 'Test', 'customer_email' => 'test@example.com', 'locale' => 'pt', 'booking_type' => 'single_hour', 'starts_at' => now()->addDay()->startOfHour(), 'ends_at' => now()->addDay()->startOfHour()->addHour(), 'status' => 'confirmed', 'payment_status' => 'paid', 'price_cents' => 1200, 'currency' => 'EUR']);

        return app(AccessCodeService::class)->createForBooking($booking);
    }

    private function remotePin(AccessCode $code): array
    {
        return ['keyboardPwdId' => 456, 'keyboardPwdName' => 'dreamgym-reservation-'.$code->booking_id.'-access-'.$code->id, 'keyboardPwd' => $code->code, 'startDate' => $code->valid_from->getTimestampMs(), 'endDate' => $code->valid_until->getTimestampMs(), 'status' => 1];
    }

    public function test_credentials_are_encrypted_and_token_refresh_is_saved(): void
    {
        $connection = TtlockConnection::find(1);
        $data = $connection->credentials;
        $data['expires_at'] = 0;
        $connection->update(['credentials' => $data]);
        $this->assertStringNotContainsString('client_secret', DB::table('ttlock_connections')->value('credentials'));
        $this->assertSame([], $connection->toArray()['credentials'] ?? []);
        Http::fakeSequence()->push(['access_token' => 'new-token', 'refresh_token' => 'new-refresh', 'expires_in' => 3600])->push(['list' => []]);
        app(TtlockClient::class)->request('/v3/lock/list');
        $this->assertSame('new-refresh', $connection->fresh()->credentials['refresh_token']);
        Http::assertSent(fn ($r) => str_ends_with($r->url(), '/oauth2/token') && $r['grant_type'] === 'refresh_token' && $r['refresh_token'] === 'refresh');
        Http::assertSent(fn ($r) => str_ends_with($r->url(), '/v3/lock/list') && $r['accessToken'] === 'new-token');
    }

    public function test_pin_uses_exact_window_gateway_and_is_idempotent(): void
    {
        $code = $this->code();
        Http::fakeSequence()->push(['list' => []])->push(['keyboardPwdVersion' => 4])->push(['keyboardPwdId' => 456]);
        $provider = app(TtlockLockProvider::class);
        $result = $provider->provisionTemporaryPin($code);
        $this->assertTrue($result->ready_for_use);
        $provider->provisionTemporaryPin($result);
        Http::assertSentCount(3);
        Http::assertSent(fn ($r) => str_ends_with($r->url(), '/keyboardPwd/add') && $r['addType'] === 2 && $r['lockId'] === 123
            && $r['keyboardPwd'] === $code->code && $r['startDate'] === $code->valid_from->getTimestampMs() && $r['endDate'] === $code->valid_until->getTimestampMs());
    }

    public function test_uncertain_add_is_reconciled_without_a_second_pin(): void
    {
        $code = $this->code();
        Http::fakeSequence()->push(['list' => []])->push(['keyboardPwdVersion' => 4])->push(['errcode' => -2012])
            ->push(['list' => [$this->remotePin($code)]]);
        $service = app(LockProvisioningService::class);
        $this->assertFalse($service->provision($code)->ready_for_use);
        $this->assertTrue($service->provision($code)->ready_for_use);
        Http::assertSentCount(4);
    }

    public function test_cancellation_reconciles_and_revokes_only_the_booking_pin(): void
    {
        $code = $this->code();
        $code->update(['ttlock_lock_id' => 123]);
        $code->booking->update(['status' => 'cancelled']);
        Http::fakeSequence()->push(['list' => [$this->remotePin($code)]])->push(['errcode' => 0]);
        $provider = app(TtlockLockProvider::class);
        $this->assertNotNull($provider->revoke($code)->revoked_at);
        $provider->revoke($code);
        Http::assertSentCount(2);
        Http::assertSent(fn ($r) => str_ends_with($r->url(), '/keyboardPwd/delete') && $r['keyboardPwdId'] === 456 && $r['deleteType'] === 2);
    }

    public function test_cancelled_and_expired_bookings_never_create_access(): void
    {
        $code = $this->code();
        $code->booking->update(['status' => 'cancelled']);
        $this->assertSame('failed', app(LockProvisioningService::class)->provision($code)->provision_status);
        Http::assertNothingSent();
    }

    public function test_gateway_failure_hides_pin_from_customer_email_and_retries(): void
    {
        $code = $this->code();
        Http::fakeSequence()->push(['list' => []])->push(['keyboardPwdVersion' => 4])->push(['errcode' => -2012])->push(['list' => [$this->remotePin($code)]]);
        app(LockProvisioningService::class)->provision($code);
        $rendered = (new BookingConfirmed($code->booking->fresh(['room', 'accessCode'])))->render();
        $this->assertStringNotContainsString($code->code, $rendered);
        $this->artisan('ttlock:sync')->assertSuccessful();
        $this->assertNotNull($code->fresh()->access_notified_at);
        Mail::assertSent(BookingConfirmed::class, 1);
        $this->artisan('ttlock:sync')->assertSuccessful();
        Mail::assertSent(BookingConfirmed::class, 1);
    }

    public function test_mismatched_remote_pin_is_never_overwritten(): void
    {
        $code = $this->code();
        $pin = $this->remotePin($code);
        $pin['endDate'] += 3600000;
        Http::fakeSequence()->push(['list' => [$pin]]);
        $this->assertFalse(app(LockProvisioningService::class)->provision($code)->ready_for_use);
        Http::assertSentCount(1);
    }

    public function test_invalid_pin_or_missing_mapping_never_sends(): void
    {
        $code = $this->code();
        $code->update(['code' => '123890']);
        $this->assertFalse(app(LockProvisioningService::class)->provision($code)->ready_for_use);
        $code->update(['code' => '123456']);
        $code->booking->room->update(['ttlock_lock_id' => null]);
        $this->assertFalse(app(LockProvisioningService::class)->provision($code)->ready_for_use);
        Http::assertNothingSent();
    }

    public function test_remote_errors_do_not_leak_secrets(): void
    {
        Http::fakeSequence()->push(['error' => 'secret-token-and-password'], 401);
        try {
            app(TtlockClient::class)->request('/v3/lock/list');
            $this->fail('Expected refusal');
        } catch (RuntimeException $e) {
            $this->assertStringNotContainsString('secret-token', $e->getMessage());
            $this->assertNull($e->getPrevious());
        }
    }
}
