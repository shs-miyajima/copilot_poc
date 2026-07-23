{{--
  質問追加・編集フォームの共用パーシャル
  Alpine.js コンポーネント内で使用: type, options, hasOptions, isRating を持つこと
--}}
<div class="space-y-4">
    {{-- 質問タイプ --}}
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">
            質問タイプ <span class="text-red-500">*</span>
        </label>
        <select name="type" x-model="type" required
                class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            @foreach(\App\Models\Question::TYPES as $value => $label)
            <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </select>
    </div>

    {{-- 質問文 --}}
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">
            質問文 <span class="text-red-500">*</span>
        </label>
        <textarea name="label" x-model="label" required rows="2"
                  placeholder="質問を入力してください"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
    </div>

    {{-- 補足説明 --}}
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">補足説明</label>
        <input type="text" name="hint" x-model="hint"
               placeholder="回答者へのヒント（任意）"
               class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
    </div>

    {{-- 評価スケール設定 --}}
    <div x-show="isRating" class="grid grid-cols-2 gap-3">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">最小値</label>
            <input type="number" name="scale_min" x-model="scale_min" min="1"
                   class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">最大値</label>
            <input type="number" name="scale_max" x-model="scale_max" min="2"
                   class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
    </div>

    {{-- 選択肢 --}}
    <div x-show="hasOptions">
        <label class="block text-sm font-medium text-gray-700 mb-2">選択肢</label>
        <div class="space-y-2">
            <template x-for="(opt, i) in options" :key="i">
                <div class="flex items-center space-x-2">
                    <input type="text" :name="`options[${i}]`" x-model="options[i]"
                           placeholder="選択肢を入力"
                           class="flex-1 px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <button type="button" @click="removeOption(i)"
                            class="text-red-400 hover:text-red-600 flex-shrink-0">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </template>
        </div>
        <button type="button" @click="addOption()"
                class="mt-2 text-sm text-blue-600 hover:text-blue-800 flex items-center">
            <svg class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            選択肢を追加
        </button>
    </div>

    {{-- 必須設定 --}}
    <div class="flex items-center">
        <input type="checkbox" name="is_required" id="is_required" x-model="is_required"
               value="1"
               class="h-4 w-4 text-blue-600 border-gray-300 rounded">
        <label for="is_required" class="ml-2 text-sm text-gray-700">この質問を必須にする</label>
    </div>
</div>
