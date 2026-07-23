<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Question extends Model
{
    use HasFactory;

    public const TYPES = [
        'radio'     => '単一選択（ラジオボタン）',
        'checkbox'  => '複数選択（チェックボックス）',
        'text'      => '短文テキスト',
        'textarea'  => '長文テキスト',
        'rating'    => '評価スケール',
        'number'    => '数値入力',
        'date'      => '日付・時刻入力',
        'dropdown'  => 'ドロップダウン選択',
    ];

    /** 選択肢を持つ質問タイプ */
    public const OPTION_TYPES = ['radio', 'checkbox', 'dropdown'];

    /** 評価スケール */
    public const RATING_TYPE = 'rating';

    protected $fillable = [
        'survey_id',
        'type',
        'label',
        'hint',
        'is_required',
        'order',
        'scale_min',
        'scale_max',
    ];

    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
            'order'       => 'integer',
            'scale_min'   => 'integer',
            'scale_max'   => 'integer',
        ];
    }

    // ----------------------------------------------------------------
    // リレーション
    // ----------------------------------------------------------------

    public function survey(): BelongsTo
    {
        return $this->belongsTo(Survey::class);
    }

    public function options(): HasMany
    {
        return $this->hasMany(QuestionOption::class)->orderBy('order');
    }

    public function logics(): HasMany
    {
        return $this->hasMany(QuestionLogic::class);
    }

    // ----------------------------------------------------------------
    // ヘルパー
    // ----------------------------------------------------------------

    public function hasOptions(): bool
    {
        return in_array($this->type, self::OPTION_TYPES, true);
    }

    public function isRating(): bool
    {
        return $this->type === self::RATING_TYPE;
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }
}
