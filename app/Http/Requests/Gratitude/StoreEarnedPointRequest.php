<?php

namespace App\Http\Requests\Gratitude;

use Illuminate\Foundation\Http\FormRequest;

class StoreEarnedPointRequest extends FormRequest
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
            'journey_id'  => 'nullable|integer',
        ];
    }
}
