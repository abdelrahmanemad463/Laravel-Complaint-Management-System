<?php

namespace App\Http\Requests;

use App\Models\ComplaintType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreComplaintRequest extends FormRequest
{
    private ?ComplaintType $resolvedType = null;

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
            'type_id' => [Rule::exists('complaint_types', 'id')->where('is_active', true)],
            'status_id' => [Rule::exists('complaint_statuses', 'id')->where('is_active', true)],
            'short_description' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'complaint_date' => ['required', 'date'],
            'serial_number' => ['nullable', 'string', 'max:255'],
            'price' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'resolution' => ['nullable', 'string'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $type = $this->type();
            if (! $type || ! $type->category_id || ! $type->priority_id) {
                $validator->errors()->add('type_id', __('common.type_missing_category_priority'));
            }
        });
    }

    public function type(): ?ComplaintType
    {
        return $this->resolvedType ??= ComplaintType::with(['category', 'priority'])->find((int) $this->input('type_id')) ?: null;
    }
}