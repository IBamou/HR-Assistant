<?php

namespace App\Models;

use App\Enums\ProcessStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Analysis extends Model
{
    use HasFactory;

    protected $fillable = [
        'application_id', 'user_id', 'matching_score', 'recommendation',
        'extracted_skills', 'missing_skills', 'strengths', 'gaps',
        'justification', 'status',
    ];

    protected function casts(): array
    {
        return [
            'matching_score' => 'integer',
            'extracted_skills' => 'array',
            'missing_skills' => 'array',
            'status' => ProcessStatus::class,
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeOwnedByCurrentUser($query)
    {
        return $query->where('user_id', auth()->id());
    }
}
