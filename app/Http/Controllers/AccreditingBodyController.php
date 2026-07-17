<?php

namespace App\Http\Controllers;

use App\Models\AccreditingBody;
use Illuminate\Http\Request;

class AccreditingBodyController extends Controller
{
    /**
     * Enforce QA Admin role.
     */
    private function enforceAdmin()
    {
        if (!auth()->check() || auth()->user()->usertype !== 'QA Admin') {
            abort(403, 'Unauthorized action. Only QA Admins can manage accrediting bodies.');
        }
    }

    /**
     * Store a newly created accrediting body in storage.
     */
    public function store(Request $request)
    {
        $this->enforceAdmin();

        $validated = $request->validate([
            'name' => 'required|string|unique:accrediting_bodies,name|max:255',
            'code' => 'required|string|unique:accrediting_bodies,code|max:50',
            'type' => 'required|string|in:Local,International,Regulatory',
            'description' => 'nullable|string',
            'areas' => 'nullable|array',
            'areas.*' => 'nullable|string|max:255',
        ]);

        // Standardize the acronym/code to uppercase
        $validated['code'] = strtoupper(trim($validated['code']));

        // Filter out empty string/null values from areas array
        if ($request->has('areas')) {
            $validated['areas'] = array_values(array_filter(array_map('trim', $request->input('areas'))));
        } else {
            $validated['areas'] = [];
        }

        AccreditingBody::create($validated);

        return redirect()->back()->with('success', 'Accrediting Body created successfully.');
    }
}
