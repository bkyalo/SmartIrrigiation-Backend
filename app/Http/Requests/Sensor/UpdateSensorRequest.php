<?php

namespace App\Http\Requests\Sensor;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSensorRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled at controller/middleware level
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('sensors')
                    ->where('sensor_type_id', $this->input('sensor_type_id', $this->sensor->sensor_type_id ?? null))
                    ->ignore($this->sensor->id)
            ],
            'sensor_type_id' => 'sometimes|required|exists:sensor_types,id',
            'plot_id' => 'nullable|exists:plots,id',
            'location' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'elevation' => 'nullable|numeric|min:0',
            'installation_date' => 'nullable|date',
            'last_maintenance_date' => 'nullable|date|after_or_equal:installation_date',
            'battery_level' => 'nullable|integer|min:0|max:100',
            'battery_voltage' => 'nullable|numeric|min:0',
            'signal_strength' => 'nullable|integer|min:0|max:100',
            'update_interval' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
            'metadata' => 'nullable|array',
            'notes' => 'nullable|string',
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
            'name.unique' => 'A sensor with this name already exists for the selected sensor type.',
            'sensor_type_id.exists' => 'The selected sensor type is invalid.',
            'plot_id.exists' => 'The selected plot is invalid.',
            'last_maintenance_date.after_or_equal' => 'The last maintenance date must be after or equal to the installation date.',
            'battery_level.between' => 'The battery level must be between 0 and 100 percent.',
            'signal_strength.between' => 'The signal strength must be between 0 and 100 percent.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        if ($this->has('metadata') && is_string($this->metadata)) {
            $this->merge([
                'metadata' => json_decode($this->metadata, true),
            ]);
        }
    }
}
