<?php

namespace App\Http\Requests\Tank;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Tank;

class UpdateTankRequest extends FormRequest
{
    /**
     * The tank instance.
     *
     * @var Tank|null
     */
    protected $tank;

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        $this->tank = $this->route('tank');

        // Ensure current_level doesn't exceed capacity if both are provided
        if ($this->has('current_level') && $this->has('capacity')) {
            $this->merge([
                'current_level' => min($this->current_level, $this->capacity),
            ]);
        } elseif ($this->has('current_level') && $this->tank) {
            $capacity = $this->has('capacity') ? $this->capacity : $this->tank->capacity;
            $this->merge([
                'current_level' => min($this->current_level, $capacity),
            ]);
        } elseif ($this->has('capacity') && $this->tank) {
            $this->merge([
                'current_level' => min($this->tank->current_level, $this->capacity),
            ]);
        }
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Authorization is handled by the controller or middleware
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $tankId = $this->tank ? $this->tank->id : null;

        return [
            'name' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('tanks', 'name')->ignore($tankId),
            ],
            'description' => 'nullable|string|max:1000',
            'capacity' => 'sometimes|numeric|min:1|max:100000',
            'current_level' => 'nullable|numeric|min:0',
            'min_threshold' => 'nullable|numeric|min:0|lt:max_threshold',
            'max_threshold' => 'nullable|numeric|gt:min_threshold',
            'status' => ['nullable', 'string', Rule::in(['active', 'maintenance', 'inactive'])],
            'location' => 'nullable|string|max:255',
            'last_refilled' => 'nullable|date',
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
            'name.unique' => 'A tank with this name already exists.',
            'capacity.numeric' => 'The capacity must be a number.',
            'capacity.min' => 'The capacity must be at least 1.',
            'current_level.numeric' => 'The current level must be a number.',
            'current_level.min' => 'The current level cannot be negative.',
            'current_level.max' => 'The current level cannot exceed the tank capacity.',
            'min_threshold.lt' => 'The minimum threshold must be less than the maximum threshold.',
            'max_threshold.gt' => 'The maximum threshold must be greater than the minimum threshold.',
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
            if ($this->has('capacity') && $this->tank && 
                $this->tank->current_level > $this->capacity) {
                $validator->errors()->add(
                    'capacity', 
                    'The capacity must be greater than or equal to the current water level.'
                );
            }
        });
    }
}
