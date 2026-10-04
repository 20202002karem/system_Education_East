<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Site extends Model
{
    use HasFactory;

    protected $fillable = ['type', 'code', 'name_ar', 'status'];

    public function siteManagers(): HasMany
    {
        return $this->hasMany(SiteManager::class);
    }

    public function currentManager(): ?SiteManager
    {
        return $this->siteManagers()->whereNull('to')->latest('from')->first();
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
