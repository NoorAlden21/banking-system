<?php

namespace App\Banking\Transactions\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class DepositExternalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'account_public_id' => ['required', 'uuid'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'description' => ['nullable', 'string', 'max:255'],

            'gateway' => ['nullable', 'in:card,wire,legacy'],

            'payment_token' => ['required', 'string', 'max:200'],
        ];
    }
}
