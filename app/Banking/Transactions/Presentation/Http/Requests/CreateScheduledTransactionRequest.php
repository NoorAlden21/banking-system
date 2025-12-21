<?php

namespace App\Banking\Transactions\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateScheduledTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'owner_user_id' => ['nullable', 'integer', 'min:1'], // staff فقط
            'source_account_public_id' => ['required', 'uuid'],
            'destination_account_public_id' => ['required', 'uuid'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'description' => ['nullable', 'string', 'max:255'],

            'frequency' => ['required', 'in:daily,weekly,monthly'],
            'interval' => ['nullable', 'integer', 'min:1', 'max:365'],
            'day_of_week' => ['nullable', 'integer', 'min:0', 'max:6'],
            'day_of_month' => ['nullable', 'integer', 'min:1', 'max:28'],
            'run_time' => ['nullable', 'date_format:H:i'],

            'start_at' => ['nullable', 'date'],
            'end_at' => ['nullable', 'date'],
        ];
    }

    public function runTime(): string
    {
        $t = (string) $this->input('run_time', '09:00');
        return $t . ':00';
    }
}
