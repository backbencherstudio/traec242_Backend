<?php

declare(strict_types=1);

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProviderProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (int) $this->user()?->type === 2;
    }

    public function rules(): array
    {
        $userId = $this->user()?->id;

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'last_name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'unique:users,email,'.$userId],
            'phone' => ['sometimes', 'string', 'max:20'],
            'bio' => ['sometimes', 'string'],
            'languages' => ['sometimes', 'array'],
            'languages.*' => ['string'],
        ];
    }
}
