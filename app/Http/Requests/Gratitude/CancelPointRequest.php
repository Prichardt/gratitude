<?php

namespace App\Http\Requests\Gratitude;

use Illuminate\Foundation\Http\FormRequest;

class CancelPointRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'date'                 => 'required|date',
            'cancellation_reason'  => 'required|string',
            'cancellation_points'  => 'required|numeric|min:1',
            'earned_point_id'      => 'nullable|integer|exists:earned_points,id',
            'bonus_point_id'       => 'nullable|integer|exists:bonus_points,id',
        ];
    }
}
