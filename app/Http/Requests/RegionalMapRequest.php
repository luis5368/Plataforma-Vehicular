<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class RegionalMapRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    } // Authorization is enforced by the regional route group.

    public function rules(): array
    {
        return [
            'south' => 'required|numeric|between:-90,90', 'north' => 'required|numeric|between:-90,90',
            'west' => 'required|numeric|between:-180,180', 'east' => 'required|numeric|between:-180,180',
            'zoom' => 'required|integer|between:0,18',
            'department' => 'nullable|string|max:100', 'municipality' => 'nullable|string|max:100', 'zone' => 'nullable|string|max:50',
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            if (! $validator->errors()->any() && ((float) $this->south >= (float) $this->north || (float) $this->west === (float) $this->east)) {
                $validator->errors()->add('bounds', 'El área geográfica debe tener altura y anchura válidas.');
            }
        }];
    }
}
