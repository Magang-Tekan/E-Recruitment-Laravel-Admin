<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserAnswer extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'question_id',
        'choice_id',
        'application_id',
        'answered_at'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'answered_at' => 'datetime',
    ];

    /**
     * Get the user that owns the answer.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the question that owns the answer.
     */
    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    /**
     * Get the choice selected for this answer.
     */
    public function choice(): BelongsTo
    {
        return $this->belongsTo(Choice::class);
    }

    /**
     * Get the application that owns the answer.
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }
}