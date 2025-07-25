<?php

namespace App\Http\Requests\Schedule;

use App\Models\Schedule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class StoreScheduleRequest extends FormRequest
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
            'plot_id' => ['required', 'exists:plots,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'start_time' => ['required', 'date', 'after_or_equal:now'],
            'duration_minutes' => ['required', 'integer', 'min:1', 'max:1440'], // Max 24 hours
            'frequency' => ['required', 'string', Rule::in([
                Schedule::FREQUENCY_DAILY,
                Schedule::FREQUENCY_WEEKLY,
                Schedule::FREQUENCY_MONTHLY,
                Schedule::FREQUENCY_CUSTOM,
            ])],
            'frequency_params' => ['nullable', 'array'],
            'frequency_params.days_of_week' => ['required_if:frequency,' . Schedule::FREQUENCY_WEEKLY, 'array'],
            'frequency_params.days_of_week.*' => ['integer', 'min:0', 'max:6'], // 0 (Sunday) to 6 (Saturday)
            'frequency_params.day_of_month' => ['required_if:frequency,' . Schedule::FREQUENCY_MONTHLY, 'integer', 'min:1', 'max:31'],
            'frequency_params.interval' => ['required_if:frequency,' . Schedule::FREQUENCY_CUSTOM, 'integer', 'min:1'],
            'frequency_params.unit' => ['required_if:frequency,' . Schedule::FREQUENCY_CUSTOM, 'string', Rule::in(['days', 'weeks', 'months'])],
            'is_active' => ['boolean'],
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
            'plot_id.required' => 'The plot ID is required.',
            'plot_id.exists' => 'The selected plot does not exist.',
            'start_time.after_or_equal' => 'The start time must be a date in the future.',
            'duration_minutes.max' => 'The duration cannot exceed 24 hours (1440 minutes).',
            'frequency.in' => 'The selected frequency is invalid.',
            'frequency_params.days_of_week.required_if' => 'Days of week are required for weekly frequency.',
            'frequency_params.day_of_month.required_if' => 'Day of month is required for monthly frequency.',
            'frequency_params.interval.required_if' => 'Interval is required for custom frequency.',
            'frequency_params.unit.required_if' => 'Unit is required for custom frequency.',
            'end_date.after' => 'The end date must be after the start time.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        $this->merge([
            'is_active' => $this->boolean('is_active', true),
            'frequency_params' => $this->input('frequency_params', []),
            'metadata' => $this->input('metadata', []),
        ]);
    }
}
