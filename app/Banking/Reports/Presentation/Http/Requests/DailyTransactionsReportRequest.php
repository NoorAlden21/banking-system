<?php

namespace App\Banking\Reports\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class DailyTransactionsReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return ['date' => ['nullable', 'date_format:Y-m-d']];
    }

    public function reportDate(): string
    {
        return (string) ($this->input('date') ?: now()->toDateString());
    }
}
