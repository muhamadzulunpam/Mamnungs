<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $logs = ActivityLog::query()
            ->when($request->input('user_id'), fn ($q, $v) => $q->where('user_id', $v))
            ->when($request->input('action'), fn ($q, $v) => $q->where('action', $v))
            ->when($request->input('search'), fn ($q, $v) => $q->where('description', 'like', "%{$v}%"))
            ->when($request->input('from'), fn ($q, $v) => $q->where('created_at', '>=', Carbon::parse($v)->startOfDay()))
            ->when($request->input('to'), fn ($q, $v) => $q->where('created_at', '<=', Carbon::parse($v)->endOfDay()))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/ActivityLogs/Index', [
            'logs' => $logs,
            'users' => User::orderBy('name')->get(['id', 'name']),
            'filters' => $request->only('user_id', 'action', 'search', 'from', 'to'),
        ]);
    }
}