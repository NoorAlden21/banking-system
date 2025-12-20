<?php

namespace App\Banking\Transactions\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class DecideApprovalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'decision' => ['required', 'in:approve,reject'],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function decision(): string
    {
        return (string) $this->input('decision');
    }
    public function note(): ?string
    {
        return $this->filled('note') ? (string) $this->input('note') : null;
    }
}
