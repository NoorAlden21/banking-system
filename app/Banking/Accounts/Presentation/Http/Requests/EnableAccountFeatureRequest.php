<?php

namespace App\Banking\Accounts\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class EnableAccountFeatureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'feature_key' => ['required', 'string', 'max:50', 'in:overdraft,premium,insurance'],
            'meta' => ['nullable', 'array'],

            'meta.limit' => ['nullable', 'numeric', 'min:0'],
            'meta.fee_rate_percent' => ['nullable', 'numeric', 'min:0'],
            'meta.monthly_fee' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function featureKey(): string
    {
        return (string) $this->input('feature_key');
    }
    public function meta(): array
    {
        return (array) ($this->input('meta') ?? []);
    }
}
