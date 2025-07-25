<?php

namespace App\Http\Requests\Pump;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Tank;

class StorePumpRequest extends FormRequest
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
            'name' => 'required|string|max:255|unique:pumps,name',
            'description' => 'nullable|string|max:1000',
            'tank_id' => [
                'required',
                'integer',
                Rule::exists('tanks', 'id')->whereNull('deleted_at')
            ],
            'flow_rate' => 'nullable|numeric|min:0',
            'max_flow_rate' => 'nullable|numeric|min:0',
            'pressure' => 'nullable|numeric|min:0',
            'max_pressure' => 'nullable|numeric|min:0',
            'power_rating' => 'nullable|numeric|min:0',
            'power_consumption' => 'nullable|numeric|min:0',
            'voltage' => 'nullable|numeric|min:0',
            'current' => 'nullable|numeric|min:0',
            'efficiency' => 'nullable|numeric|between:0,100',
            'total_runtime' => 'nullable|integer|min:0',
            'status' => [
                'nullable',
                'string',
                Rule::in(['active', 'inactive', 'maintenance', 'error'])
            ],
            'manufacturer' => 'nullable|string|max:255',
            'model' => 'nullable|string|max:255',
            'serial_number' => 'nullable|string|max:255',
            'installation_date' => 'nullable|date',
            'last_maintenance_date' => 'nullable|date',
            'next_maintenance_date' => 'nullable|date|after_or_equal:today',
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
            'name.required' => 'The pump name is required.',
            'name.unique' => 'A pump with this name already exists.',
            'tank_id.required' => 'A tank must be selected for the pump.',
            'tank_id.exists' => 'The selected tank does not exist or has been deleted.',
            'flow_rate.numeric' => 'The flow rate must be a number.',
            'flow_rate.min' => 'The flow rate cannot be negative.',
            'max_flow_rate.numeric' => 'The maximum flow rate must be a number.',
            'max_flow_rate.min' => 'The maximum flow rate cannot be negative.',
            'pressure.numeric' => 'The pressure must be a number.',
            'pressure.min' => 'The pressure cannot be negative.',
            'max_pressure.numeric' => 'The maximum pressure must be a number.',
            'max_pressure.min' => 'The maximum pressure cannot be negative.',
            'power_rating.numeric' => 'The power rating must be a number.',
            'power_rating.min' => 'The power rating cannot be negative.',
            'power_consumption.numeric' => 'The power consumption must be a number.',
            'power_consumption.min' => 'The power consumption cannot be negative.',
            'voltage.numeric' => 'The voltage must be a number.',
            'voltage.min' => 'The voltage cannot be negative.',
            'current.numeric' => 'The current must be a number.',
            'current.min' => 'The current cannot be negative.',
            'efficiency.numeric' => 'The efficiency must be a number.',
            'efficiency.between' => 'The efficiency must be between 0 and 100.',
            'total_runtime.integer' => 'The total runtime must be an integer.',
            'total_runtime.min' => 'The total runtime cannot be negative.',
            'status.in' => 'The selected status is invalid.',
            'installation_date.date' => 'The installation date is not a valid date.',
            'last_maintenance_date.date' => 'The last maintenance date is not a valid date.',
            'next_maintenance_date.date' => 'The next maintenance date is not a valid date.',
            'next_maintenance_date.after_or_equal' => 'The next maintenance date must be today or in the future.',
            'metadata.array' => 'The metadata must be a valid JSON object.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        // Set default status if not provided
        if (!$this->has('status')) {
            $this->merge(['status' => 'inactive']);
        }
        
        // Ensure numeric fields are properly cast
        $numericFields = [
            'flow_rate', 'max_flow_rate', 'pressure', 'max_pressure',
            'power_rating', 'power_consumption', 'voltage', 'current',
            'efficiency', 'total_runtime'
        ];
        
        foreach ($numericFields as $field) {
            if ($this->has($field) && is_string($this->$field)) {
                $this->merge([
                    $field => (float) $this->$field
                ]);
            }
        }
    }
}
