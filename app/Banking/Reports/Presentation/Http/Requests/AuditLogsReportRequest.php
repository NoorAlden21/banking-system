<?php

namespace App\Banking\Reports\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class AuditLogsReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'action' => ['nullable', 'string', 'max:120'],
            'actor_user_id' => ['nullable', 'integer', 'min:1'],
            'subject_public_id' => ['nullable', 'uuid'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:200'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function filters(): array
    {
        return array_filter([
            'action' => $this->input('action'),
            'actor_user_id' => $this->input('actor_user_id'),
            'subject_public_id' => $this->input('subject_public_id'),
            'from' => $this->input('from'),
            'to' => $this->input('to'),
        ], fn ($v) => $v !== null && $v !== '');
    }

    public function perPage(): int
    {
        return (int) ($this->input('per_page', 50));
    }
    public function page(): int
    {
        return (int) ($this->input('page', 1));
    }
}
