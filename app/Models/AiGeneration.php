<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiGeneration extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'form_id',
        'mode',
        'prompt',
        'input',
        'result',
        'status',
        'error',
        'provider',
        'model',
        'tokens_prompt',
        'tokens_completion',
        'tokens_total',
        'latency_ms',
        'repair_attempts',
    ];

    protected $casts = [
        'input' => 'array',
        'result' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }
}
