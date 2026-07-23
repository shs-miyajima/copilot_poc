{{-- アンケート基本情報フォーム（create/edit 共用） --}}

<div class="space-y-5">
    {{-- タイトル --}}
    <div>
        <label for="title" class="block text-sm font-medium text-gray-700 mb-1">
            タイトル <span class="text-red-500">*</span>
        </label>
        <input type="text" id="title" name="title" value="{{ old('title', $survey->title ?? '') }}"
               required maxlength="255"
               class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('title') border-red-500 @enderror">
        @error('title')
            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
        @enderror
    </div>

    {{-- 説明 --}}
    <div>
        <label for="description" class="block text-sm font-medium text-gray-700 mb-1">説明</label>
        <textarea id="description" name="description" rows="3"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500">{{ old('description', $survey->description ?? '') }}</textarea>
    </div>

    {{-- 公開期間 --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <label for="starts_at" class="block text-sm font-medium text-gray-700 mb-1">公開開始日時</label>
            <input type="datetime-local" id="starts_at" name="starts_at"
                   value="{{ old('starts_at', isset($survey) && $survey->starts_at ? $survey->starts_at->format('Y-m-d\TH:i') : '') }}"
                   class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('starts_at') border-red-500 @enderror">
            @error('starts_at')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label for="ends_at" class="block text-sm font-medium text-gray-700 mb-1">公開終了日時</label>
            <input type="datetime-local" id="ends_at" name="ends_at"
                   value="{{ old('ends_at', isset($survey) && $survey->ends_at ? $survey->ends_at->format('Y-m-d\TH:i') : '') }}"
                   class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('ends_at') border-red-500 @enderror">
            @error('ends_at')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>

    {{-- 回答上限 --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <label for="max_responses" class="block text-sm font-medium text-gray-700 mb-1">回答上限数</label>
            <input type="number" id="max_responses" name="max_responses" min="1"
                   value="{{ old('max_responses', $survey->max_responses ?? '') }}"
                   placeholder="上限なし"
                   class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        <div>
            <label for="response_limit_type" class="block text-sm font-medium text-gray-700 mb-1">重複回答制限</label>
            <select id="response_limit_type" name="response_limit_type"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="none"   {{ old('response_limit_type', $survey->response_limit_type ?? 'none') === 'none'   ? 'selected' : '' }}>制限なし</option>
                <option value="cookie" {{ old('response_limit_type', $survey->response_limit_type ?? 'none') === 'cookie' ? 'selected' : '' }}>Cookie（同一ブラウザ1回）</option>
                <option value="ip"     {{ old('response_limit_type', $survey->response_limit_type ?? 'none') === 'ip'     ? 'selected' : '' }}>IPアドレス（同一IP1回）</option>
            </select>
        </div>
    </div>

    {{-- 送信完了メッセージ --}}
    <div>
        <label for="thanks_message" class="block text-sm font-medium text-gray-700 mb-1">送信完了メッセージ</label>
        <textarea id="thanks_message" name="thanks_message" rows="2"
                  placeholder="ご回答ありがとうございました。"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500">{{ old('thanks_message', $survey->thanks_message ?? '') }}</textarea>
    </div>
</div>
