<?php

namespace App\Http\Requests\ApprovalRequest;

use App\Models\ApprovalRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreApprovalRequestRequest extends FormRequest
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
            'action_type' => [
                'required',
                'string',
                Rule::in([
                    ApprovalRequest::ACTION_IRRIGATION,
                    ApprovalRequest::ACTION_VALVE_CONTROL,
                    ApprovalRequest::ACTION_PUMP_CONTROL,
                    ApprovalRequest::ACTION_SCHEDULE_UPDATE,
                    ApprovalRequest::ACTION_SYSTEM_CONFIG,
                    ApprovalRequest::ACTION_MAINTENANCE,
                    ApprovalRequest::ACTION_OTHER,
                ]),
            ],
            'action_parameters' => ['required', 'array'],
            'priority' => [
                'required',
                'string',
                Rule::in([
                    ApprovalRequest::PRIORITY_LOW,
                    ApprovalRequest::PRIORITY_NORMAL,
                    ApprovalRequest::PRIORITY_HIGH,
                    ApprovalRequest::PRIORITY_CRITICAL,
                ]),
            ],
            'request_notes' => ['nullable', 'string', 'max:1000'],
            'expires_at' => ['nullable', 'date', 'after:now'],
            'approvable_type' => ['required_with:approvable_id', 'string'],
            'approvable_id' => ['required_with:approvable_type', 'integer'],
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
            'action_type.required' => 'The action type is required.',
            'action_type.in' => 'The selected action type is invalid.',
            'action_parameters.required' => 'The action parameters are required.',
            'action_parameters.array' => 'The action parameters must be an array.',
            'priority.required' => 'The priority is required.',
            'priority.in' => 'The selected priority is invalid.',
            'request_notes.max' => 'The request notes may not be greater than 1000 characters.',
            'expires_at.after' => 'The expiration date must be in the future.',
            'approvable_type.required_with' => 'The approvable type is required when approvable ID is present.',
            'approvable_id.required_with' => 'The approvable ID is required when approvable type is present.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        $this->merge([
            'action_parameters' => $this->input('action_parameters', []),
            'metadata' => $this->input('metadata', []),
            'priority' => $this->input('priority', ApprovalRequest::PRIORITY_NORMAL),
        ]);
    }
}
