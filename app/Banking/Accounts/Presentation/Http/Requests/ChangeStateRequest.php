<?php

namespace App\Banking\Accounts\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChangeStateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'state' => ['required', 'in:active,frozen,suspended,closed'],
        ];
    }
}
