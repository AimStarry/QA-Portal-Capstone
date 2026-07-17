<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ResponsibleUnit;
use App\Models\Laboratory;
use App\Models\College;
use App\Models\Unit;

class AdminCategoryController extends Controller
{
    /**
     * Enforce QA Admin role on all actions.
     */
    private function enforceAdmin()
    {
        if (!auth()->check() || auth()->user()->usertype !== 'QA Admin') {
            abort(403, 'Unauthorized action. Only QA Admins can manage categories.');
        }
    }

    /**
     * List all Categories/Laboratories and Responsible Units.
     */
    public function index(Request $request)
    {
        $this->enforceAdmin();

        $responsibleUnits = ResponsibleUnit::with(['laboratories', 'college', 'unit'])->orderBy('name')->get();
        $laboratories = Laboratory::with('responsibleUnit')->orderBy('name')->get();

        $colleges = College::orderBy('name')->get();
        $units = Unit::orderBy('name')->get();

        return response()->json([
            'responsibleUnits' => $responsibleUnits,
            'laboratories' => $laboratories
        ]);
    }

    /**
     * Store a new Responsible Unit.
     */
    public function storeUnit(Request $request)
    {
        $this->enforceAdmin();

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:responsible_units,name',
            'code' => 'nullable|string|max:50',
            'college_id' => 'nullable|exists:colleges,college_id',
            'unit_id' => 'nullable|exists:units,unit_id',
        ]);

        $ru = ResponsibleUnit::create($validated);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Responsible Unit created successfully.',
                'data' => $ru
            ]);
        }

        return redirect()->route('admin.categories.index')->with('success', 'Responsible Unit created successfully.');
    }

    /**
     * Delete a Responsible Unit.
     */
    public function destroyUnit(Request $request, $id)
    {
        $this->enforceAdmin();

        $unit = ResponsibleUnit::findOrFail($id);
        $unit->delete();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Responsible Unit deleted successfully.'
            ]);
        }

        return redirect()->route('admin.categories.index')->with('success', 'Responsible Unit deleted successfully.');
    }

    /**
     * Store a new Category/Laboratory.
     */
    public function storeCategory(Request $request)
    {
        $this->enforceAdmin();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'responsible_unit_id' => 'required|exists:responsible_units,responsible_unit_id',
        ]);

        $exists = Laboratory::where('name', $validated['name'])
            ->where('responsible_unit_id', $validated['responsible_unit_id'])
            ->exists();

        if ($exists) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This category/laboratory already exists under the selected unit.'
                ], 422);
            }
            return back()->withErrors(['name' => 'This category/laboratory already exists under the selected unit.']);
        }

        $lab = Laboratory::create($validated);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Category / Laboratory created successfully.',
                'data' => $lab
            ]);
        }

        return redirect()->route('admin.categories.index')->with('success', 'Category / Laboratory created successfully.');
    }

    /**
     * Delete a Category/Laboratory.
     */
    public function destroyCategory(Request $request, $id)
    {
        $this->enforceAdmin();

        $lab = Laboratory::findOrFail($id);
        $lab->delete();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Category / Laboratory deleted successfully.'
            ]);
        }

        return redirect()->route('admin.categories.index')->with('success', 'Category / Laboratory deleted successfully.');
    }
}

