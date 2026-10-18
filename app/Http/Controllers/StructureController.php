<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Category;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Struktur-Pflege mit Reitern (Bereiche · Abteilungen · Kategorien) —
 * Inline-Bearbeitung nach dem Muster der Organisations-Seite.
 *
 * Structure maintenance with tabs (areas · departments · categories) —
 * inline editing following the pattern of the organisation page.
 */
class StructureController extends Controller
{
    private const TYPES = ['areas', 'departments', 'categories'];

    public function index()
    {
        return view('structure.index', [
            'areas'      => Area::with('departments')->orderBy('sort_order')->orderBy('name')->get(),
            'categories' => Category::withCount('applications')->orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, string $type)
    {
        abort_unless(in_array($type, self::TYPES), 404);

        match ($type) {
            'areas' => Area::create($this->areaData($request) + [
                'slug' => $this->uniqueSlug(Area::query(), $request->input('name')),
            ]),
            'departments' => $this->storeDepartment($request),
            'categories' => Category::create($this->categoryData($request)),
        };

        return back()->with('ok', __('Eintrag angelegt.'));
    }

    public function update(Request $request, string $type, int $id)
    {
        abort_unless(in_array($type, self::TYPES), 404);

        match ($type) {
            'areas' => tap(Area::findOrFail($id), function (Area $area) use ($request) {
                $data = $this->areaData($request);
                if ($data['name'] !== $area->name) {
                    $data['slug'] = $this->uniqueSlug(Area::whereKeyNot($area->id), $data['name']);
                }
                $area->update($data);
            }),
            'departments' => tap(Department::findOrFail($id), function (Department $dept) use ($request) {
                $data = $this->departmentData($request);
                if ($data['name'] !== $dept->name || (int) $data['area_id'] !== (int) $dept->area_id) {
                    $data['slug'] = $this->uniqueSlug(
                        Department::where('area_id', $data['area_id'])->whereKeyNot($dept->id),
                        $data['name']
                    );
                }
                $dept->update($data);
            }),
            'categories' => Category::findOrFail($id)->update($this->categoryData($request, $id)),
        };

        return back()->with('ok', __('Änderungen gespeichert.'));
    }

    public function destroy(string $type, int $id)
    {
        abort_unless(in_array($type, self::TYPES), 404);

        match ($type) {
            'areas'       => Area::findOrFail($id)->delete(),
            'departments' => Department::findOrFail($id)->delete(),
            'categories'  => Category::findOrFail($id)->delete(),
        };

        return back()->with('ok', __('Eintrag gelöscht.'));
    }

    private function storeDepartment(Request $request): void
    {
        $data = $this->departmentData($request);
        $data['slug'] = $this->uniqueSlug(Department::where('area_id', $data['area_id']), $data['name']);
        Department::create($data);
    }

    private function areaData(Request $request): array
    {
        $data = $request->validate([
            'name'       => ['required', 'string', 'max:100'],
            'name_en'    => ['nullable', 'string', 'max:100'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65000'],
        ]);

        return [
            'name'       => $data['name'],
            'name_en'    => $data['name_en'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
            'active'     => $request->boolean('active'),
        ];
    }

    private function departmentData(Request $request): array
    {
        $data = $request->validate([
            'area_id'    => ['required', 'exists:areas,id'],
            'name'       => ['required', 'string', 'max:100'],
            'name_en'    => ['nullable', 'string', 'max:100'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65000'],
        ]);

        return [
            'area_id'    => $data['area_id'],
            'name'       => $data['name'],
            'name_en'    => $data['name_en'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
            'active'     => $request->boolean('active'),
        ];
    }

    private function categoryData(Request $request, ?int $ignoreId = null): array
    {
        $data = $request->validate([
            'name'       => ['required', 'string', 'max:100', 'unique:categories,name'.($ignoreId ? ','.$ignoreId : '')],
            'name_en'    => ['nullable', 'string', 'max:100'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65000'],
        ]);

        return [
            'name'       => $data['name'],
            'name_en'    => $data['name_en'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
        ];
    }

    private function uniqueSlug($query, string $name): string
    {
        $base = Str::slug($name) ?: 'entry';
        $slug = $base;
        $i = 2;

        while ((clone $query)->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
