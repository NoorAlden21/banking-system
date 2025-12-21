<?php

namespace App\Banking\CustomerSupport\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class AddTicketMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:4000'],
            'is_internal' => ['nullable', 'boolean'],
        ];
    }

    public function isInternal(): bool
    {
        return (bool) $this->input('is_internal', false);
    }
}
