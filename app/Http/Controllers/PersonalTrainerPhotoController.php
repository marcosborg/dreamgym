<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PersonalTrainerPhotoController extends Controller
{
    /**
     * @throws FileNotFoundException
     */
    public function __invoke(string $path): StreamedResponse|RedirectResponse
    {
        abort_unless(str_starts_with($path, 'personal-trainers/'), 404);
        abort_if(collect(explode('/', $path))->contains(
            fn (string $segment) => $segment === '.' || $segment === '..'
                || ! preg_match('/\A[A-Za-z0-9_.-]+\z/', $segment)
        ), 404);

        $disk = Storage::disk('public');
        if (! $disk->exists($path)) {
            $base = rtrim((string) config('media.remote_base_url'), '/');
            $parts = parse_url($base);
            abort_unless(app()->environment(['local', 'sandbox'])
                && filter_var($base, FILTER_VALIDATE_URL)
                && ($parts['scheme'] ?? null) === 'https'
                && ! isset($parts['user']) && ! isset($parts['pass'])
                && ! isset($parts['query']) && ! isset($parts['fragment']), 404);

            // Browser reads only: the public disk remains local for all writes.
            return redirect()->away($base.'/'.implode('/', array_map('rawurlencode', explode('/', $path))))
                ->header('Cache-Control', 'no-store')
                ->header('Referrer-Policy', 'no-referrer');
        }

        return $disk->response($path, null, [
            'Cache-Control' => 'public, max-age=86400',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
