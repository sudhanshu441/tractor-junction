<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BrandRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can($this->route('brand') ? 'brands.edit' : 'brands.create') ?? false;
    }

    public function rules(): array
    {
        $id = $this->route('brand')?->id;

        return [
            'name' => ['required', 'string', 'max:100', Rule::unique('brands', 'name')->ignore($id)->whereNull('deleted_at')],
            'description' => ['nullable', 'string', 'max:5000'],
            'country' => ['nullable', 'string', 'max:60'],
            'founded_year' => ['nullable', 'integer', 'min:1800', 'max:'.date('Y')],
            'website' => ['nullable', 'url', 'max:255'],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp,svg', 'max:1024'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_popular' => ['boolean'],
            'is_active' => ['boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_popular' => $this->boolean('is_popular'),
            'is_active' => $this->boolean('is_active'),
            'sort_order' => $this->input('sort_order') ?: 0,
        ]);
    }
}
