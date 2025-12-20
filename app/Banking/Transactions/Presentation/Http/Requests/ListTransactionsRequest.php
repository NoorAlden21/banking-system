<?php

namespace App\Banking\Transactions\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ListTransactionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'scope' => ['nullable', 'in:mine,all'],
            'status' => ['nullable', 'string', 'max:50'],
            'type' => ['nullable', 'string', 'max:50'],
            'account_public_id' => ['nullable', 'uuid'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'q' => ['nullable', 'string', 'max:100'],
            'min_amount' => ['nullable', 'numeric', 'min:0'],
            'max_amount' => ['nullable', 'numeric', 'min:0'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:200'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function scope(): string
    {
        return (string) ($this->input('scope', 'mine'));
    }
    public function perPage(): int
    {
        return (int) ($this->input('per_page', 50));
    }
    public function page(): int
    {
        return (int) ($this->input('page', 1));
    }

    public function filters(): array
    {
        return array_filter([
            'status' => $this->input('status'),
            'type' => $this->input('type'),
            'account_public_id' => $this->input('account_public_id'),
            'from' => $this->input('from'),
            'to' => $this->input('to'),
            'q' => $this->input('q'),
            'min_amount' => $this->input('min_amount'),
            'max_amount' => $this->input('max_amount'),
        ], fn ($v) => $v !== null && $v !== '');
    }
}
