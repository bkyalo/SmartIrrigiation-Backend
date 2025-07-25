<?php

namespace App\Http\Requests\SensorReading;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSensorReadingRequest extends FormRequest
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
            'sensor_id' => 'sometimes|required|exists:sensors,id',
            'value' => 'sometimes|required|numeric',
            'unit' => 'sometimes|required|string|max:20',
            'recorded_at' => 'sometimes|date',
            'battery_level' => 'nullable|integer|min:0|max:100',
            'signal_strength' => 'nullable|integer|min:0|max:100',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'elevation' => 'nullable|numeric',
            'accuracy' => 'nullable|numeric|min:0',
            'metadata' => 'nullable|array',
            'is_anomaly' => 'sometimes|boolean',
            'is_manually_entered' => 'sometimes|boolean',
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
            'sensor_id.required' => 'The sensor ID is required.',
            'sensor_id.exists' => 'The selected sensor does not exist.',
            'value.required' => 'The reading value is required.',
            'value.numeric' => 'The reading value must be a number.',
            'unit.required' => 'The unit of measurement is required.',
            'battery_level.between' => 'The battery level must be between 0 and 100 percent.',
            'signal_strength.between' => 'The signal strength must be between 0 and 100 percent.',
            'latitude.between' => 'The latitude must be between -90 and 90 degrees.',
            'longitude.between' => 'The longitude must be between -180 and 180 degrees.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        // Ensure metadata is properly formatted as an array
        if ($this->has('metadata') && is_string($this->metadata)) {
            $this->merge([
                'metadata' => json_decode($this->metadata, true) ?? [],
            ]);
        }
    }
}
