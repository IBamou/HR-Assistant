<?php

namespace App\Models;

use App\Enums\ProcessStatus;
use Database\Factories\AnalysisFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Analysis extends Model
{
    /** @use HasFactory<AnalysisFactory> */
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

    /**
     * @return BelongsTo<Application, $this>
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeOwnedByCurrentUser($query)
    {
        return $query->where('user_id', auth()->id());
    }
}
