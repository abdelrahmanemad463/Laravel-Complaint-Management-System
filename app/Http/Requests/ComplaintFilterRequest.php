<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class ComplaintFilterRequest extends FormRequest
{
    public function authorize(): bool { return auth()->user()?->can('complaint.view') ?? false; }
    public function rules(): array { return ['complaint_id' => ['nullable','integer','exists:complaints,id'], 'customer_id' => ['nullable','integer','exists:customers,id'], 'branch_ids' => ['nullable','array'], 'branch_ids.*' => ['integer','exists:branches,id'], 'service_id' => ['nullable','integer','exists:services,id'], 'source_id' => ['nullable','integer','exists:complaint_sources,id'], 'category_id' => ['nullable','integer','exists:complaint_categories,id'], 'type_id' => ['nullable','integer','exists:complaint_types,id'], 'priority_id' => ['nullable','integer','exists:priorities,id'], 'status_id' => ['nullable','integer','exists:complaint_statuses,id'], 'created_by' => ['nullable','integer','exists:users,id'], 'date_from' => ['nullable','date'], 'date_to' => ['nullable','date','after_or_equal:date_from']]; }
}
