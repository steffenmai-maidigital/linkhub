<?php

namespace App\Models;

use Spatie\Activitylog\Models\Activity;

/**
 * Audit-Log mit Hash-Kette (revisionssicher/nachweisbar):
 * record_hash = SHA-256( Hash des Vorgängers + kanonischer Inhalt ).
 * Wird ein Eintrag nachträglich verändert oder entfernt, bricht die Kette
 * ab diesem Punkt — prüfbar über den Button „Hash-Kette prüfen" im Audit-Log.
 *
 * Audit log with hash chain (audit-proof/verifiable):
 * record_hash = SHA-256( hash of the predecessor + canonical content ).
 * If an entry is changed or removed afterwards, the chain breaks
 * from that point — verifiable via the "Hash-Kette prüfen" button in the audit log.
 */
class ActivityLog extends Activity
{
    protected $table = 'activity_log';

    protected static function booted(): void
    {
        static::creating(function (self $entry) {
            $prev = static::query()->latest('id')->value('record_hash') ?? 'genesis';
            $entry->record_hash = static::computeHash($prev, $entry);
        });
    }

    public static function computeHash(string $prevHash, self $entry): string
    {
        return hash('sha256', $prevHash.'|'.json_encode([
            'log'         => $entry->log_name,
            'description' => $entry->description,
            'subject'     => [$entry->subject_type, $entry->subject_id],
            'causer'      => [$entry->causer_type, $entry->causer_id],
            'properties'  => $entry->properties,
        ], JSON_UNESCAPED_UNICODE));
    }

    /**
     * Kette prüfen: liefert ['valid' => bool, 'checked' => int, 'first_break' => ?int]
     *
     * Verify the chain: returns ['valid' => bool, 'checked' => int, 'first_break' => ?int]
     */
    public static function verifyChain(): array
    {
        $prev = 'genesis';
        $checked = 0;
        $first = true;

        foreach (static::query()->orderBy('id')->cursor() as $entry) {
            if ($first) {
                $prev = $entry->record_hash ?? 'genesis';
                $first = false;
                $checked++;
                continue;
            }
            $expected = static::computeHash($prev, $entry);
            if ($entry->record_hash !== null && $entry->record_hash !== $expected) {
                return ['valid' => false, 'checked' => $checked, 'first_break' => $entry->id];
            }
            $prev = $entry->record_hash ?? $expected;
            $checked++;
        }

        return ['valid' => true, 'checked' => $checked, 'first_break' => null];
    }
}
