@extends('layouts.survey')

@section('title', '回答完了 - ' . $survey->title)

@section('content')
<div class="bg-white rounded-lg shadow-md p-8 text-center">
    <div class="flex justify-center mb-6">
        <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center">
            <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
        </div>
    </div>

    <h1 class="text-2xl font-bold text-gray-800 mb-4">回答ありがとうございました</h1>

    @if($survey->thanks_message)
        <p class="text-gray-600 whitespace-pre-line">{{ $survey->thanks_message }}</p>
    @else
        <p class="text-gray-600">アンケートへのご回答ありがとうございました。<br>いただいた回答は今後の改善に役立てます。</p>
    @endif
</div>
@endsection
