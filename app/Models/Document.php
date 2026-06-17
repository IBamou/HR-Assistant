<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Document extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'title', 'filename', 'original_path', 'metadata'];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function application(): HasOne
    {
        return $this->hasOne(Application::class);
    }

    public function scopeOwnedByCurrentUser($query)
    {
        return $query->where('user_id', auth()->id());
    }
}
