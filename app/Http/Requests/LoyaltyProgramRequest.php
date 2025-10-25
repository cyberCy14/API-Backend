<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoyaltyProgramRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'company_id'     => 'required|exists:companies,id',
            'program_name'   => 'required|string|max:255',
            'description'    => 'nullable|string',
            'program_type'   => 'required|string|max:100',
            'is_active'      => 'boolean',
            'start_date'     => 'nullable|date',
            'end_date'       => 'nullable|date|after_or_equal:start_date',
            'instructions'   => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'company_id.required'   => 'Company ID is required.',
            'company_id.exists'     => 'The selected company does not exist.',
            'program_name.required' => 'Program name is required.',
            'program_name.max'      => 'Program name may not be greater than 255 characters.',
            'program_type.required' => 'Program type is required.',
            'program_type.max'      => 'Program type may not be greater than 100 characters.',
            'is_active.boolean'     => 'Is active must be true or false.',
            'start_date.date'       => 'Start date must be a valid date.',
            'end_date.date'         => 'End date must be a valid date.',
            'end_date.after_or_equal' => 'End date must be a date after or equal to start date.',
        ];
    }
}