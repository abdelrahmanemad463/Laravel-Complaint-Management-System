<?php
namespace App\Http\Controllers;
use App\Http\Requests\{StoreCustomerRequest,UpdateCustomerRequest};
use App\Models\Customer;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
class CustomerController extends Controller
{
    public function index(Request $request) { abort_unless(auth()->user()?->can('customer.view'), 403); $search = trim((string)$request->input('search')); $customers = Customer::withCount('complaints')->when($search, fn($q) => $q->where(fn($x) => $x->where('name','like',"%$search%")->orWhere('phone_primary',$search)->orWhere('phone_2',$search)->orWhere('phone_3',$search)->orWhere('phone_4',$search)))->latest()->paginate(30)->withQueryString(); return view('customers.index', compact('customers','search')); }
    public function search(Request $request) { abort_unless(auth()->user()?->can('customer.view'), 403); $term = trim((string) $request->input('q')); $customers = Customer::query()->when($term !== '', fn($q) => $q->where(fn($x) => $x->where('name','like',"%$term%")->orWhere('phone_primary','like',"%$term%")->orWhere('phone_2','like',"%$term%")->orWhere('phone_3','like',"%$term%")->orWhere('phone_4','like',"%$term%")))->orderBy('name')->limit(10)->get(['id','name','phone_primary','phone_2','phone_3','phone_4']); return response()->json($customers); }
    public function quickStore(StoreCustomerRequest $request) { $values = $request->validated(); $existing = Customer::phone($values['phone_primary'])->first(); if ($existing) return response()->json(['message' => __('customers.phone_exists'), 'customer' => $existing->only(['id','name','phone_primary','phone_2','phone_3','phone_4','address'])], 422); $customer = Customer::create($values); app(ActivityLogService::class)->record('customer.created',$customer,__('customers.created_log')); return response()->json(['message' => __('customers.created'), 'customer' => $customer->only(['id','name','phone_primary','phone_2','phone_3','phone_4','address'])], 201); }
    public function create() { abort_unless(auth()->user()?->can('customer.create'), 403); return view('customers.create'); }
    public function store(StoreCustomerRequest $request) { $customer = Customer::create($request->validated()); app(ActivityLogService::class)->record('customer.created',$customer,__('customers.created_log')); return redirect()->route('customers.show',$customer)->with('success',__('customers.created')); }
    public function show(Customer $customer) { abort_unless(auth()->user()?->can('customer.view'), 403); $customer->load(['complaints.status','complaints.priority','complaints.branch']); return view('customers.show', compact('customer')); }
    public function edit(Customer $customer) { abort_unless(auth()->user()?->can('customer.update'), 403); return view('customers.edit', compact('customer')); }
    public function update(UpdateCustomerRequest $request, Customer $customer) { $old = $customer->only(array_keys($request->validated())); $customer->update($request->validated()); app(ActivityLogService::class)->record('customer.updated',$customer,__('customers.updated_log'),$old,$customer->only(array_keys($request->validated()))); return redirect()->route('customers.show',$customer)->with('success',__('customers.updated')); }
}
