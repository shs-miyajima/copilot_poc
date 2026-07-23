@extends('layouts.admin')

@section('title', 'アンケート新規作成')

@section('content')
<div class="max-w-2xl">
    <div class="mb-6">
        <a href="{{ route('admin.surveys.index') }}" class="text-sm text-blue-600 hover:text-blue-800">&larr; 一覧に戻る</a>
    </div>

    <div class="bg-white rounded-lg shadow-sm p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-6">アンケートの基本情報</h2>

        <form method="POST" action="{{ route('admin.surveys.store') }}">
            @csrf
            @include('admin.surveys._form')

            <div class="mt-6 flex justify-end space-x-3">
                <a href="{{ route('admin.surveys.index') }}"
                   class="px-4 py-2 border border-gray-300 rounded-md text-sm text-gray-700 hover:bg-gray-50 transition duration-150">
                    キャンセル
                </a>
                <button type="submit"
                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md transition duration-150">
                    作成して質問を追加する
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
