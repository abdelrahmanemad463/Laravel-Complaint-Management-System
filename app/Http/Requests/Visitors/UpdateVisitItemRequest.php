<?php

namespace App\Http\Requests\Visitors;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVisitItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()?->can('visit.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(['ok', 'nc', 'na'])],
            'root_cause_id' => ['nullable', 'integer', Rule::exists('visitors_root_causes', 'id')],
            'note' => ['nullable', 'string', 'max:4000'],
            'main_kitchen' => ['sometimes', 'boolean'],
            'support_department' => ['nullable', 'string', 'max:255'],
        ];
    }
}