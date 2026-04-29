<?php

namespace App\Http\Requests\Gratitude;

use Illuminate\Foundation\Http\FormRequest;

class StoreRedemptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'points' => 'required|numeric|min:1',
            'amount' => 'nullable|numeric',
            'reason' => 'nullable|string',
            'redemption_type' => 'nullable|string|in:journey,partner,other',
            'journey_id' => 'nullable|integer|required_if:redemption_type,journey',
        ];
    }
}
