<?php

namespace App\Banking\Transactions\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ListScheduledTransactionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'scope' => ['nullable', 'in:mine,all'],
            'status' => ['nullable', 'in:active,paused,canceled'],
            'frequency' => ['nullable', 'in:daily,weekly,monthly'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:200'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function scope(): string
    {
        return (string) $this->input('scope', 'mine');
    }
    public function perPage(): int
    {
        return (int) $this->input('per_page', 50);
    }
    public function page(): int
    {
        return (int) $this->input('page', 1);
    }

    public function filters(): array
    {
        return array_filter([
            'status' => $this->input('status'),
            'frequency' => $this->input('frequency'),
        ], fn ($v) => $v !== null && $v !== '');
    }
}
