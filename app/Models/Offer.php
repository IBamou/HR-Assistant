<?php

namespace App\Models;

use App\Enums\EmploymentType;
use App\Enums\ExperienceLevel;
use Database\Factories\OfferFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int $user_id
 * @property string $title
 * @property string $description
 * @property string|null $responsibilities
 * @property array $required_skills
 * @property array|null $soft_skills
 * @property ExperienceLevel|null $min_experience_level
 * @property string|null $education_level
 * @property EmploymentType|null $employment_type
 * @property string|null $location
 * @property string $slug
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
class Offer extends Model
{
    /** @use HasFactory<OfferFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'responsibilities',
        'required_skills',
        'soft_skills',
        'min_experience_level',
        'education_level',
        'employment_type',
        'location',
        'slug',
    ];

    protected function casts(): array
    {
        return [
            'required_skills' => 'array',
            'soft_skills' => 'array',
            'min_experience_level' => ExperienceLevel::class,
            'employment_type' => EmploymentType::class,
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Offer $offer) {
            if (empty($offer->slug)) {
                $offer->slug = static::generateUniqueSlug($offer->title);
            }
        });

        static::updating(function (Offer $offer) {
            if ($offer->isDirty('title') && ! $offer->isDirty('slug')) {
                $offer->slug = static::generateUniqueSlug($offer->title, $offer->id);
            }
        });
    }

    private static function generateUniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $slug = Str::slug($title);
        $originalSlug = $slug;
        $counter = 1;

        $query = static::withTrashed()->where('slug', $slug);
        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        while ($query->exists()) {
            $slug = $originalSlug.'-'.$counter;
            $query = static::withTrashed()->where('slug', $slug);
            if ($ignoreId) {
                $query->where('id', '!=', $ignoreId);
            }
            $counter++;
        }

        return $slug;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
