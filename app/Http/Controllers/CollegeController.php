<?php

namespace App\Http\Controllers;

use App\Models\College;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class CollegeController extends Controller
{
    /**
     * Enforce QA Admin role.
     */
    private function enforceAdmin()
    {
        if (!auth()->check() || auth()->user()->usertype !== 'QA Admin') {
            abort(403, 'Unauthorized action. Only QA Admins can manage schools/colleges.');
        }
    }

    /**
     * Store a newly created school/college in storage.
     */
    public function store(Request $request)
    {
        $this->enforceAdmin();
        if ($request->hasFile('logo') || isset($_FILES['logo'])) {
            $file = $request->file('logo');
            if ($file) {
                if (!$file->isValid()) {
                    Log::error('Store College logo upload failed: ' . $file->getErrorMessage() . ' (Code: ' . $file->getError() . ')');
                } else {
                    Log::info('Store College logo upload is valid: ' . $file->getClientOriginalName() . ' size: ' . $file->getSize() . ' mime: ' . $file->getMimeType());
                }
            } else {
                Log::error('Store College logo is null in Laravel. PHP error code: ' . ($_FILES['logo']['error'] ?? 'no file in _FILES'));
            }
        }

        $validated = $request->validate([
            'name'        => 'required|string|unique:colleges,name|max:255',
            'code'        => 'nullable|string|max:50',
            'former_name' => 'nullable|string|max:255',
            'logo'        => 'nullable|image|mimes:jpg,jpeg,png,gif,svg,webp|max:10240',
        ], [
            'logo.uploaded' => 'The logo file is too large or failed to upload. The maximum allowed file size is 10MB (10240 KB). Please compress your image or select a smaller file.',
        ]);

        if ($request->hasFile('logo')) {
            $validated['logo'] = $request->file('logo')->store('logos/colleges', 'public');
        }

        College::create($validated);

        return redirect()->back()->with('success', 'School/College created successfully.');
    }

    /**
     * Update the specified school/college in storage.
     * If the name changes and former_name is empty, the previous name is preserved in former_name.
     */
    public function update(Request $request, College $college)
    {
        $this->enforceAdmin();
        if ($request->hasFile('logo') || isset($_FILES['logo'])) {
            $file = $request->file('logo');
            if ($file) {
                if (!$file->isValid()) {
                    Log::error('Update College logo upload failed: ' . $file->getErrorMessage() . ' (Code: ' . $file->getError() . ')');
                } else {
                    Log::info('Update College logo upload is valid: ' . $file->getClientOriginalName() . ' size: ' . $file->getSize() . ' mime: ' . $file->getMimeType());
                }
            } else {
                Log::error('Update College logo is null in Laravel. PHP error code: ' . ($_FILES['logo']['error'] ?? 'no file in _FILES'));
            }
        }

        $validated = $request->validate([
            'name'        => 'required|string|max:255|unique:colleges,name,' . $college->college_id . ',college_id',
            'code'        => 'nullable|string|max:50',
            'former_name' => 'nullable|string|max:255',
            'dean_id'     => 'nullable|exists:users,id',
            'logo'        => 'nullable|image|mimes:jpg,jpeg,png,gif,svg,webp|max:10240',
        ], [
            'logo.uploaded' => 'The logo file is too large or failed to upload. The maximum allowed file size is 10MB (10240 KB). Please compress your image or select a smaller file.',
        ]);

        if ($validated['name'] !== $college->name && empty($validated['former_name'])) {
            $validated['former_name'] = $college->name;
        }

        // Handle logo upload
        if ($request->hasFile('logo')) {
            // Delete old logo if it exists
            if ($college->logo) {
                Storage::disk('public')->delete($college->logo);
            }
            $validated['logo'] = $request->file('logo')->store('logos/colleges', 'public');
        }

        // Separate dean_id since it's not a direct column on the College model
        $deanId = $validated['dean_id'] ?? null;
        unset($validated['dean_id']);

        $college->update($validated);

        // Clear existing Dean/Principal mapping for this college
        \App\Models\User::whereIn('usertype', ['Dean', 'Principal'])
            ->where('college_id', $college->college_id)
            ->update(['college_id' => null]);

        // Assign the new Dean or Principal
        if ($deanId) {
            \App\Models\User::where('id', $deanId)
                ->whereIn('usertype', ['Dean', 'Principal'])
                ->update(['college_id' => $college->college_id]);
        }

        return redirect()->back()->with('success', 'School/College updated successfully.');
    }
}
