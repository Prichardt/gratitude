<?php

namespace App\Http\Requests\Gratitude;

use Illuminate\Foundation\Http\FormRequest;

class StoreBonusPointRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'type'        => 'nullable|string|in:referring_guest,other',
            'category'    => 'nullable|string|max:255',
            'date'        => 'nullable|required_unless:type,referring_guest|date',
            'description' => 'required|string',
            'points'      => 'required|numeric|min:1',
            'amount'      => 'nullable|numeric|min:0',
            'guest_id'    => 'nullable',
            'guest_name'  => 'nullable|string|max:255',
            'journey_id'  => 'nullable|required_if:type,referring_guest|integer',
            'journey_end_date' => 'nullable|required_if:type,referring_guest|date',
            'journey_data' => 'nullable|array',
        ];
    }
}
