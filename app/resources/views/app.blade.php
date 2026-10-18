<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title inertia>Damai Karya</title>

        <link rel="icon" type="image/png" href="/brand/damai-karya/icon.png">
        <link rel="preload" href="/brand/damai-karya/fonts/nunito.woff2" as="font" type="font/woff2" crossorigin>
        <link rel="preload" href="/brand/damai-karya/fonts/source-sans-3.woff2" as="font" type="font/woff2" crossorigin>

        @routes
        @vite(['resources/js/app.ts'])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
