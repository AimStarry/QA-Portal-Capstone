<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAccreditationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->usertype === 'QA Admin';
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'program_id' => [
                'required',
                Rule::exists('programs', 'program_id')->where(function ($query) {
                    $query->where('is_accreditable', true);
                }),
            ],
            'accrediting_body' => 'required|string|max:255',
            'type' => ['required', Rule::in(['Local', 'International', 'Regulatory'])],
            'level_or_tier' => 'nullable|string|max:255',
            'last_visit' => 'nullable|date',
            'expiry_date' => 'nullable|date',
            'status' => ['required', Rule::in(['Active', 'Expiring Soon', 'Expired', 'Pending'])],
        ];
    }

    /**
     * Get the custom validation error messages.
     */
    public function messages(): array
    {
        return [
            'program_id.exists' => 'This program is marked as non-accreditable (compliance items are deficient). It cannot undergo review or receive accreditation status.',
        ];
    }
}
