<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var User|null $managedUser */
        $managedUser = $this->route('user');

        return $managedUser
            ? $this->user()?->can('update', $managedUser) ?? false
            : $this->user()?->can('create', User::class) ?? false;
    }

    public function rules(): array
    {
        /** @var User|null $managedUser */
        $managedUser = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($managedUser?->id),
            ],
            'unit_id' => ['nullable', 'exists:units,id'],
            'position_id' => ['nullable', 'exists:positions,id'],
            'role' => ['required', 'exists:roles,name'],
            'is_active' => ['required', 'boolean'],
            'password' => [
                $managedUser ? 'nullable' : 'required',
                'string',
                'min:8',
                'confirmed',
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'unit_id' => 'unit kerja',
            'position_id' => 'jabatan',
        ];
    }
}
