<?php

namespace App\Http\Requests\Valve;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Tank;
use App\Models\Plot;
use App\Models\Valve;

class UpdateValveRequest extends FormRequest
{
    /**
     * The valve instance.
     *
     * @var Valve|null
     */
    protected $valve;

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        $this->valve = $this->route('valve');
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
        $valveId = $this->valve ? $this->valve->id : null;
        
        return [
            'name' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('valves', 'name')->ignore($valveId)
            ],
            'description' => 'nullable|string|max:1000',
            'tank_id' => [
                'sometimes',
                'integer',
                Rule::exists('tanks', 'id')->whereNull('deleted_at')
            ],
            'plot_id' => [
                'nullable',
                'integer',
                Rule::exists('plots', 'id')->whereNull('deleted_at')
            ],
            'flow_rate' => 'sometimes|nullable|numeric|min:0',
            'is_manual_override' => 'sometimes|boolean',
            'status' => [
                'sometimes',
                'string',
                Rule::in(['active', 'inactive', 'maintenance', 'error'])
            ],
            'metadata' => 'sometimes|nullable|array',
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
            'name.unique' => 'A valve with this name already exists.',
            'tank_id.exists' => 'The selected tank does not exist or has been deleted.',
            'plot_id.exists' => 'The selected plot does not exist or has been deleted.',
            'flow_rate.numeric' => 'The flow rate must be a number.',
            'flow_rate.min' => 'The flow rate cannot be negative.',
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
        });
    }
}
