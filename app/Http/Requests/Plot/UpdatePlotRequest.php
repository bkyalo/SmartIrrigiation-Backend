<?php

namespace App\Http\Requests\Plot;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Valve;
use App\Models\Plot;

class UpdatePlotRequest extends FormRequest
{
    /**
     * The plot instance.
     *
     * @var Plot|null
     */
    protected $plot;

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        $this->plot = $this->route('plot');
        
        // If optimal_moisture_min is being updated but not max, set a default max
        if ($this->has('optimal_moisture_min') && !$this->has('optimal_moisture_max')) {
            $max = $this->plot ? 
                max($this->optimal_moisture_min + 10, $this->plot->optimal_moisture_max) : 
                $this->optimal_moisture_min + 10;
            $this->merge(['optimal_moisture_max' => $max]);
        }
        
        // If optimal_moisture_max is being updated but not min, set a default min
        if ($this->has('optimal_moisture_max') && !$this->has('optimal_moisture_min')) {
            $min = $this->plot ? 
                min($this->optimal_moisture_max - 10, $this->plot->optimal_moisture_min) : 
                max(0, $this->optimal_moisture_max - 10);
            $this->merge(['optimal_moisture_min' => $min]);
        }
    }

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
        $plotId = $this->plot ? $this->plot->id : null;
        
        return [
            'name' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('plots', 'name')->ignore($plotId),
            ],
            'description' => 'nullable|string|max:1000',
            'location' => 'nullable|string|max:255',
            'area' => 'nullable|numeric|min:0',
            'area_unit' => 'nullable|string|in:m²,ft²,acre,hectare',
            'crop_type' => 'nullable|string|max:100',
            'planting_date' => 'nullable|date',
            'soil_type' => 'nullable|string|max:100',
            'optimal_moisture_min' => 'nullable|numeric|min:0|max:100|lt:optimal_moisture_max',
            'optimal_moisture_max' => 'nullable|numeric|min:0|max:100|gt:optimal_moisture_min',
            'irrigation_method' => 'nullable|string|in:drip,sprinkler,flood,manual',
            'valve_ids' => 'sometimes|array',
            'valve_ids.*' => [
                'integer',
                Rule::exists('valves', 'id')->where(function ($query) {
                    $query->whereNull('deleted_at');
                })
            ],
            'status' => 'sometimes|string|in:active,fallow,maintenance',
            'metadata' => 'sometimes|array',
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
            'name.unique' => 'A plot with this name already exists.',
            'area.numeric' => 'The area must be a number.',
            'area.min' => 'The area cannot be negative.',
            'optimal_moisture_min.lt' => 'The minimum moisture must be less than the maximum moisture.',
            'optimal_moisture_max.gt' => 'The maximum moisture must be greater than the minimum moisture.',
            'valve_ids.*.exists' => 'One or more selected valves do not exist or have been deleted.',
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
            if ($this->has('optimal_moisture_min') && $this->has('optimal_moisture_max') && 
                $this->optimal_moisture_min >= $this->optimal_moisture_max) {
                $validator->errors()->add(
                    'optimal_moisture_min', 
                    'The minimum moisture must be less than the maximum moisture.'
                );
            }
        });
    }
}
