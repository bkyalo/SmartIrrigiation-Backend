<?php

namespace App\Http\Requests\IrrigationEvent;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class StoreIrrigationEventRequest extends FormRequest
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
            'plot_id' => 'required|exists:plots,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'status' => [
                'required',
                Rule::in(['scheduled', 'in_progress', 'completed', 'cancelled', 'failed']),
            ],
            'start_time' => 'required|date|after_or_equal:now',
            'end_time' => 'required|date|after:start_time',
            'actual_start_time' => 'nullable|date|after_or_equal:start_time',
            'actual_end_time' => 'nullable|date|after:actual_start_time',
            'water_volume' => 'nullable|numeric|min:0',
            'water_flow_rate' => 'nullable|numeric|min:0',
            'valve_ids' => 'required|array|min:1',
            'valve_ids.*' => 'exists:valves,id',
            'metadata' => 'nullable|array',
            'notes' => 'nullable|string|max:2000',
            'is_recurring' => 'boolean',
            'recurrence_pattern' => [
                'nullable',
                'required_if:is_recurring,true',
                'string',
                Rule::in(['daily', 'weekly', 'monthly', 'custom']),
            ],
            'recurrence_interval' => [
                'nullable',
                'required_if:is_recurring,true',
                'integer',
                'min:1',
                'max:365',
            ],
            'recurrence_ends_at' => [
                'nullable',
                'required_if:is_recurring,true',
                'date',
                'after:start_time',
            ],
            'weather_conditions' => 'nullable|array',
            'weather_conditions.temperature' => 'nullable|numeric',
            'weather_conditions.humidity' => 'nullable|numeric|between:0,100',
            'weather_conditions.rainfall' => 'nullable|numeric|min:0',
            'weather_conditions.wind_speed' => 'nullable|numeric|min:0',
            'weather_conditions.conditions' => 'nullable|string|max:255',
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
            'plot_id.required' => 'The plot ID is required.',
            'plot_id.exists' => 'The selected plot does not exist.',
            'name.required' => 'The event name is required.',
            'status.required' => 'The status is required.',
            'status.in' => 'The selected status is invalid.',
            'start_time.required' => 'The start time is required.',
            'start_time.after_or_equal' => 'The start time must be a future date and time.',
            'end_time.required' => 'The end time is required.',
            'end_time.after' => 'The end time must be after the start time.',
            'valve_ids.required' => 'At least one valve must be selected.',
            'valve_ids.*.exists' => 'One or more selected valves are invalid.',
            'recurrence_pattern.required_if' => 'The recurrence pattern is required for recurring events.',
            'recurrence_interval.required_if' => 'The recurrence interval is required for recurring events.',
            'recurrence_ends_at.required_if' => 'The recurrence end date is required for recurring events.',
            'recurrence_ends_at.after' => 'The recurrence must end after the start time.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        // Ensure boolean fields are properly cast
        $this->merge([
            'is_recurring' => filter_var($this->is_recurring, FILTER_VALIDATE_BOOLEAN),
        ]);

        // If this is a recurring event, ensure the end time is set appropriately
        if ($this->is_recurring && $this->recurrence_ends_at) {
            $this->merge([
                'end_time' => min(
                    Carbon::parse($this->end_time),
                    Carbon::parse($this->recurrence_ends_at)
                )->toDateTimeString(),
            ]);
        }
    }
}
