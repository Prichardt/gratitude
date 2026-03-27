<?php

namespace App\Http\Requests\Gratitude;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEarnedPointRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'date'        => 'required|date',
            'category'    => 'required|string|max:255',
            'points'      => 'required|numeric|min:1',
            'amount'      => 'required|numeric|min:0',
            'description' => 'required|string',
            'expires_at'  => 'nullable|date',
        ];
    }
}
