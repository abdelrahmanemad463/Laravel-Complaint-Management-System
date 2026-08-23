<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreComplaintRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()?->can('complaint.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'exists:customers,id'],
            'branch_id' => [Rule::exists('branches', 'id')->where('is_active', true)],
            'service_id' => [Rule::exists('services', 'id')->where('is_active', true)],
            'source_id' => [Rule::exists('complaint_sources', 'id')->where('is_active', true)],
            'category_id' => [Rule::exists('complaint_categories', 'id')->where('is_active', true)],
            'type_id' => [Rule::exists('complaint_types', 'id')->where('is_active', true)],
            'priority_id' => [Rule::exists('priorities', 'id')->where('is_active', true)],
            'status_id' => [Rule::exists('complaint_statuses', 'id')->where('is_active', true)],
            'short_description' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'complaint_date' => ['required', 'date'],
            'resolution' => ['nullable', 'string'],
        ];
    }
}
