<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Rules\PasswordComplexityRule;


class UpdateUserRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:users,email,' . $this->users->id,
            'password' => ['required', 'string', 'min:8', 'confirmed', new PasswordComplexityRule()],
            // 'password' => [
            //     'nullable',
            //     'string',
            //     'min:8',
            //     'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).+$/'
            // ],
        ];
    }

    public function messages(): array
    {
        return [
            'password.regex' => 'Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character.',
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
