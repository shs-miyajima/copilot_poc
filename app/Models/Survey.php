<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Survey extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'status',
        'starts_at',
        'ends_at',
        'max_responses',
        'response_limit_type',
        'thanks_message',
        'public_token',
    ];

    protected function casts(): array
    {
        return [
            'starts_at'    => 'datetime',
            'ends_at'      => 'datetime',
            'max_responses' => 'integer',
        ];
    }

    // ----------------------------------------------------------------
    // ライフサイクルフック
    // ----------------------------------------------------------------

    protected static function booted(): void
    {
        static::creating(function (Survey $survey) {
            if (empty($survey->public_token)) {
                $survey->public_token = Str::uuid()->toString();
            }
        });
    }

    // ----------------------------------------------------------------
    // リレーション
    // ----------------------------------------------------------------

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class)->orderBy('order');
    }

    public function responses(): HasMany
    {
        return $this->hasMany(Response::class);
    }

    // ----------------------------------------------------------------
    // スコープ
    // ----------------------------------------------------------------

    /** 公開中かつ期間内のアンケートのみ */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published')
            ->where(function (Builder $q) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function (Builder $q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            });
    }

    // ----------------------------------------------------------------
    // ヘルパーメソッド
    // ----------------------------------------------------------------

    /**
     * 回答を受け付けられる状態かどうかを判定する
     */
    public function isAcceptingResponses(): bool
    {
        if ($this->status !== 'published') {
            return false;
        }
        if ($this->starts_at && $this->starts_at->isFuture()) {
            return false;
        }
        if ($this->ends_at && $this->ends_at->isPast()) {
            return false;
        }
        if ($this->max_responses !== null) {
            $count = $this->responses()->whereNotNull('submitted_at')->count();
            if ($count >= $this->max_responses) {
                return false;
            }
        }
        return true;
    }

    /**
     * 公開URLを返す
     */
    public function getPublicUrlAttribute(): string
    {
        return url('/s/' . $this->public_token);
    }

    /**
     * ステータスの日本語ラベルを返す
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'draft'     => '下書き',
            'published' => '公開中',
            'closed'    => '終了',
            default     => $this->status,
        };
    }

    /**
     * ステータスのバッジCSSクラスを返す
     */
    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            'draft'     => 'bg-gray-100 text-gray-800',
            'published' => 'bg-green-100 text-green-800',
            'closed'    => 'bg-red-100 text-red-800',
            default     => 'bg-gray-100 text-gray-800',
        };
    }
}
