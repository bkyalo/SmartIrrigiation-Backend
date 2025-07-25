<?php

namespace App\Http\Requests\Tank;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTankRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Authorization is handled by the controller or middleware
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
            'name' => 'required|string|max:255|unique:tanks,name',
            'description' => 'nullable|string|max:1000',
            'capacity' => 'required|numeric|min:1|max:100000',
            'current_level' => 'nullable|numeric|min:0|max:100000',
            'min_threshold' => 'nullable|numeric|min:0|lt:max_threshold',
            'max_threshold' => 'nullable|numeric|gt:min_threshold',
            'status' => ['nullable', 'string', Rule::in(['active', 'maintenance', 'inactive'])],
            'location' => 'nullable|string|max:255',
            'last_refilled' => 'nullable|date',
            'metadata' => 'nullable|array',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'The tank name is required.',
            'name.unique' => 'A tank with this name already exists.',
            'capacity.required' => 'The tank capacity is required.',
            'capacity.numeric' => 'The capacity must be a number.',
            'capacity.min' => 'The capacity must be at least 1.',
            'current_level.numeric' => 'The current level must be a number.',
            'current_level.min' => 'The current level cannot be negative.',
            'current_level.max' => 'The current level cannot exceed the tank capacity.',
            'min_threshold.lt' => 'The minimum threshold must be less than the maximum threshold.',
            'max_threshold.gt' => 'The maximum threshold must be greater than the minimum threshold.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        // Ensure current_level doesn't exceed capacity if both are provided
        if ($this->has('current_level') && $this->has('capacity')) {
            $this->merge([
                'current_level' => min($this->current_level, $this->capacity),
            ]);
        }

        // Set default status if not provided
        if (!$this->has('status')) {
            $this->merge([
                'status' => 'active',
            ]);
        }
    }
}
