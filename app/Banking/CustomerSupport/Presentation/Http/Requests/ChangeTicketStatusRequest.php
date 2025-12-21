<?php

namespace App\Banking\CustomerSupport\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ChangeTicketStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'in:open,pending_staff,pending_customer,resolved,closed'],
        ];
    }

    public function status(): string
    {
        return (string) $this->input('status');
    }
}
