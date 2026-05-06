<?php

namespace App\Http\Requests\Gratitude;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEarnedPointRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'earning_type' => 'nullable|string|in:journey,other',
            'date' => 'nullable|required_unless:earning_type,journey|date',
            'category' => 'required|string|max:255',
            'points' => 'required|numeric|min:1',
            'amount' => 'required|numeric|min:0',
            'description' => 'required|string',
            'journey_id' => 'nullable|required_if:earning_type,journey|integer',
            'journey_end_date' => 'nullable|required_if:earning_type,journey|date',
            'project_data' => 'nullable|array',
            'expires_at' => 'nullable|date',
        ];
    }
}
