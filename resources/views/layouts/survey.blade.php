<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'アンケート')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    @stack('styles')
</head>
<body class="bg-gray-50 min-h-screen">

    <header class="bg-white shadow-sm">
        <div class="max-w-2xl mx-auto px-4 py-4">
            <p class="text-sm text-gray-500 text-center">アンケートフォーム</p>
        </div>
    </header>

    <main class="max-w-2xl mx-auto px-4 py-8">
        @yield('content')
    </main>

    <footer class="mt-12 py-6 text-center text-xs text-gray-400">
        &copy; {{ date('Y') }} アンケート管理システム
    </footer>

    @stack('scripts')
</body>
</html>
