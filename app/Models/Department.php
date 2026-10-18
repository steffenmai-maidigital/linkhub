<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Department extends Model
{
    use Auditable;

    protected $fillable = ['area_id', 'name', 'name_en', 'slug', 'sort_order', 'active'];
    protected $casts = ['active' => 'boolean'];

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
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
