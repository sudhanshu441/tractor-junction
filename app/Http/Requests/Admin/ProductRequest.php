<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can($this->route('product') ? 'products.edit' : 'products.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'brand_id' => ['required', 'exists:brands,id'],
            'category_id' => ['required', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:150'],
            'model_code' => ['nullable', 'string', 'max:60'],
            'status' => ['required', 'in:available,upcoming,discontinued'],
            'hp_min' => ['nullable', 'numeric', 'min:0', 'max:999'],
            'hp_max' => ['nullable', 'numeric', 'min:0', 'max:999', 'gte:hp_min'],
            'launch_year' => ['nullable', 'integer', 'min:1950', 'max:'.(date('Y') + 3)],
            'expected_launch_date' => ['nullable', 'date'],
            'short_description' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:20000'],
            'is_featured' => ['boolean'],
            'is_popular' => ['boolean'],
            'is_active' => ['boolean'],

            'specs' => ['array'],
            'features' => ['array'],
            'features.*.title' => ['nullable', 'string', 'max:150'],
            'features.*.description' => ['nullable', 'string', 'max:1000'],
            'faqs' => ['array'],
            'faqs.*.question' => ['nullable', 'string', 'max:255'],
            'faqs.*.answer' => ['nullable', 'string', 'max:3000'],
            'videos' => ['array'],
            'videos.*.title' => ['nullable', 'string', 'max:150'],
            'videos.*.youtube_id' => ['nullable', 'string', 'max:255'],
            'videos.*.type' => ['nullable', 'in:review,walkaround,comparison,demo'],
            'competitors' => ['array', 'max:6'],
            'competitors.*' => ['nullable', 'exists:products,id'],

            'images' => ['array', 'max:12'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_featured' => $this->boolean('is_featured'),
            'is_popular' => $this->boolean('is_popular'),
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    public function messages(): array
    {
        return [
            'hp_max.gte' => __('Maximum HP cannot be lower than minimum HP.'),
            'images.*.max' => __('Each image must be 5 MB or smaller.'),
        ];
    }
}
