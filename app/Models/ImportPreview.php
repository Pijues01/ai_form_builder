<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportPreview extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'form_id',
        'original_filename',
        'file_type',
        'disk',
        'file_path',
        'status',
        'result',
        'warnings',
        'error',
    ];

    protected $casts = [
        'result' => 'array',
        'warnings' => 'array',
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
