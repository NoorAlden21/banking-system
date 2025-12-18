<?php

namespace App\Banking\Accounts\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ListUsersWithAccountsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'limit' => ['nullable', 'integer', 'min:1', 'max:200'],
            'page' => ['nullable', 'integer', 'min:1'],
            'include_group' => ['nullable', 'boolean'],
            'only_customers' => ['nullable', 'boolean'],
            'q' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function limit(): int
    {
        return (int) $this->input('limit', 50);
    }
    public function page(): int
    {
        return (int) $this->input('page', 1);
    }
    public function includeGroup(): bool
    {
        return (bool) filter_var($this->input('include_group', false), FILTER_VALIDATE_BOOLEAN);
    }
    public function onlyCustomers(): bool
    {
        return (bool) filter_var($this->input('only_customers', true), FILTER_VALIDATE_BOOLEAN);
    }
    public function search(): ?string
    {
        return $this->filled('q') ? (string) $this->input('q') : null;
    }
}
