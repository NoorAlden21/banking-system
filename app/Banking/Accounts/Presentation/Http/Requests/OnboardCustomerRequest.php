<?php

namespace App\Banking\Accounts\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OnboardCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'customer' => ['required', 'array'],
            'customer.name' => ['required', 'string', 'max:255'],
            'customer.email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'customer.phone' => ['nullable', 'string', 'max:30', 'unique:users,phone'],

            'accounts' => ['required', 'array', 'min:1'],
            'accounts.*.type' => ['required', 'in:savings,checking,loan,investment', 'distinct'],
            'accounts.*.daily_limit' => ['nullable', 'numeric', 'min:0'],
            'accounts.*.monthly_limit' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
