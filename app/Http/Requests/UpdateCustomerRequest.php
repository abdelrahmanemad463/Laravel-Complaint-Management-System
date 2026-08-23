<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class UpdateCustomerRequest extends StoreCustomerRequest
{
    public function authorize(): bool { return auth()->user()?->can('customer.update') ?? false; }
}
