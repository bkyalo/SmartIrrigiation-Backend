<?php

namespace App\Http\Requests\Valve;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Tank;
use App\Models\Plot;

class StoreValveRequest extends FormRequest
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
            'name' => 'required|string|max:255|unique:valves,name',
            'description' => 'nullable|string|max:1000',
            'tank_id' => [
                'required',
                'integer',
                Rule::exists('tanks', 'id')->whereNull('deleted_at')
            ],
            'plot_id' => [
                'nullable',
                'integer',
                Rule::exists('plots', 'id')->whereNull('deleted_at')
            ],
            'flow_rate' => 'nullable|numeric|min:0',
            'is_manual_override' => 'boolean',
            'status' => [
                'nullable',
                'string',
                Rule::in(['active', 'inactive', 'maintenance', 'error'])
            ],
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
            'name.required' => 'The valve name is required.',
            'name.unique' => 'A valve with this name already exists.',
            'tank_id.required' => 'A tank must be selected for the valve.',
            'tank_id.exists' => 'The selected tank does not exist or has been deleted.',
            'plot_id.exists' => 'The selected plot does not exist or has been deleted.',
            'flow_rate.numeric' => 'The flow rate must be a number.',
            'flow_rate.min' => 'The flow rate cannot be negative.',
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

        // Ensure is_manual_override is a boolean
        if (!$this->has('is_manual_override')) {
            $this->merge(['is_manual_override' => false]);
        }
    }
}
