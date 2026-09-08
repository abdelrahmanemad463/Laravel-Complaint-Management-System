<?php
namespace App\Http\Controllers;
use App\Models\{Complaint,Branch};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class ReportController extends Controller
{
    public function branches(Request $request) { $request->validate(['date_from'=>'nullable|date','date_to'=>'nullable|date|after_or_equal:date_from','branch_ids'=>'nullable|array','branch_ids.*'=>'integer|exists:branches,id']); $filters=$request->only(['date_from','date_to','branch_ids']); $rows=Complaint::query()->select('complaint_date','branch_id',DB::raw('count(*) as total'))->with('branch')->when($filters['date_from']??null,fn($q,$v)=>$q->whereDate('complaint_date','>=',$v))->when($filters['date_to']??null,fn($q,$v)=>$q->whereDate('complaint_date','<=',$v))->when($filters['branch_ids']??null,fn($q,$v)=>$q->whereIn('branch_id',$v))->groupBy('complaint_date','branch_id')->orderByDesc('complaint_date')->orderBy('branch_id')->paginate(30)->withQueryString(); $selectedBranchIds=array_map('intval',$filters['branch_ids']??[]); $branches=Branch::orderBy('sort_order')->orderBy('name_en')->limit(5)->get(); if($selectedBranchIds){$missingBranches=Branch::whereIn('id',$selectedBranchIds)->whereNotIn('id',$branches->pluck('id'))->get();$branches=$branches->concat($missingBranches);} return view('reports.branches',compact('rows','branches','filters','selectedBranchIds')); }
}
