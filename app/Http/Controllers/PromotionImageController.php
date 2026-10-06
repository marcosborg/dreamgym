<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PromotionImageController extends Controller
{
    public function __invoke(string $filename): StreamedResponse
    {
        abort_unless(preg_match('/\A[A-Za-z0-9_-]+\.(?:jpe?g|png|webp)\z/i', $filename), 404);
        $disk = Storage::disk('public');
        $path = 'promotions/'.$filename;
        abort_unless($disk->exists($path), 404);

        return $disk->response($path, null, ['Cache-Control' => 'public, max-age=86400', 'X-Content-Type-Options' => 'nosniff']);
    }
}
