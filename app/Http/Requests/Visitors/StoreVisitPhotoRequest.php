<?php

namespace App\Http\Requests\Visitors;

use Illuminate\Foundation\Http\FormRequest;

class StoreVisitPhotoRequest extends FormRequest
{
    public const MAX_MB = 20;

    public function authorize(): bool
    {
        return auth()->user()?->can('visit.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'photo' => ['required', 'file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:'.(self::MAX_MB * 1024)],
        ];
    }
}
