<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'ダッシュボード') - アンケート管理システム</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    @stack('styles')
</head>
<body class="bg-gray-100 min-h-screen" x-data="{ sidebarOpen: false }">

    {{-- モバイル用オーバーレイ --}}
    <div
        x-show="sidebarOpen"
        @click="sidebarOpen = false"
        x-transition:enter="transition-opacity ease-linear duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity ease-linear duration-300"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-20 bg-black bg-opacity-50 lg:hidden"
    ></div>

    {{-- サイドバー --}}
    <aside
        :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
        class="fixed inset-y-0 left-0 z-30 w-64 bg-gray-800 text-white transform transition-transform duration-300 ease-in-out lg:translate-x-0 lg:static lg:inset-auto"
    >
        <div class="flex items-center justify-between h-16 px-6 bg-gray-900">
            <span class="text-lg font-bold">アンケート管理</span>
            <button @click="sidebarOpen = false" class="lg:hidden text-gray-400 hover:text-white">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <nav class="mt-4 px-4 space-y-1">
            <a href="{{ route('admin.dashboard') }}"
               class="flex items-center px-4 py-2 rounded-md text-sm font-medium {{ request()->routeIs('admin.dashboard') ? 'bg-gray-700 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} transition duration-150">
                <svg class="mr-3 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7h18M3 12h18M3 17h18"/>
                </svg>
                ダッシュボード
            </a>

            @if(Auth::user()->isEditorOrAbove())
            <a href="{{ route('admin.surveys.index') }}"
               class="flex items-center px-4 py-2 rounded-md text-sm font-medium {{ request()->routeIs('admin.surveys.*') ? 'bg-gray-700 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} transition duration-150">
                <svg class="mr-3 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                アンケート管理
            </a>
            @endif

            @if(Auth::user()->isAdmin())
            <a href="{{ route('admin.users.index') }}"
               class="flex items-center px-4 py-2 rounded-md text-sm font-medium {{ request()->routeIs('admin.users.*') ? 'bg-gray-700 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} transition duration-150">
                <svg class="mr-3 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                </svg>
                ユーザー管理
            </a>
            @endif
        </nav>
    </aside>

    {{-- メインコンテンツエリア --}}
    <div class="lg:ml-64 flex flex-col min-h-screen">

        {{-- ヘッダー --}}
        <header class="bg-white shadow-sm h-16 flex items-center justify-between px-6">
            <button @click="sidebarOpen = true" class="lg:hidden text-gray-500 hover:text-gray-700">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>

            <h1 class="text-lg font-semibold text-gray-800">@yield('title', 'ダッシュボード')</h1>

            <div class="flex items-center space-x-4">
                <span class="text-sm text-gray-600">{{ Auth::user()->name }}</span>
                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                    @if(Auth::user()->role === 'admin') bg-red-100 text-red-800
                    @elseif(Auth::user()->role === 'editor') bg-blue-100 text-blue-800
                    @else bg-gray-100 text-gray-800
                    @endif">
                    {{ ['admin' => '管理者', 'editor' => '編集者', 'viewer' => '閲覧者'][Auth::user()->role] ?? Auth::user()->role }}
                </span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-sm text-gray-500 hover:text-gray-700 transition duration-150">
                        ログアウト
                    </button>
                </form>
            </div>
        </header>

        {{-- フラッシュメッセージ --}}
        @if (session('success'))
            <div class="mx-6 mt-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded" role="alert">
                {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div class="mx-6 mt-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded" role="alert">
                {{ session('error') }}
            </div>
        @endif

        {{-- ページコンテンツ --}}
        <main class="flex-1 p-6">
            @yield('content')
        </main>

        <footer class="py-4 px-6 text-center text-xs text-gray-400 border-t bg-white">
            &copy; {{ date('Y') }} アンケート管理システム
        </footer>
    </div>

    @stack('scripts')
</body>
</html>
