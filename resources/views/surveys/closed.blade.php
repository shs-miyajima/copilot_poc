@extends('layouts.survey')

@section('title', '受付終了 - ' . $survey->title)

@section('content')
<div class="bg-white rounded-lg shadow-md p-8 text-center">
    <div class="flex justify-center mb-6">
        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center">
            <svg class="w-8 h-8 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
    </div>

    <h1 class="text-2xl font-bold text-gray-800 mb-4">受付を終了しました</h1>
    <p class="text-gray-600">
        このアンケートは現在回答を受け付けておりません。<br>
        期間が終了したか、回答上限に達した可能性があります。
    </p>
</div>
@endsection
