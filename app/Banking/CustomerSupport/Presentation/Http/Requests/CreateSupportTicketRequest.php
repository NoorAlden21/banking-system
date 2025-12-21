<?php

namespace App\Banking\CustomerSupport\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateSupportTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'subject' => ['required', 'string', 'max:150'],
            'message' => ['required', 'string', 'max:4000'],
            'category' => ['nullable', 'string', 'max:30'],
            'priority' => ['nullable', 'in:low,normal,high'],
        ];
    }

    public function priority(): string
    {
        return (string) ($this->input('priority', 'normal'));
    }
}
