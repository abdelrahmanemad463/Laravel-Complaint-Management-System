<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DashboardFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()?->can('complaint.view') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'branch_ids' => array_values(array_filter((array) $this->input('branch_ids', []), static fn ($value) => $value !== null && $value !== '')),
        ]);
    }

    public function rules(): array
    {
        return [
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'branch_ids' => ['nullable', 'array'],
            'branch_ids.*' => ['integer', 'exists:branches,id'],
            'service_id' => ['nullable', 'integer', 'exists:services,id'],
            'category_id' => ['nullable', 'integer', 'exists:complaint_categories,id'],
            'source_id' => ['nullable', 'integer', 'exists:complaint_sources,id'],
            'type_id' => ['nullable', 'integer', 'exists:complaint_types,id'],
            'priority_id' => ['nullable', 'integer', 'exists:priorities,id'],
            'status_id' => ['nullable', 'integer', 'exists:complaint_statuses,id'],
        ];
    }
}
