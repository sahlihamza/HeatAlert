<?php

namespace App\Http\Requests;

use App\Models\Module4Notification;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreModule4NotificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'exists:users,id'],
            'equipement_sensible_id' => ['nullable', 'exists:equipements_sensibles,id'],
            'message' => ['required', 'string', 'min:5', 'max:2000'],
            'canal' => ['required', Rule::in(Module4Notification::CANAUX)],
        ];
    }
}
