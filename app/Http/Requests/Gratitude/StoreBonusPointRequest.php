<?php

namespace App\Http\Requests\Gratitude;

use Illuminate\Foundation\Http\FormRequest;

class StoreBonusPointRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'date'        => 'required|date',
            'description' => 'required|string',
            'points'      => 'required|numeric|min:1',
        ];
    }
}
