<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Application extends Model
{
    use Auditable;

    protected $fillable = ['name', 'name_en', 'description', 'description_en', 'url', 'manual_url', 'category_id', 'sort_order', 'active'];
    protected $casts = ['active' => 'boolean'];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function areas(): BelongsToMany
    {
        return $this->belongsToMany(Area::class);
    }

    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class);
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

    /** Beschreibung passend zur gewählten Sprache / Description for the chosen language */
    public function displayDescription(): ?string
    {
        return app()->getLocale() === 'en' && filled($this->description_en) ? $this->description_en : $this->description;
    }

    /**
     * Sichtbarkeits-Zusammenfassung für die Pflege-Liste, z. B. "IT, Produktion / Fertigung"
     *
     * Visibility summary for the maintenance list, e.g. "IT, Produktion / Fertigung"
     */
    public function visibilityLabel(): string
    {
        $parts = [];
        foreach ($this->areas as $area) {
            $parts[] = $area->displayName();
        }
        foreach ($this->departments as $dept) {
            $parts[] = ($dept->area ? $dept->area->displayName().' / ' : '').$dept->displayName();
        }

        return implode(', ', $parts);
    }

    /**
     * Monogramm für die Kachel (max. 2 Initialen), z. B. "DokuWiki" -> "DW"
     *
     * Monogram for the tile (max. 2 initials), e.g. "DokuWiki" -> "DW"
     */
    public function monogram(): string
    {
        $words = preg_split('/[\s\-_\/]+/u', trim($this->name)) ?: [];
        $letters = collect($words)
            ->filter()
            ->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))
            ->take(2);

        if ($letters->count() < 2) {
            return mb_strtoupper(mb_substr($this->name, 0, 2));
        }

        return $letters->implode('');
    }

    /**
     * Deterministischer Farbton (0–359) aus dem Namen — pflegearm statt Icon-Upload
     *
     * Deterministic hue (0–359) from the name — low-maintenance instead of an icon upload
     */
    public function hue(): int
    {
        return hexdec(substr(md5(mb_strtolower($this->name)), 0, 4)) % 360;
    }
}
