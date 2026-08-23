<?php

namespace App\Http\Controllers;

use App\Services\ActivityLogService;
use Illuminate\Http\Request;

class MasterDataController extends Controller
{
    private array $types = [
        'branches' => ['model' => \App\Models\Branch::class, 'title' => 'branches', 'module' => 'branch', 'fields' => ['name', 'code', 'is_active', 'sort_order']],
        'services' => ['model' => \App\Models\Service::class, 'title' => 'services', 'module' => 'service', 'fields' => ['name', 'color', 'is_active', 'sort_order']],
        'sources' => ['model' => \App\Models\ComplaintSource::class, 'title' => 'sources', 'module' => 'source', 'fields' => ['name', 'color', 'is_active', 'sort_order']],
        'categories' => ['model' => \App\Models\ComplaintCategory::class, 'title' => 'categories', 'module' => 'category', 'fields' => ['name', 'color', 'is_active', 'sort_order']],
        'types' => ['model' => \App\Models\ComplaintType::class, 'title' => 'types', 'module' => 'type', 'fields' => ['name', 'color', 'is_active', 'sort_order']],
        'priorities' => ['model' => \App\Models\Priority::class, 'title' => 'priorities', 'module' => 'priority', 'fields' => ['name', 'color', 'level', 'is_active', 'sort_order']],
        'statuses' => ['model' => \App\Models\ComplaintStatus::class, 'title' => 'statuses', 'module' => 'status', 'fields' => ['name', 'color', 'is_active', 'sort_order']],
    ];

    private function config(string $type): array
    {
        abort_unless(isset($this->types[$type]), 404);
        return $this->types[$type];
    }

    private function authorizeAction(array $config, string $action): void
    {
        abort_unless(auth()->user()?->can($config['module'].'.'.$action), 403);
    }

    private function rules(array $config): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'color' => in_array('color', $config['fields'], true) ? ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'] : ['nullable', 'string', 'max:30'],
            'code' => ['nullable', 'string', 'max:50'],
            'level' => ['nullable', 'integer', 'min:0'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function index(string $type)
    {
        $config = $this->config($type);
        $this->authorizeAction($config, 'view');
        $items = ($config['model'])::orderBy('sort_order')->orderBy('name')->paginate(20);
        return view('master-data.index', compact('type', 'config', 'items'));
    }

    public function create(string $type)
    {
        $config = $this->config($type);
        $this->authorizeAction($config, 'create');
        return view('master-data.form', compact('type', 'config'));
    }

    public function store(Request $request, string $type)
    {
        $config = $this->config($type);
        $this->authorizeAction($config, 'create');
        $values = $request->validate($this->rules($config));
        $values['is_active'] = $request->boolean('is_active');
        $record = ($config['model'])::create($values);
        app(ActivityLogService::class)->record('master_data.created', $record, __('activity.master_data.created'));
        return redirect()->route('master.index', $type)->with('success', __('common.saved'));
    }

    public function edit(string $type, int $item)
    {
        $config = $this->config($type);
        $this->authorizeAction($config, 'update');
        $record = ($config['model'])::findOrFail($item);
        return view('master-data.form', compact('type', 'config', 'record'));
    }

    public function update(Request $request, string $type, int $item)
    {
        $config = $this->config($type);
        $this->authorizeAction($config, 'update');
        $record = ($config['model'])::findOrFail($item);
        $values = $request->validate($this->rules($config));
        $values['is_active'] = $request->boolean('is_active');
        $old = $record->only(array_keys($values));
        $record->update($values);
        app(ActivityLogService::class)->record('master_data.updated', $record, __('activity.master_data.updated'), $old, $record->only(array_keys($values)));
        return redirect()->route('master.index', $type)->with('success', __('common.saved'));
    }

    public function destroy(string $type, int $item)
    {
        $config = $this->config($type);
        $this->authorizeAction($config, 'delete');
        $record = ($config['model'])::findOrFail($item);
        $record->delete();
        app(ActivityLogService::class)->record('master_data.deleted', $record, __('activity.master_data.deleted'));
        return back()->with('success', __('common.deleted'));
    }
}
