<?php

namespace App\Banking\Accounts\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class PreviewInterestRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'days' => ['sometimes', 'integer', 'min:1', 'max:365'],
            'market' => ['sometimes', 'string', 'max:50'],
        ];
    }

    public function days(): int
    {
        return (int) ($this->input('days', 30));
    }

    public function market(): ?string
    {
        return $this->filled('market') ? (string) $this->input('market') : null;
    }
}
