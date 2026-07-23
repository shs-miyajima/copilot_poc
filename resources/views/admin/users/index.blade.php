@extends('layouts.admin')

@section('title', 'ユーザー管理')

@section('content')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <h2 class="text-xl font-bold text-gray-800">ユーザー管理</h2>
    <a href="{{ route('admin.users.create') }}"
       class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md transition-colors">
        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        新規ユーザー作成
    </a>
</div>

{{-- 検索フィルタ --}}
<form method="GET" class="bg-white rounded-lg shadow-sm border border-gray-100 p-4 mb-6 flex flex-wrap gap-3">
    <input type="text" name="search" value="{{ request('search') }}"
           placeholder="名前・メールで検索"
           class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 w-64">
    <select name="role" class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        <option value="">全ロール</option>
        <option value="admin"  {{ request('role') === 'admin'  ? 'selected' : '' }}>管理者</option>
        <option value="editor" {{ request('role') === 'editor' ? 'selected' : '' }}>編集者</option>
        <option value="viewer" {{ request('role') === 'viewer' ? 'selected' : '' }}>閲覧者</option>
    </select>
    <button type="submit" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm rounded-md transition-colors">検索</button>
    @if(request('search') || request('role'))
        <a href="{{ route('admin.users.index') }}" class="px-4 py-2 text-gray-500 hover:text-gray-700 text-sm">クリア</a>
    @endif
</form>

<div class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden">
    <table class="w-full text-sm">
        <thead>
            <tr class="bg-gray-50 border-b border-gray-200">
                <th class="text-left px-4 py-3 text-gray-600">名前</th>
                <th class="text-left px-4 py-3 text-gray-600">メールアドレス</th>
                <th class="text-left px-4 py-3 text-gray-600">ロール</th>
                <th class="text-left px-4 py-3 text-gray-600">ステータス</th>
                <th class="text-left px-4 py-3 text-gray-600">最終ログイン</th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($users as $user)
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-3 font-medium text-gray-800">{{ $user->name }}</td>
                <td class="px-4 py-3 text-gray-600">{{ $user->email }}</td>
                <td class="px-4 py-3">
                    @php
                        $roleClass = match($user->role) {
                            'admin'  => 'bg-red-100 text-red-800',
                            'editor' => 'bg-blue-100 text-blue-800',
                            default  => 'bg-gray-100 text-gray-800',
                        };
                        $roleLabel = match($user->role) {
                            'admin'  => '管理者',
                            'editor' => '編集者',
                            default  => '閲覧者',
                        };
                    @endphp
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $roleClass }}">
                        {{ $roleLabel }}
                    </span>
                </td>
                <td class="px-4 py-3">
                    @if($user->is_active)
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">有効</span>
                    @else
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-500">無効</span>
                    @endif
                </td>
                <td class="px-4 py-3 text-gray-500 text-xs">
                    {{ $user->last_login_at ? $user->last_login_at->format('Y/m/d H:i') : '未ログイン' }}
                </td>
                <td class="px-4 py-3">
                    <div class="flex items-center gap-2 justify-end">
                        <a href="{{ route('admin.users.edit', $user) }}"
                           class="text-xs text-blue-600 hover:underline">編集</a>
                        @if(Auth::id() !== $user->id)
                        <form method="POST" action="{{ route('admin.users.toggleActive', $user) }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit"
                                    class="text-xs {{ $user->is_active ? 'text-orange-500 hover:text-orange-700' : 'text-green-600 hover:text-green-800' }}">
                                {{ $user->is_active ? '無効化' : '有効化' }}
                            </button>
                        </form>
                        @endif
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="px-4 py-8 text-center text-gray-400">ユーザーが見つかりません。</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">
    {{ $users->links() }}
</div>
@endsection
