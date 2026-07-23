<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 アクセス権限がありません</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="text-center">
        <h1 class="text-6xl font-bold text-gray-300">403</h1>
        <h2 class="text-2xl font-semibold text-gray-700 mt-4">アクセス権限がありません</h2>
        <p class="text-gray-500 mt-2">このページにアクセスする権限がありません。</p>
        <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('admin.dashboard') }}"
           class="mt-6 inline-block bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-6 rounded-md transition duration-150">
            前のページに戻る
        </a>
    </div>
</body>
</html>
