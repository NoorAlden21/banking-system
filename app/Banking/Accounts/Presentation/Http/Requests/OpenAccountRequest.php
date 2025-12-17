<?php

namespace App\Banking\Accounts\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OpenAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'in:savings,checking,loan,investment'],
            'daily_limit' => ['nullable', 'numeric', 'min:0'],
            'monthly_limit' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
