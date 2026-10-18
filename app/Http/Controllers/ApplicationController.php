<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\Area;
use App\Models\Category;
use Illuminate\Http\Request;

class ApplicationController extends Controller
{
    public function index()
    {
        $items = Application::with(['category', 'areas', 'departments.area'])
            ->orderBy('name')
            ->paginate(50);

        return view('applications.index', compact('items'));
    }

    public function create()
    {
        return $this->form(new Application(['active' => true, 'sort_order' => 0]));
    }

    public function edit(Application $application)
    {
        return $this->form($application);
    }

    private function form(Application $item)
    {
        return view('applications.form', [
            'item'       => $item,
            'categories' => Category::orderBy('sort_order')->orderBy('name')->get(),
            'areas'      => Area::with('departments')->orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $item = new Application($this->validated($request));
        $item->save();
        $this->syncVisibility($item, $request);

        return redirect()->route('applications.index')->with('ok', __('Applikation „:name“ angelegt.', ['name' => $item->name]));
    }

    public function update(Request $request, Application $application)
    {
        $application->update($this->validated($request));
        $this->syncVisibility($application, $request);

        return redirect()->route('applications.index')->with('ok', __('Applikation „:name“ gespeichert.', ['name' => $application->name]));
    }

    public function destroy(Application $application)
    {
        $name = $application->name;
        $application->delete();

        return redirect()->route('applications.index')->with('ok', __('Applikation „:name“ gelöscht.', ['name' => $name]));
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:120'],
            'name_en'     => ['nullable', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'description_en' => ['nullable', 'string', 'max:1000'],
            'url'         => ['required', 'url', 'max:500'],
            'manual_url'  => ['nullable', 'url', 'max:500'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'sort_order'  => ['nullable', 'integer', 'min:0', 'max:65000'],
        ]);

        $data['sort_order'] = $data['sort_order'] ?? 0;
        $data['active'] = $request->boolean('active');

        return $data;
    }

    private function syncVisibility(Application $item, Request $request): void
    {
        $areas = collect($request->input('areas', []))->map(fn ($v) => (int) $v)->all();
        $departments = collect($request->input('departments', []))->map(fn ($v) => (int) $v)->all();

        $item->areas()->sync($areas);
        $item->departments()->sync($departments);
    }
}
