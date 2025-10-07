<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Company extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'logo',
        'description',
        'email',
        'phone',
        'address',
        'website',
        'featured',
        'display_order',
    ];

    protected $casts = [
        'featured' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the logo URL.
     */
    public function getLogoUrlAttribute()
    {
        if ($this->logo) {
            return asset('storage/' . $this->logo);
        }
        return null;
    }

    /**
     * Get the logo URL using the service.
     */
    public function getLogoUrl(): ?string
    {
        return app(\App\Services\FileUploadService::class)->getUrl($this->logo);
    }

    /**
     * Get the periods for the company.
     */
    public function periods()
    {
        return $this->hasMany(Period::class);
    }

    public function aboutUs()
    {
        return $this->hasOne(AboutUs::class);
    }
}
