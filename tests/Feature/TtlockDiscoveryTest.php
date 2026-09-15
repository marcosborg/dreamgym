<?php

namespace Tests\Feature;

use App\Services\Locks\TtlockDiscoveryClient;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class TtlockDiscoveryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['ttlock.base_url' => 'https://euapi.ttlock.com', 'ttlock.client_id' => 'client', 'ttlock.client_secret' => 'secret']);
        Http::preventStrayRequests();
    }

    public function test_discovery_paginates_and_returns_only_safe_fields(): void
    {
        $lock = ['lockId' => 1, 'lockAlias' => 'Dream Gym 1', 'hasGateway' => 1, 'lockData' => 'sensitive'];
        Http::fakeSequence()->push(['access_token' => 'private-token'])
            ->push(['list' => array_fill(0, 100, $lock)])
            ->push(['list' => [array_merge($lock, ['lockId' => 2])]]);
        $locks = app(TtlockDiscoveryClient::class)->discover('account', 'password');
        $this->assertCount(101, $locks);
        $this->assertArrayNotHasKey('lockData', $locks[0]);
        Http::assertSent(fn ($r) => $r->url() === 'https://euapi.ttlock.com/oauth2/token'
            && $r['password'] === md5('password') && $r['client_secret'] === 'secret');
        Http::assertSent(fn ($r) => str_ends_with($r->url(), '/v3/lock/list') && $r['pageNo'] === 2
            && $r['accessToken'] === 'private-token' && $r['date'] > 1000000000000);
        Http::assertSentCount(3);
    }

    public function test_vendor_error_does_not_expose_secrets(): void
    {
        Http::fakeSequence()->push(['error' => 'invalid_grant', 'description' => 'private-password']);
        try {
            app(TtlockDiscoveryClient::class)->discover('account', 'password');
            $this->fail('Expected authentication failure');
        } catch (RuntimeException $e) {
            $this->assertStringNotContainsString('private-password', $e->getMessage());
        }
        Http::assertSentCount(1);
    }

    public function test_missing_configuration_never_sends_a_request(): void
    {
        config(['ttlock.client_secret' => null]);
        $this->artisan('ttlock:discover')->assertFailed();
        Http::assertNothingSent();
    }

    public function test_untrusted_endpoint_is_rejected_before_sending_credentials(): void
    {
        config(['ttlock.base_url' => 'https://example.com']);
        $this->artisan('ttlock:discover')->assertFailed();
        Http::assertNothingSent();
    }

    public function test_lock_list_errors_are_not_treated_as_empty_success(): void
    {
        Http::fakeSequence()->push(['access_token' => 'token'])->push(['errcode' => -2012]);
        $this->expectException(RuntimeException::class);
        app(TtlockDiscoveryClient::class)->discover('account', 'password');
    }
}
