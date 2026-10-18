<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use Auditable;

    protected $fillable = ['name', 'name_en', 'sort_order'];

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
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
