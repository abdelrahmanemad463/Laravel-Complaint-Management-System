<?php

namespace App\Http\Requests\Visitors;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StartVisitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()?->can('visit.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'visit_type_id' => ['required', Rule::exists('visitors_visit_types', 'id')->where('is_active', true)],
            'branch_id' => ['required', Rule::exists('branches', 'id')->where('is_active', true)],
            'visit_date' => ['required', 'date'],
        ];
    }
}
