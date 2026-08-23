<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(auth()->user()?->can('audit.view'), 403);
        $logs = ActivityLog::with('user')->latest('created_at')->when($request->filled('action'), fn ($q) => $q->where('action', 'like', '%'.$request->string('action').'%'))->paginate(30)->withQueryString();
        return view('audit-logs.index', compact('logs'));
    }
}
