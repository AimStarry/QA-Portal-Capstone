<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UnitController extends Controller
{
    /**
     * Enforce access control: only QA Admin and Heads of Units can manage units.
     */
    private function enforceAccess()
    {
        if (!auth()->check() || !in_array(auth()->user()->usertype, ['QA Admin', 'Head of Unit'])) {
            abort(403, 'Unauthorized action. Only QA Admins and Heads of Units can manage offices/units.');
        }
    }

    /**
     * Store a newly created office/unit in storage.
     */
    public function store(Request $request)
    {
        $this->enforceAccess();
        $validated = $request->validate([
            'name' => 'required|string|unique:units,name|max:255',
            'code' => 'nullable|string|max:50',
            'logo' => 'nullable|image|mimes:jpg,jpeg,png,gif,svg,webp|max:10240',
        ], [
            'logo.uploaded' => 'The logo file is too large or failed to upload. The maximum allowed file size is 10MB (10240 KB). Please compress your image or select a smaller file.',
        ]);

        if ($request->hasFile('logo')) {
            $validated['logo'] = $request->file('logo')->store('logos/units', 'public');
        }

        $unit = Unit::create($validated);

        // Sync corresponding ResponsibleUnit entry for compliance task assignment dropdowns
        \App\Models\ResponsibleUnit::updateOrCreate(
            ['unit_id' => $unit->unit_id],
            ['name' => $unit->name, 'code' => $unit->code]
        );

        return redirect()->back()->with('success', 'Office/Unit created successfully.');
    }

    /**
     * Update the specified office/unit in storage.
     */
    public function update(Request $request, Unit $unit)
    {
        $this->enforceAccess();
        $validated = $request->validate([
            'name'    => ['required', 'string', 'max:255', \Illuminate\Validation\Rule::unique('units', 'name')->ignore($unit->unit_id, 'unit_id')],
            'code'    => 'nullable|string|max:50',
            'head_id' => 'nullable|exists:users,id',
            'logo'    => 'nullable|image|mimes:jpg,jpeg,png,gif,svg,webp|max:10240',
        ], [
            'logo.uploaded' => 'The logo file is too large or failed to upload. The maximum allowed file size is 10MB (10240 KB). Please compress your image or select a smaller file.',
        ]);

        // Handle logo upload
        if ($request->hasFile('logo')) {
            if ($unit->logo) {
                Storage::disk('public')->delete($unit->logo);
            }
            $validated['logo'] = $request->file('logo')->store('logos/units', 'public');
        }

        // Separate head_id since it's not a direct column on the Unit model
        $headId = $validated['head_id'] ?? null;
        unset($validated['head_id']);

        $unit->update($validated);

        // Sync corresponding ResponsibleUnit entry
        \App\Models\ResponsibleUnit::updateOrCreate(
            ['unit_id' => $unit->unit_id],
            ['name' => $unit->name, 'code' => $unit->code]
        );

        // Clear existing Head of Unit mapping for this unit
        \App\Models\User::where('usertype', 'Head of Unit')
            ->where('unit_id', $unit->unit_id)
            ->update(['unit_id' => null]);

        // Assign the new Head of Unit
        if ($headId) {
            \App\Models\User::where('id', $headId)
                ->where('usertype', 'Head of Unit')
                ->update(['unit_id' => $unit->unit_id]);
        }

        return redirect()->back()->with('success', 'Office/Unit updated successfully.');
    }

    /**
     * Remove the specified office/unit from storage.
     */
    public function destroy(Unit $unit)
    {
        $this->enforceAccess();
        if ($unit->logo) {
            Storage::disk('public')->delete($unit->logo);
        }
        \App\Models\ResponsibleUnit::where('unit_id', $unit->unit_id)->delete();
        $unit->delete();

        return redirect()->back()->with('success', 'Office/Unit deleted successfully.');
    }
}
