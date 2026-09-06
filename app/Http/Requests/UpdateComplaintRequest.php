<?php

namespace App\Http\Requests;

use App\Models\ComplaintType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateComplaintRequest extends FormRequest
{
    private ?ComplaintType $resolvedType = null;

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
            'type_id' => ['required', 'exists:complaint_types,id'],
            'status_id' => ['required', 'exists:complaint_statuses,id'],
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