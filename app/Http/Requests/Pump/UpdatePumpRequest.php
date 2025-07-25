<?php

namespace App\Http\Requests\Pump;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Tank;
use App\Models\Pump;

class UpdatePumpRequest extends FormRequest
{
    /**
     * The pump instance.
     *
     * @var Pump|null
     */
    protected $pump;

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        $this->pump = $this->route('pump');
    }

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
        $pumpId = $this->pump ? $this->pump->id : null;
        
        return [
            'name' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('pumps', 'name')->ignore($pumpId)
            ],
            'description' => 'sometimes|nullable|string|max:1000',
            'tank_id' => [
                'sometimes',
                'integer',
                Rule::exists('tanks', 'id')->whereNull('deleted_at')
            ],
            'flow_rate' => 'sometimes|nullable|numeric|min:0',
            'max_flow_rate' => 'sometimes|nullable|numeric|min:0',
            'pressure' => 'sometimes|nullable|numeric|min:0',
            'max_pressure' => 'sometimes|nullable|numeric|min:0',
            'power_rating' => 'sometimes|nullable|numeric|min:0',
            'power_consumption' => 'sometimes|nullable|numeric|min:0',
            'voltage' => 'sometimes|nullable|numeric|min:0',
            'current' => 'sometimes|nullable|numeric|min:0',
            'efficiency' => 'sometimes|nullable|numeric|between:0,100',
            'total_runtime' => 'sometimes|nullable|integer|min:0',
            'status' => [
                'sometimes',
                'string',
                Rule::in(['active', 'inactive', 'maintenance', 'error'])
            ],
            'manufacturer' => 'sometimes|nullable|string|max:255',
            'model' => 'sometimes|nullable|string|max:255',
            'serial_number' => 'sometimes|nullable|string|max:255',
            'installation_date' => 'sometimes|nullable|date',
            'last_maintenance_date' => 'sometimes|nullable|date',
            'next_maintenance_date' => [
                'sometimes',
                'nullable',
                'date',
                function ($attribute, $value, $fail) {
                    if ($value && strtotime($value) < strtotime('today')) {
                        $fail('The next maintenance date must be today or in the future.');
                    }
                },
            ],
            'metadata' => 'sometimes|nullable|array',
            'is_running' => 'sometimes|boolean',
            'last_started_at' => 'sometimes|nullable|date',
            'last_stopped_at' => 'sometimes|nullable|date',
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
            'name.unique' => 'A pump with this name already exists.',
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
            'metadata.array' => 'The metadata must be a valid JSON object.',
            'is_running.boolean' => 'The is running field must be true or false.',
            'last_started_at.date' => 'The last started at is not a valid date.',
            'last_stopped_at.date' => 'The last stopped at is not a valid date.',
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Additional validation logic can be added here if needed
            if ($this->has('flow_rate') && $this->has('max_flow_rate') && 
                $this->flow_rate > $this->max_flow_rate) {
                $validator->errors()->add('flow_rate', 
                    'The flow rate cannot be greater than the maximum flow rate.');
            }
            
            if ($this->has('pressure') && $this->has('max_pressure') && 
                $this->pressure > $this->max_pressure) {
                $validator->errors()->add('pressure', 
                    'The pressure cannot be greater than the maximum pressure.');
            }
        });
    }
}
