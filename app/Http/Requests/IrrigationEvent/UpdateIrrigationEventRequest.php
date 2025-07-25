<?php

namespace App\Http\Requests\IrrigationEvent;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class UpdateIrrigationEventRequest extends FormRequest
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
        $irrigationEvent = $this->route('irrigationEvent');
        
        return [
            'plot_id' => 'sometimes|exists:plots,id',
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string|max:1000',
            'status' => [
                'sometimes',
                Rule::in(['scheduled', 'in_progress', 'completed', 'cancelled', 'failed']),
            ],
            'start_time' => [
                'sometimes',
                'date',
                function ($attribute, $value, $fail) use ($irrigationEvent) {
                    if ($irrigationEvent->status === 'completed' || $irrigationEvent->status === 'cancelled') {
                        $fail("Cannot update start time of a {$irrigationEvent->status} event.");
                    }
                    
                    if (Carbon::parse($value)->lt(now()) && $this->input('status') !== 'in_progress') {
                        $fail('The start time must be in the future.');
                    }
                },
            ],
            'end_time' => 'sometimes|date|after:start_time',
            'actual_start_time' => [
                'nullable',
                'date',
                function ($attribute, $value, $fail) use ($irrigationEvent) {
                    if ($value && $irrigationEvent->status === 'scheduled') {
                        $fail('Cannot set actual start time for a scheduled event. Change status to in_progress first.');
                    }
                    
                    if ($value && $this->has('start_time') && Carbon::parse($value)->lt(Carbon::parse($this->input('start_time')))) {
                        $fail('The actual start time cannot be before the scheduled start time.');
                    }
                },
            ],
            'actual_end_time' => [
                'nullable',
                'date',
                function ($attribute, $value, $fail) use ($irrigationEvent) {
                    if ($value && $irrigationEvent->status !== 'completed' && $irrigationEvent->status !== 'cancelled') {
                        $fail('Cannot set actual end time unless the event is completed or cancelled.');
                    }
                    
                    if ($value && $this->has('actual_start_time') && Carbon::parse($value)->lt(Carbon::parse($this->input('actual_start_time')))) {
                        $fail('The actual end time must be after the actual start time.');
                    }
                },
            ],
            'water_volume' => 'nullable|numeric|min:0',
            'water_flow_rate' => 'nullable|numeric|min:0',
            'valve_ids' => 'sometimes|array|min:1',
            'valve_ids.*' => 'exists:valves,id',
            'metadata' => 'nullable|array',
            'notes' => 'nullable|string|max:2000',
            'is_recurring' => 'sometimes|boolean',
            'recurrence_pattern' => [
                'nullable',
                'required_with:is_recurring',
                'string',
                Rule::in(['daily', 'weekly', 'monthly', 'custom']),
            ],
            'recurrence_interval' => [
                'nullable',
                'required_with:is_recurring',
                'integer',
                'min:1',
                'max:365',
            ],
            'recurrence_ends_at' => [
                'nullable',
                'required_with:is_recurring',
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
            'plot_id.exists' => 'The selected plot does not exist.',
            'end_time.after' => 'The end time must be after the start time.',
            'valve_ids.required' => 'At least one valve must be selected.',
            'valve_ids.*.exists' => 'One or more selected valves are invalid.',
            'recurrence_pattern.required_with' => 'The recurrence pattern is required for recurring events.',
            'recurrence_interval.required_with' => 'The recurrence interval is required for recurring events.',
            'recurrence_ends_at.required_with' => 'The recurrence end date is required for recurring events.',
            'recurrence_ends_at.after' => 'The recurrence must end after the start time.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        // Ensure boolean fields are properly cast
        if ($this->has('is_recurring')) {
            $this->merge([
                'is_recurring' => filter_var($this->is_recurring, FILTER_VALIDATE_BOOLEAN),
            ]);
        }

        // If this is a recurring event, ensure the end time is set appropriately
        if ($this->is_recurring && $this->recurrence_ends_at) {
            $endTime = $this->has('end_time') ? $this->end_time : $this->route('irrigationEvent')->end_time;
            
            $this->merge([
                'end_time' => min(
                    Carbon::parse($endTime),
                    Carbon::parse($this->recurrence_ends_at)
                )->toDateTimeString(),
            ]);
        }
    }
}
