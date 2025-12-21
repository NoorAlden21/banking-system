<?php

namespace App\Banking\Transactions\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateScheduledTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'amount' => ['nullable', 'numeric', 'min:0.01'],
            'description' => ['nullable', 'string', 'max:255'],

            'frequency' => ['nullable', 'in:daily,weekly,monthly'],
            'interval' => ['nullable', 'integer', 'min:1', 'max:365'],
            'day_of_week' => ['nullable', 'integer', 'min:0', 'max:6'],
            'day_of_month' => ['nullable', 'integer', 'min:1', 'max:28'],
            'run_time' => ['nullable', 'date_format:H:i'],

            'start_at' => ['nullable', 'date'],
            'end_at' => ['nullable', 'date'],

            'status' => ['nullable', 'in:active,paused,canceled'],
        ];
    }

    public function runTimeOrNull(): ?string
    {
        if (!$this->filled('run_time')) return null;
        return (string) $this->input('run_time') . ':00';
    }
}
