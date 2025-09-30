<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VacancyPeriods extends Model
{
    use HasFactory;

    protected $table = 'vacancy_periods';

    protected $fillable = [
        'vacancy_id',
        'period_id',
    ];

    public function vacancy(): BelongsTo
    {
        return $this->belongsTo(Vacancies::class, 'vacancy_id');
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(Period::class, 'period_id');
    }
}