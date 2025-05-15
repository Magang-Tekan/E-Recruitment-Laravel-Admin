<?php

namespace App\Models;

use App\Enums\CandidatesStage;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Candidate extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'vacancy_id',
        'applied_at',
        'status',
    ];

    protected $casts = [
        'applied_at' => 'date',
        'status' => CandidatesStage::class,
    ];

    /**
     * Get the user associated with the candidate.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get administrations associated with the candidate.
     */
    public function administrations(): HasMany
    {
        // If Administration model exists, uncomment this line:
        // return $this->hasMany(Administration::class);
        
        // If there is no Administration model yet, return empty collection
        return $this->hasMany(Model::class); // This is a placeholder, actual implementation would depend on existence of Administration model
    }

    /**
     * Get the vacancy associated with the candidate.
     */
    public function vacancy(): BelongsTo
    {
        return $this->belongsTo(Vacancies::class);
    }
}
