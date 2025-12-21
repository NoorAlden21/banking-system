<?php

namespace App\Banking\CustomerSupport\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class AssignTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'assigned_to_user_id' => ['required', 'integer', 'exists:users,id'],
        ];
    }

    public function assignedToUserId(): int
    {
        return (int) $this->input('assigned_to_user_id');
    }
}
