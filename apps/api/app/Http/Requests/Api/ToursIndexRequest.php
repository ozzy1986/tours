<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class ToursIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'category' => 'array',
            'category.*' => 'string|alpha_dash',
            'duration_min' => 'nullable|integer|min:1|max:60',
            'duration_max' => 'nullable|integer|min:1|max:60',
            'price_min' => 'nullable|integer|min:0',
            'price_max' => 'nullable|integer|min:0',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'sort' => 'nullable|in:price_asc,price_desc,duration_asc,duration_desc,newest',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:48',
        ];
    }
}
