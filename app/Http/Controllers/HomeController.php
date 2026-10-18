<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\Area;
use Illuminate\Http\Request;

/**
 * Öffentliches Portal: Applikationen mit Filterung über
 * Bereiche (?area=…) und deren Abteilungen (?department=…). Alte
 * Lesezeichen mit ?bereich=…&abteilung=… (vor Standard 10) gelten weiter.
 *
 * Public portal: applications filtered by
 * areas (?area=…) and their departments (?department=…). Old bookmarks
 * with ?bereich=…&abteilung=… (before standard 10) keep working.
 */
class HomeController extends Controller
{
    public function index(Request $request)
    {
        $areas = Area::where('active', true)
            ->with(['departments' => fn ($q) => $q->where('active', true)])
            ->orderBy('sort_order')->orderBy('name')
            ->get();

        $activeArea = null;
        $activeDept = null;

        if ($slug = $request->query('area', $request->query('bereich'))) {
            $activeArea = $areas->firstWhere('slug', $slug);
        }
        if ($activeArea && ($deptSlug = $request->query('department', $request->query('abteilung')))) {
            $activeDept = $activeArea->departments->firstWhere('slug', $deptSlug);
        }

        $query = Application::with(['category', 'areas', 'departments'])
            ->where('active', true);

        if ($activeDept) {
            // Abteilung gewählt: nur direkt zugeordnete Applikationen
            // Department selected: only directly assigned applications
            $query->whereHas('departments', fn ($q) => $q->where('departments.id', $activeDept->id));
        } elseif ($activeArea) {
            // Bereich gewählt: direkt am Bereich ODER an einer seiner Abteilungen
            // Area selected: directly on the area OR on one of its departments
            $deptIds = $activeArea->departments->pluck('id');
            $query->where(function ($q) use ($activeArea, $deptIds) {
                $q->whereHas('areas', fn ($sub) => $sub->where('areas.id', $activeArea->id));
                if ($deptIds->isNotEmpty()) {
                    $q->orWhereHas('departments', fn ($sub) => $sub->whereIn('departments.id', $deptIds));
                }
            });
        }

        $applications = $query->orderBy('sort_order')->orderBy('name')->get();

        // Anzeige nach Kategorie gruppieren; Einträge ohne Kategorie ans Ende
        // Group the display by category; entries without a category go last
        $groups = $applications
            ->groupBy(fn (Application $app) => $app->category_id ?? 0)
            ->map(fn ($apps) => ['category' => $apps->first()->category, 'apps' => $apps])
            ->sortBy(fn ($group) => $group['category']
                ? [0, $group['category']->sort_order, $group['category']->name]
                : [1, PHP_INT_MAX, ''])
            ->values();

        return view('home', [
            'areas'      => $areas,
            'activeArea' => $activeArea,
            'activeDept' => $activeDept,
            'groups'     => $groups,
            'total'      => $applications->count(),
        ]);
    }
}
