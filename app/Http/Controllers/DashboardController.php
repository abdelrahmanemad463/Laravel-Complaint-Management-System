<?php
namespace App\Http\Controllers;
use App\Models\{Complaint,Branch,ComplaintStatus,Priority};
use Illuminate\Support\Facades\DB;
class DashboardController extends Controller
{
    public function __invoke()
    {
        $start = now()->subDays(6)->startOfDay();
        $daily = Complaint::select('complaint_date', DB::raw('count(*) as total'))->whereDate('complaint_date','>=',$start)->groupBy('complaint_date')->pluck('total','complaint_date');
        $days = collect(range(6,0))->map(fn($i) => now()->subDays($i)->toDateString())->mapWithKeys(fn($date) => [$date => $daily[$date] ?? 0]);
        $branches = Branch::withCount('complaints')->orderByDesc('complaints_count')->limit(5)->get();
        $statuses = ComplaintStatus::withCount('complaints')->orderByDesc('complaints_count')->get();
        $priorities = Priority::withCount('complaints')->orderBy('level')->get();
        return view('dashboard.index', compact('days','branches','statuses','priorities'));
    }
}
