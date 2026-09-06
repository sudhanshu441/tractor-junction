<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can($this->routeIs('*.store') ? 'users.create' : 'users.edit') ?? false;
    }

    public function rules(): array
    {
        $id = $this->route('user')?->id;
        $creating = $id === null;

        return [
            'name' => ['required', 'string', 'max:100'],
            'mobile' => ['required', 'digits:10', 'regex:/^[6-9]\d{9}$/', Rule::unique('users', 'mobile')->ignore($id)],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($id)],
            'password' => [$creating ? 'required' : 'nullable', 'string', 'min:10', 'confirmed'],
            'role' => ['required', 'string', 'exists:roles,name'],
            'state_id' => ['nullable', 'exists:states,id'],
            'district_id' => ['nullable', 'exists:districts,id'],
            'is_active' => ['boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['is_active' => $this->boolean('is_active')]);
    }

    public function messages(): array
    {
        return [
            'mobile.regex' => __('Enter a valid 10-digit Indian mobile number.'),
            'password.min' => __('Staff passwords must be at least 10 characters.'),
        ];
    }
}
