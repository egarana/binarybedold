<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUnitRequest extends FormRequest
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
            'vendor.value' => ['nullable', 'exists:vendors,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:units,slug'],
            'description' => ['nullable', 'string'],
            'qty' => ['required', 'integer', 'min:0'],
            'type' => ['nullable', 'string', 'max:255'],
            'size' => ['nullable', 'integer', 'min:0'],
            'bed_size' => ['nullable', 'string', 'max:255'],
            'view' => ['nullable', 'string', 'max:255'],
            'occupancy' => ['nullable', 'integer', 'min:1'],
            'free_breakfast' => ['boolean'],
            'features' => ['nullable', 'array'],
        ];
    }

    public function attributes(): array
    {
        return [
            'vendor.value' => 'vendor',
        ];
    }
}
