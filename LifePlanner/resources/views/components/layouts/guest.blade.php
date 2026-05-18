<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="LifePlanner SIM — Login">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Login — LifePlanner SIM' }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen flex items-center justify-center" style="background: linear-gradient(135deg, var(--color-paper) 0%, var(--color-cream) 100%);">

    <div style="width:100%; max-width:420px; padding:0 24px;">
        {{ $slot }}
    </div>

</body>
</html>
