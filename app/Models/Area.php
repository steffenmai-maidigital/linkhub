<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Area extends Model
{
    use Auditable;

    protected $fillable = ['name', 'name_en', 'slug', 'sort_order', 'active'];
    protected $casts = ['active' => 'boolean'];

    public function departments(): HasMany
    {
        return $this->hasMany(Department::class)->orderBy('sort_order')->orderBy('name');
    }

    public function applications(): BelongsToMany
    {
        return $this->belongsToMany(Application::class);
    }

    /**
     * Anzeigename passend zur gewählten Sprache (EN-Feld optional)
     *
     * Display name for the chosen language (EN field optional)
     */
    public function displayName(): string
    {
        return app()->getLocale() === 'en' && filled($this->name_en) ? $this->name_en : $this->name;
    }
}
