<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class SyncRolePermissionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (int) auth()->user()?->type === 1;
    }

    public function rules(): array
    {
        return [
            'role_id' => ['sometimes', 'exists:roles,id'],
            'permission_id' => ['required', 'array'],
            'permission_id.*' => ['integer', 'exists:permissions,id'],
        ];
    }
}
