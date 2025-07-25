<?php

namespace App\Http\Requests\Plot;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Valve;

class StorePlotRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization is handled by the controller or middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:plots,name',
            'description' => 'nullable|string|max:1000',
            'location' => 'nullable|string|max:255',
            'area' => 'nullable|numeric|min:0',
            'area_unit' => 'nullable|string|in:m²,ft²,acre,hectare',
            'crop_type' => 'nullable|string|max:100',
            'planting_date' => 'nullable|date',
            'soil_type' => 'nullable|string|max:100',
            'optimal_moisture_min' => 'nullable|numeric|min:0|max:100|lt:optimal_moisture_max',
            'optimal_moisture_max' => 'nullable|numeric|min:0|max:100|gt:optimal_moisture_min',
            'irrigation_method' => 'nullable|string|in:drip,sprinkler,flood,manual',
            'valve_ids' => 'nullable|array',
            'valve_ids.*' => [
                'integer',
                Rule::exists('valves', 'id')->where(function ($query) {
                    $query->whereNull('deleted_at');
                })
            ],
            'status' => 'nullable|string|in:active,fallow,maintenance',
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
            'name.required' => 'The plot name is required.',
            'name.unique' => 'A plot with this name already exists.',
            'area.numeric' => 'The area must be a number.',
            'area.min' => 'The area cannot be negative.',
            'optimal_moisture_min.lt' => 'The minimum moisture must be less than the maximum moisture.',
            'optimal_moisture_max.gt' => 'The maximum moisture must be greater than the minimum moisture.',
            'valve_ids.*.exists' => 'One or more selected valves do not exist or have been deleted.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        // Ensure optimal moisture values are set together
        if ($this->has('optimal_moisture_min') && !$this->has('optimal_moisture_max')) {
            $this->merge(['optimal_moisture_max' => $this->optimal_moisture_min + 10]);
        } elseif (!$this->has('optimal_moisture_min') && $this->has('optimal_moisture_max')) {
            $this->merge(['optimal_moisture_min' => max(0, $this->optimal_moisture_max - 10)]);
        }

        // Set default status if not provided
        if (!$this->has('status')) {
            $this->merge(['status' => 'active']);
        }
    }
}
