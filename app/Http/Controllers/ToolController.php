<?php

namespace App\Http\Controllers;

use App\Exports\ToolsExport;
use App\Models\Tool;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class ToolController extends Controller
{
    public function export()
    {
        return Excel::download(new ToolsExport, 'tools-'.now()->format('Y-m-d').'.xlsx');
    }

    public function index()
    {
        return view('dashboard.tools.index');
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['sort_order'] = (Tool::max('sort_order') ?? 0) + 1;

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('tools', 'public');
        }

        Tool::create($data);

        return $this->modalOk($request, 'dashboard.tools', 'Tool added.');
    }

    public function edit(Request $request, Tool $tool)
    {
        if ($request->ajax()) {
            return view('dashboard.tools.partials.form', ['model' => $tool]);
        }

        return view('dashboard.tools.edit', compact('tool'));
    }

    public function update(Request $request, Tool $tool)
    {
        $data = $this->validated($request, $tool);

        if ($request->hasFile('image')) {
            if ($tool->image) {
                Storage::disk('public')->delete($tool->image);
            }
            $data['image'] = $request->file('image')->store('tools', 'public');
        }

        $tool->update($data);

        return $this->modalOk($request, 'dashboard.tools', 'Tool updated.');
    }

    public function destroy(Tool $tool)
    {
        if ($tool->image) {
            Storage::disk('public')->delete($tool->image);
        }

        $tool->delete();

        return redirect()->route('dashboard.tools')->with('status', 'Tool deleted.');
    }

    private function validated(Request $request, ?Tool $tool = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('tools', 'name')->ignore($tool?->id)],
            'category' => ['required', 'string', 'max:100'],
            'blurb' => ['nullable', 'string', 'max:255'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);
    }
}
