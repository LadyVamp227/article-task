<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Admin') — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-100 text-gray-900 antialiased">
    <nav class="border-b border-gray-200 bg-white">
        <div class="mx-auto flex h-16 max-w-5xl items-center justify-between px-4">
            <a href="{{ route('admin.surveys.index') }}" class="flex items-center gap-2 font-semibold">
                <span class="inline-flex h-7 w-7 items-center justify-center rounded-md bg-indigo-600 text-sm text-white">S</span>
                Surveys Admin
            </a>
            <div class="flex items-center gap-4 text-sm">
                <span class="hidden text-gray-500 sm:inline">{{ auth()->user()?->email }}</span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="rounded-lg border border-gray-300 px-3 py-1.5 font-medium text-gray-700 hover:bg-gray-50">
                        Sign out
                    </button>
                </form>
            </div>
        </div>
    </nav>

    <main class="mx-auto max-w-5xl px-4 py-8">
        @if (session('status'))
            <div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                {{ session('status') }}
            </div>
        @endif

        @yield('content')
    </main>
</body>
</html>
