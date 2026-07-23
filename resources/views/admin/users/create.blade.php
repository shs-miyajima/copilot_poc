@extends('layouts.admin')

@section('title', 'ユーザー作成')

@section('content')
<div class="max-w-2xl">
    <div class="mb-6">
        <a href="{{ route('admin.users.index') }}" class="text-sm text-blue-600 hover:underline">&larr; ユーザー一覧に戻る</a>
        <h2 class="text-xl font-bold text-gray-800 mt-1">新規ユーザー作成</h2>
    </div>

    <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6">
        @if ($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-6">
                <ul class="list-disc list-inside text-sm space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.users.store') }}" class="space-y-5">
            @csrf

            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">名前 <span class="text-red-500">*</span></label>
                <input type="text" id="name" name="name" value="{{ old('name') }}" required
                       class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('name') border-red-500 @enderror">
            </div>

            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">メールアドレス <span class="text-red-500">*</span></label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" required
                       class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('email') border-red-500 @enderror">
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">パスワード <span class="text-red-500">*</span></label>
                <input type="password" id="password" name="password" required
                       class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('password') border-red-500 @enderror">
                <p class="text-xs text-gray-400 mt-1">8文字以上、大文字・小文字・数字を含めてください。</p>
            </div>

            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">パスワード（確認） <span class="text-red-500">*</span></label>
                <input type="password" id="password_confirmation" name="password_confirmation" required
                       class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label for="role" class="block text-sm font-medium text-gray-700 mb-1">ロール <span class="text-red-500">*</span></label>
                <select id="role" name="role" required
                        class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('role') border-red-500 @enderror">
                    <option value="viewer"  {{ old('role', 'viewer') === 'viewer'  ? 'selected' : '' }}>閲覧者</option>
                    <option value="editor"  {{ old('role') === 'editor'  ? 'selected' : '' }}>編集者</option>
                    <option value="admin"   {{ old('role') === 'admin'   ? 'selected' : '' }}>管理者</option>
                </select>
            </div>

            <div class="flex items-center gap-2">
                <input type="checkbox" id="is_active" name="is_active" value="1"
                       {{ old('is_active', '1') ? 'checked' : '' }}
                       class="h-4 w-4 text-blue-600 border-gray-300 rounded">
                <label for="is_active" class="text-sm text-gray-700">アカウントを有効にする</label>
            </div>

            <div class="pt-2 flex gap-3">
                <button type="submit"
                        class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md transition-colors">
                    作成する
                </button>
                <a href="{{ route('admin.users.index') }}"
                   class="px-6 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-md transition-colors">
                    キャンセル
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
