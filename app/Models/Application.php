<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Application extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'candidate_id', 'offer_id', 'document_id'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }

    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function analysis(): HasOne
    {
        return $this->hasOne(Analysis::class);
    }

    public function scopeOwnedByCurrentUser($query)
    {
        return $query->where('user_id', auth()->id());
    }
}
