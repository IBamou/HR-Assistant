@props(['title' => null])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <div class="flex min-h-screen flex-col items-center bg-zinc-50 p-6 dark:bg-zinc-900 lg:justify-center lg:p-8">
            <div class="w-full max-w-lg">
                {{ $slot }}
            </div>
        </div>

        @fluxScripts
    </body>
</html>
