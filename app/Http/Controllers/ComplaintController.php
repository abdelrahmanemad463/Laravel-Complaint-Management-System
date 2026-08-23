<?php
namespace App\Http\Controllers;
use App\Exports\ComplaintsExport;
use App\Http\Requests\{ComplaintFilterRequest,StoreComplaintRequest,UpdateComplaintRequest};
use App\Models\{Complaint,Customer,Branch,Service,ComplaintSource,ComplaintCategory,ComplaintType,Priority,ComplaintStatus,User};
use App\Services\{ActivityLogService,ComplaintStatusService};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
class ComplaintController extends Controller
{
    public function index(ComplaintFilterRequest $request) { abort_unless(auth()->user()?->can('complaint.view'), 403); $filters = $request->validated(); $complaints = Complaint::with(['customer','branch','service','category','type','priority','status','creator'])->filter($filters)->latest('complaint_date')->latest()->paginate(20)->withQueryString(); $data = $this->masterData(); return view('complaints.index', compact('complaints','filters','data')); }
    public function create(Request $request) { abort_unless(auth()->user()?->can('complaint.create'), 403); $customer = $request->filled('customer') ? Customer::findOrFail($request->integer('customer')) : null; $data = $this->masterData(true); return view('complaints.create', compact('customer','data')); }
    public function store(StoreComplaintRequest $request) { $values = $request->validated(); $values['created_by'] = auth()->id(); $complaint = DB::transaction(function() use ($values) { $complaint = Complaint::create($values); app(ActivityLogService::class)->record('complaint.created',$complaint,__('complaints.created_log')); return $complaint; }); return redirect()->route('complaints.show',$complaint)->with('success',__('complaints.created')); }
    public function show(Complaint $complaint) { abort_unless(auth()->user()?->can('complaint.view'), 403); $complaint->load(['customer','branch','service','source','category','type','priority','status','creator','resolver','statusHistories.fromStatus','statusHistories.toStatus','statusHistories.changer','activityLogs.user']); return view('complaints.show', compact('complaint')); }
    public function edit(Complaint $complaint) { abort_unless(auth()->user()?->can('complaint.update'), 403); $complaint->load('customer'); $data = $this->masterData(true); return view('complaints.edit', compact('complaint','data')); }
    public function update(UpdateComplaintRequest $request, Complaint $complaint) { $values = $request->validated(); $old = $complaint->only(array_keys($values)); $statusId = (int) $values['status_id']; $statusChanged = (int) $complaint->status_id !== $statusId; DB::transaction(function() use ($complaint,$values,$old,$statusChanged,$statusId) { if ($statusChanged) unset($values['status_id']); $complaint->update($values); app(ActivityLogService::class)->record('complaint.updated',$complaint,__('complaints.updated_log'),$old,$complaint->only(array_keys($values))); if ($statusChanged) app(ComplaintStatusService::class)->change($complaint,$statusId,request('status_reason')); }); return redirect()->route('complaints.show',$complaint)->with('success',__('complaints.updated')); }
    public function export(ComplaintFilterRequest $request) { abort_unless(auth()->user()->can('complaint.export'),403); return Excel::download(new ComplaintsExport($request->validated()), 'complaints-'.now()->format('Y-m-d').'.xlsx'); }
    private function masterData(bool $activeOnly = false): array { $scope = fn($model) => $model::query()->when($activeOnly, fn($q) => $q->where('is_active',true))->orderBy('sort_order')->orderBy('name')->get(); return ['customers'=>Customer::orderBy('name')->get(),'branches'=>$scope(Branch::class),'services'=>$scope(Service::class),'sources'=>$scope(ComplaintSource::class),'categories'=>$scope(ComplaintCategory::class),'types'=>$scope(ComplaintType::class),'priorities'=>$scope(Priority::class),'statuses'=>$scope(ComplaintStatus::class),'users'=>User::orderBy('name')->get()]; }
}
