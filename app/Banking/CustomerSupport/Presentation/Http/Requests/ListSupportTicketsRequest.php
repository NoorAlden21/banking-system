<?php

namespace App\Banking\CustomerSupport\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ListSupportTicketsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'scope' => ['nullable', 'in:mine,all'],
            'status' => ['nullable', 'string', 'max:30'],
            'category' => ['nullable', 'string', 'max:30'],
            'priority' => ['nullable', 'in:low,normal,high'],
            'assigned_to_user_id' => ['nullable', 'integer', 'min:1'],
            'q' => ['nullable', 'string', 'max:150'],
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
            'category' => $this->input('category'),
            'priority' => $this->input('priority'),
            'assigned_to_user_id' => $this->input('assigned_to_user_id'),
            'q' => $this->input('q'),
        ], fn ($v) => $v !== null && $v !== '');
    }
}
