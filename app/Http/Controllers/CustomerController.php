<?php
namespace App\Http\Controllers;
use App\Http\Requests\{StoreCustomerRequest,UpdateCustomerRequest};
use App\Models\Customer;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
class CustomerController extends Controller
{
    public function index(Request $request) { abort_unless(auth()->user()?->can('customer.view'), 403); $search = trim((string)$request->input('search')); $customers = Customer::withCount('complaints')->when($search, fn($q) => $q->where(fn($x) => $x->where('name','like',"%$search%")->orWhere('phone_primary',$search)->orWhere('phone_2',$search)->orWhere('phone_3',$search)->orWhere('phone_4',$search)))->latest()->paginate(15)->withQueryString(); return view('customers.index', compact('customers','search')); }
    public function create() { abort_unless(auth()->user()?->can('customer.create'), 403); return view('customers.create'); }
    public function store(StoreCustomerRequest $request) { $customer = Customer::create($request->validated()); app(ActivityLogService::class)->record('customer.created',$customer,__('customers.created_log')); return redirect()->route('customers.show',$customer)->with('success',__('customers.created')); }
    public function show(Customer $customer) { abort_unless(auth()->user()?->can('customer.view'), 403); $customer->load(['complaints.status','complaints.priority','complaints.branch']); $summary = $customer->complaints()->selectRaw("count(*) total")->selectRaw("sum(status_id = (select id from complaint_statuses where name = 'Pending' limit 1)) pending")->first(); return view('customers.show', compact('customer','summary')); }
    public function edit(Customer $customer) { abort_unless(auth()->user()?->can('customer.update'), 403); return view('customers.edit', compact('customer')); }
    public function update(UpdateCustomerRequest $request, Customer $customer) { $old = $customer->only(array_keys($request->validated())); $customer->update($request->validated()); app(ActivityLogService::class)->record('customer.updated',$customer,__('customers.updated_log'),$old,$customer->only(array_keys($request->validated()))); return redirect()->route('customers.show',$customer)->with('success',__('customers.updated')); }
}
