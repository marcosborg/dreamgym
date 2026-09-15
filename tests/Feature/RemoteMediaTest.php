<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RemoteMediaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Http::preventStrayRequests();
        config(['media.remote_base_url' => 'https://images.example.test/media/']);
    }

    public function test_missing_photos_redirect_only_in_local_and_sandbox(): void
    {
        foreach (['local', 'sandbox'] as $environment) {
            $this->app->detectEnvironment(fn () => $environment);
            $this->get('/media/personal-trainers/example.webp')
                ->assertRedirect('https://images.example.test/media/personal-trainers/example.webp')
                ->assertHeader('Cache-Control', 'no-store, private')
                ->assertHeader('Referrer-Policy', 'no-referrer');
        }
        Http::assertNothingSent();
        Storage::disk('public')->assertMissing('personal-trainers/example.webp');
    }

    public function test_production_never_redirects_missing_photos(): void
    {
        $this->app->detectEnvironment(fn () => 'production');
        $this->get('/media/personal-trainers/example.webp')->assertNotFound();
    }

    public function test_local_uploads_are_served_locally_even_when_remote_reading_is_enabled(): void
    {
        $this->app->detectEnvironment(fn () => 'local');
        $path = UploadedFile::fake()->image('new.jpg')->store('personal-trainers/submissions', 'public');
        $this->get('/media/'.$path)->assertOk()->assertHeader('Content-Type', 'image/jpeg');
        Storage::disk('public')->assertExists($path);
        Http::assertNothingSent();
    }

    public function test_missing_or_unsafe_remote_configuration_fails_closed(): void
    {
        $this->app->detectEnvironment(fn () => 'local');
        foreach ([null, '', 'http://images.example.test/media', '//images.example.test/media',
            'https://user:secret@images.example.test/media', 'https://images.example.test/media?key=x',
            'https://images.example.test/media#fragment'] as $base) {
            config(['media.remote_base_url' => $base]);
            $this->get('/media/personal-trainers/example.webp')->assertNotFound();
        }
    }

    public function test_non_photo_and_traversal_paths_cannot_redirect(): void
    {
        $this->app->detectEnvironment(fn () => 'local');
        foreach (['other/example.jpg', 'personal-trainers/%2e%2e/.env',
            'personal-trainers/folder%5c..%5csecret.jpg', 'personal-trainers//example.jpg'] as $path) {
            $this->get('/media/'.$path)->assertNotFound();
        }
    }
}
