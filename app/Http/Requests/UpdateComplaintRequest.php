<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateComplaintRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()?->can('complaint.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'exists:customers,id'],
            'branch_id' => ['required', 'exists:branches,id'],
            'service_id' => ['required', 'exists:services,id'],
            'source_id' => ['required', 'exists:complaint_sources,id'],
            'category_id' => ['required', 'exists:complaint_categories,id'],
            'type_id' => ['required', 'exists:complaint_types,id'],
            'priority_id' => ['required', 'exists:priorities,id'],
            'status_id' => ['required', 'exists:complaint_statuses,id'],
            'short_description' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'complaint_date' => ['required', 'date'],
            'resolution' => ['nullable', 'string'],
        ];
    }
}
