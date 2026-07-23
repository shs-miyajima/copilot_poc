<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionLogic extends Model
{
    use HasFactory;

    protected $fillable = [
        'question_id',
        'option_id',
        'target_question_id',
        'action',
    ];

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function option(): BelongsTo
    {
        return $this->belongsTo(QuestionOption::class, 'option_id');
    }

    public function targetQuestion(): BelongsTo
    {
        return $this->belongsTo(Question::class, 'target_question_id');
    }
}
