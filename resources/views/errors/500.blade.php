<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 サーバーエラー</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="text-center">
        <h1 class="text-6xl font-bold text-gray-300">500</h1>
        <h2 class="text-2xl font-semibold text-gray-700 mt-4">サーバーエラーが発生しました</h2>
        <p class="text-gray-500 mt-2">しばらく経ってから再度お試しください。</p>
        <a href="{{ route('admin.dashboard') }}"
           class="mt-6 inline-block bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-6 rounded-md transition duration-150">
            ダッシュボードへ戻る
        </a>
    </div>
</body>
</html>
