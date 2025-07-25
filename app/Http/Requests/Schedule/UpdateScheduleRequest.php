<?php

namespace App\Http\Requests\Schedule;

use App\Models\Schedule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class UpdateScheduleRequest extends FormRequest
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
        $schedule = $this->route('schedule');
        
        return [
            'plot_id' => ['sometimes', 'exists:plots,id'],
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'start_time' => [
                'sometimes', 
                'date', 
                function ($attribute, $value, $fail) use ($schedule) {
                    if ($schedule->status !== Schedule::STATUS_ACTIVE) {
                        $fail('Cannot update start time of a non-active schedule.');
                    }
                }
            ],
            'duration_minutes' => ['sometimes', 'integer', 'min:1', 'max:1440'], // Max 24 hours
            'frequency' => [
                'sometimes', 
                'string', 
                Rule::in([
                    Schedule::FREQUENCY_DAILY,
                    Schedule::FREQUENCY_WEEKLY,
                    Schedule::FREQUENCY_MONTHLY,
                    Schedule::FREQUENCY_CUSTOM,
                ])
            ],
            'frequency_params' => ['nullable', 'array'],
            'frequency_params.days_of_week' => ['required_if:frequency,' . Schedule::FREQUENCY_WEEKLY, 'array'],
            'frequency_params.days_of_week.*' => ['integer', 'min:0', 'max:6'],
            'frequency_params.day_of_month' => ['required_if:frequency,' . Schedule::FREQUENCY_MONTHLY, 'integer', 'min:1', 'max:31'],
            'frequency_params.interval' => ['required_if:frequency,' . Schedule::FREQUENCY_CUSTOM, 'integer', 'min:1'],
            'frequency_params.unit' => ['required_if:frequency,' . Schedule::FREQUENCY_CUSTOM, 'string', Rule::in(['days', 'weeks', 'months'])],
            'is_active' => ['boolean'],
            'status' => [
                'sometimes',
                'string',
                Rule::in([
                    Schedule::STATUS_ACTIVE,
                    Schedule::STATUS_PAUSED,
                    Schedule::STATUS_COMPLETED,
                ]),
                function ($attribute, $value, $fail) use ($schedule) {
                    // Prevent status changes from completed
                    if ($schedule->status === Schedule::STATUS_COMPLETED && $value !== Schedule::STATUS_COMPLETED) {
                        $fail('Cannot change status from completed.');
                    }
                }
            ],
            'end_date' => ['nullable', 'date', 'after:start_time'],
            'metadata' => ['nullable', 'array'],
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
            'start_time.after_or_equal' => 'The start time must be a date in the future.',
            'duration_minutes.max' => 'The duration cannot exceed 24 hours (1440 minutes).',
            'frequency.in' => 'The selected frequency is invalid.',
            'frequency_params.days_of_week.required_if' => 'Days of week are required for weekly frequency.',
            'frequency_params.day_of_month.required_if' => 'Day of month is required for monthly frequency.',
            'frequency_params.interval.required_if' => 'Interval is required for custom frequency.',
            'frequency_params.unit.required_if' => 'Unit is required for custom frequency.',
            'status.in' => 'The selected status is invalid.',
            'end_date.after' => 'The end date must be after the start time.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        $this->merge([
            'is_active' => $this->boolean('is_active', $this->is_active ?? true),
            'frequency_params' => $this->input('frequency_params', []),
            'metadata' => $this->input('metadata', $this->metadata ?? []),
        ]);
    }
}
