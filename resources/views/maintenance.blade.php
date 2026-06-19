<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased">
<main class="flex min-h-screen items-center justify-center bg-white px-6 text-center">
    <div>
        <img src="{{ asset('brand/logo.png') }}" alt="Dream Gym" class="mx-auto h-28 w-auto">
        <p class="mt-6 text-lg font-semibold text-neutral-700">Estamos a preparar tudo para sí.</p>
    </div>
</main>
</body>
</html>
