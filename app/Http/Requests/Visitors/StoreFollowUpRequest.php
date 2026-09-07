<?php

namespace App\Http\Requests\Visitors;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFollowUpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()?->can('visit.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'violation_ids' => ['required', 'array', 'min:1'],
            'violation_ids.*' => ['integer', Rule::exists('visitors_capa_actions', 'id')],
            'result' => ['required', Rule::in(['resolved', 'still_open'])],
            'note' => ['nullable', 'string', 'max:4000'],
            'photos' => ['nullable', 'array', 'max:10'],
            'photos.*' => ['file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:20480'],
        ];
    }
}