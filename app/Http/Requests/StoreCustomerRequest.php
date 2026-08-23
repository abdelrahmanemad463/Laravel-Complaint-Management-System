<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class StoreCustomerRequest extends FormRequest
{
    public function authorize(): bool { return auth()->user()?->can('customer.create') ?? false; }
    public function rules(): array { return ['name' => ['required','string','max:255'], 'phone_primary' => ['required','string','max:50'], 'phone_2' => ['nullable','string','max:50'], 'phone_3' => ['nullable','string','max:50'], 'phone_4' => ['nullable','string','max:50'], 'address' => ['nullable','string','max:5000']]; }
    protected function prepareForValidation(): void { foreach (['phone_primary','phone_2','phone_3','phone_4'] as $field) if ($this->has($field)) $this->merge([$field => trim((string) $this->input($field))]); }
}
