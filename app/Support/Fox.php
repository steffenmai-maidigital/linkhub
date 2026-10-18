<?php

namespace App\Support;

use App\Models\Setting;
use App\Models\User;

/**
 * Rua, der Fuchs-Helfer (gemeinsamer Baustein, Standard 03; Figur:
 * docs/RUA.md): ein Wikinger-Fuchs aus einem norwegischen Fjorddorf,
 * den die Iren in Dyflin „den Roten“ nannten. Er duzt, spricht knapp und
 * warm, höchstens ein Bild vom Meer je Hinweis; bei Ernstem (Passwörter,
 * Verzeichnis, Audit-Log) ohne Bilder und ohne Witz. Er begrüßt, gibt Tipps
 * passend zu Rolle und Seite und wirbt auf der Anmeldeseite für freie
 * Software. Alle Texte laufen durch __(), damit er jede Sprache spricht.
 * Zentral abschaltbar (Einstellung fox_enabled), je Person unter „Hilfe“.
 *
 * Rua, the fox helper (shared building block, Standard 03; character:
 * docs/RUA.md): a Viking fox from a Norwegian fjord village whom the
 * Irish in Dyflin called "the red one". He speaks informally, short and warm,
 * at most one sea image per hint; on serious matters (passwords, directory,
 * audit log) without images or jokes. He greets, gives tips matching the role
 * and page, and promotes free software on the sign-in page. All texts go
 * through __(), so he speaks every language.
 * Can be switched off centrally (setting fox_enabled), per person under "Help".
 */
class Fox
{
    /** Soll der Fuchs auf der aktuellen Seite erscheinen? / Should the fox appear on the current page? */
    public static function visible(): bool
    {
        if (Setting::get('fox_enabled', '1') !== '1') {
            return false;
        }
        if ($u = auth()->user()) {
            return $u->showsFox();
        }

        return request()->routeIs('login');
    }

    /**
     * Daten für fox.js — angemeldet oder Gast auf der Anmeldeseite
     *
     * Data for fox.js — signed in, or a guest on the sign-in page
     */
    public static function display(): array
    {
        $u = auth()->user();

        return $u ? self::data($u, request()->route()?->getName(), (bool) session('ok')) : self::guest();
    }

    /** Anmeldeseite: freie Software, Hilfe beim Anmelden / Sign-in page: free software, help with signing in */
    public static function guest(): array
    {
        $promotion = [
            __('Gut zu wissen: LinkHub ist freie Software. Deine Firma darf es kostenlos nutzen, anpassen und weitergeben.'),
            __('Alle Daten bleiben auf eurem eigenen Server. Keine Cloud, kein Tracking, keine fremden Dienste.'),
            __('Offener Quellcode heißt: Jeder kann nachsehen, was mit den Daten geschieht. Was ich sage, kannst du prüfen.'),
            __('Keine Lizenzkosten, kein Kleingedrucktes — und kein Anbieter, der dir einfach den Hafen dichtmacht.'),
            __('Freie Software lebt vom Mitmachen: Fehlermeldungen und Verbesserungen sind an Bord willkommen.'),
            __('LinkHub spricht Deutsch und Englisch — und lernt gern weitere Sprachen.'),
        ];
        $source = ProductInfo::sourceUrl();

        return self::package(
            greeting: self::greeting(null),
            context: [$promotion[array_rand($promotion)]],
            tips: array_merge($promotion, [
                __('Das Portal selbst braucht keine Anmeldung. Anmelden müssen sich nur Manager und Administratoren, die es pflegen.'),
                __('Melde dich mit deinem Firmenkonto an — oder mit dem Konto, das dir der Administrator eingerichtet hat.'),
                __('Dein Passwort kennt nur du. Gib es niemandem weiter, auch mir nicht.'),
            ]),
            sayings: [],
            praise: null,
            link: $source ? ['text' => __('Zum Quellcode'), 'url' => $source] : null,
        );
    }

    public static function data(User $u, ?string $route, bool $praised): array
    {
        $name = trim(explode(' ', (string) $u->name)[0] ?: $u->username);

        $sayings = [
            __('Ein gutes Portal spart jeden Tag ein paar Klicks. Der kürzeste Weg ist der beste.'),
            __('Kurze Beschreibungen lesen sich besser als lange. Kein Satz zu viel.'),
            __('Ordnung in Bereichen und Abteilungen ist wie eine gute Seekarte: Dann findet jeder seine Anwendungen.'),
            __('Fragen kostet nichts: Die Hilfe findest du unten im Footer.'),
            __('Ich bin Rua. Daheim in einem Fjorddorf in Norwegen, getauft in Dyflin: Die Iren nannten mich wegen meines Fells „den Roten“.'),
        ];

        $tips = [
            __('Tipp: Auf dem Portal filtert die Leiste oben nach Bereich und Abteilung; die Suche rechts findet nach Name und Beschreibung.'),
            __('Tipp: Der Hell/Dunkel-Schalter (◐) sitzt oben rechts, daneben die Sprache.'),
            __('Tipp: Unter „Hilfe“ findest du Version, Handbücher und die Änderungshistorie.'),
        ];
        if ($u->canEdit()) {
            $tips[] = __('Tipp: Unter „Applikationen“ legst du Einträge an. Ohne Zuordnung erscheinen sie nur unter „Alle“.');
            $tips[] = __('Tipp: Unter „Struktur“ pflegst du Bereiche, Abteilungen und Kategorien — englische Namen sind freiwillig.');
            $tips[] = __('Tipp: Ein Handbuch-Link je Applikation erscheint als „Manual“ auf der Kachel.');
        }
        if ($u->is_admin) {
            $tips[] = __('Tipp: Unter „Benutzer“ legst du eigene Konten an — auch ohne Active Directory.');
            $tips[] = __('Tipp: Einstellungen → Anmeldung verbindet LinkHub mit eurem Verzeichnis; „Verbindung testen“ prüft die Angaben.');
            $tips[] = __('Tipp: Logo und Farben deiner Firma stellst du unter Einstellungen → Firma ein.');
            $tips[] = __('Tipp: Das Audit-Log ist eine Hash-Kette. „Hash-Kette prüfen“ zeigt, ob alles unverändert ist.');
        }

        $context = match ($route) {
            'home'               => [__('Das ist das Portal, wie es alle sehen. Zum Pflegen geht\'s oben über „Applikationen“ und „Struktur“.')],
            'applications.index' => [__('Hier pflegst du die Anwendungen. „Neu anlegen“ sitzt oben links; wer was sieht, steuern Bereiche und Abteilungen.')],
            'applications.create', 'applications.edit' => [__('Name, Adresse und Kategorie genügen. Englischer Name und englische Beschreibung sind freiwillig — leichtes Gepäck.')],
            'structure.index'    => [__('Bereiche enthalten Abteilungen, Kategorien gruppieren die Kacheln auf dem Portal.')],
            'settings.index'     => [__('Die Abschnitte sind nummeriert. Unter „Anmeldung“ schaltest du das Verzeichnis ein oder aus.')],
            'users.index'        => [__('Eigene Konten brauchen kein Verzeichnis. Ein Startpasswort wird nur einmal angezeigt — gib es nur der Person, der das Konto gehört.')],
            'audit.index'        => [__('Jede Änderung steht hier, gesichert mit einer Hash-Kette gegen nachträgliche Manipulation.')],
            'account'            => [__('Hier kannst du mich auch dauerhaft ausblenden. Ich nehm\'s dir nicht übel.')],
            'help'               => [__('Version, Handbücher und Änderungshistorie — und ganz unten der kürzeste Weg zum Support.')],
            default              => [],
        };

        $praise = [__('Skål! Erledigt.'), __('Gespeichert, fest wie ein Runenstein.'), __('Das hat geklappt.'), __('Sauber. Weiter geht die Fahrt.')];

        return self::package(
            greeting: self::greeting($name),
            context: $context,
            tips: $tips,
            sayings: $sayings,
            // nach Ernstem (Löschen, Passwort …) schlicht, sonst mit Bild – siehe FoxOccasion
            // after serious actions (deleting, password …) plain, otherwise with an image – see FoxOccasion
            praise: $praised ? (session('fox_occasion') === 'serious' ? __('Erledigt.') : $praise[array_rand($praise)]) : null,
            link: null,
        );
    }

    private static function greeting(?string $name): string
    {
        $hour = (int) now()->format('G');
        if ($name === null || $name === '') {
            return match (true) {
                $hour < 11 => __('Guten Morgen! Ich bin Rua, ein Fuchs aus einem Fjorddorf in Norwegen. Die Iren nannten mich „den Roten“.'),
                $hour < 18 => __('Heil dir! Ich bin Rua, ein Fuchs aus einem Fjorddorf in Norwegen. Die Iren nannten mich „den Roten“.'),
                default    => __('Guten Abend! Ich bin Rua, ein Fuchs aus einem Fjorddorf in Norwegen. Die Iren nannten mich „den Roten“.'),
            };
        }

        return match (true) {
            $hour < 11 => __('Guten Morgen, :name! Wohin soll die Fahrt heute gehen?', ['name' => $name]),
            $hour < 18 => __('Heil dir, :name! Willkommen zurück im Hafen.', ['name' => $name]),
            default    => __('Guten Abend, :name! Noch etwas zu pflegen, bevor der Hafen schläft?', ['name' => $name]),
        };
    }

    private static function package(string $greeting, array $context, array $tips, array $sayings, ?string $praise, ?array $link): array
    {
        return [
            'editor'   => null,
            'book'     => null,
            'greeting' => $greeting,
            'context'  => array_values($context),
            'tips'     => $tips,
            'sayings'  => $sayings,
            'praise'   => $praise,
            'link'     => $link,
            // Fehler und Warten (fox.js) / errors and waiting (fox.js)
            'error'    => self::errorLine(),
            'waiting'  => [__('Die Ruder sind im Wasser …'), __('Einen Moment noch, gleich legen wir an.')],
            'texts'    => [
                'close' => __('Schließen'),
                'sleep' => __('Schlafen legen'),
                'wake'  => __('Rua schläft — antippen zum Wecken'),
                'woken' => __('Gähn … ausgeschlafen. Danke fürs Wecken, ich bin wieder an Deck.'),
                'next'  => __('Noch ein Tipp'),
                'fox'   => __('Rua, der Fuchs: antippen für einen Tipp'),
            ],
        ];
    }

    /**
     * Rua zu einer Fehlermeldung: Kam die Seite mit Validierungsfehlern zurück,
     * ein Satz dazu. Auf Anmelde-, Konto-, Passwort- und Zwei-Faktor-Seiten ohne
     * Bild und ohne Witz (docs/RUA.md), sonst Gegenwind statt Drama.
     * Die Routennamen gibt es in LinkHub (php artisan route:list).
     *
     * Rua on an error message: if the page came back with validation errors,
     * one line about it. On sign-in, account, password and two-factor pages
     * without images or jokes (docs/RUA.md), otherwise headwind, not drama.
     * These route names exist in LinkHub (php artisan route:list).
     */
    private static function errorLine(): ?string
    {
        $errors = session('errors');
        if (! $errors || ! $errors->any()) {
            return null;
        }
        if (request()->routeIs('login', 'login.*', 'account', 'account.*', 'users.password', 'users.2fa')) {
            return __('Das hat nicht gepasst. Prüf deine Angaben und versuch es noch einmal.');
        }
        $lines = [__('Gegenwind. Versuch es nochmal, ich bleib an Deck.'), __('Da fehlt noch etwas. Schau dir das markierte Feld an.')];

        return $lines[array_rand($lines)];
    }
}
