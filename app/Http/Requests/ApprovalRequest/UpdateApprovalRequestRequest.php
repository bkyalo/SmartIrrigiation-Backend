<?php

namespace App\Http\Requests\ApprovalRequest;

use App\Models\ApprovalRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateApprovalRequestRequest extends FormRequest
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
        $approvalRequest = $this->route('approvalRequest');
        
        return [
            'action_type' => [
                'sometimes',
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
                function ($attribute, $value, $fail) use ($approvalRequest) {
                    if ($approvalRequest->status !== ApprovalRequest::STATUS_PENDING) {
                        $fail('Action type cannot be changed after the request has been processed.');
                    }
                },
            ],
            'action_parameters' => [
                'sometimes',
                'array',
                function ($attribute, $value, $fail) use ($approvalRequest) {
                    if ($approvalRequest->status !== ApprovalRequest::STATUS_PENDING) {
                        $fail('Action parameters cannot be changed after the request has been processed.');
                    }
                },
            ],
            'priority' => [
                'sometimes',
                'string',
                Rule::in([
                    ApprovalRequest::PRIORITY_LOW,
                    ApprovalRequest::PRIORITY_NORMAL,
                    ApprovalRequest::PRIORITY_HIGH,
                    ApprovalRequest::PRIORITY_CRITICAL,
                ]),
            ],
            'request_notes' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'response_notes' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'expires_at' => [
                'sometimes',
                'nullable',
                'date',
                'after:now',
                function ($attribute, $value, $fail) use ($approvalRequest) {
                    if ($approvalRequest->status !== ApprovalRequest::STATUS_PENDING) {
                        $fail('Expiration date cannot be changed after the request has been processed.');
                    }
                },
            ],
            'status' => [
                'sometimes',
                'string',
                Rule::in([
                    ApprovalRequest::STATUS_PENDING,
                    ApprovalRequest::STATUS_APPROVED,
                    ApprovalRequest::STATUS_REJECTED,
                    ApprovalRequest::STATUS_EXPIRED,
                    ApprovalRequest::STATUS_CANCELLED,
                ]),
                function ($attribute, $value, $fail) use ($approvalRequest) {
                    // Only allow status changes from pending to other statuses
                    if ($approvalRequest->status !== ApprovalRequest::STATUS_PENDING && 
                        $value !== $approvalRequest->status) {
                        $fail('Status can only be changed from pending to another status.');
                    }
                },
            ],
            'metadata' => ['sometimes', 'array'],
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
            'action_type.in' => 'The selected action type is invalid.',
            'action_parameters.array' => 'The action parameters must be an array.',
            'priority.in' => 'The selected priority is invalid.',
            'request_notes.max' => 'The request notes may not be greater than 1000 characters.',
            'response_notes.max' => 'The response notes may not be greater than 1000 characters.',
            'expires_at.after' => 'The expiration date must be in the future.',
            'status.in' => 'The selected status is invalid.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        $this->merge([
            'action_parameters' => $this->input('action_parameters', []),
            'metadata' => $this->input('metadata', $this->metadata ?? []),
        ]);
    }
}
