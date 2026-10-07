<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        // Log SELALU dikunci ke user yang sedang login
        $logs = ActivityLog::where('user_id', $user->id)
            ->when($request->input('action'), fn ($q, $v) => $q->where('action', $v))
            ->when($request->input('from'), fn ($q, $v) => $q->where('created_at', '>=', Carbon::parse($v)->startOfDay()))
            ->when($request->input('to'), fn ($q, $v) => $q->where('created_at', '<=', Carbon::parse($v)->endOfDay()))
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        // Login terakhir sebelum sesi ini
        $logins = ActivityLog::where('user_id', $user->id)
            ->where('action', 'login')
            ->latest('id')
            ->limit(2)
            ->get();

        $paid = fn () => Order::where('user_id', $user->id)->where('status', 'PAID');

        return Inertia::render('Profil/Index', [
            'profile' => [
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'is_active' => $user->is_active,
                'created_at' => $user->created_at,
            ],
            'stats' => [
                'previousLogin' => $logins[1]->created_at ?? null,
                'orders' => $paid()->count(),
                'sales' => (int) $paid()->sum('total'),
            ],
            'logs' => $logs,
            'filters' => $request->only('action', 'from', 'to'),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
        ]);

        $request->user()->update($data);

        return back()->with('success', 'Profil berhasil diperbarui.');
    }

    public function password(Request $request)
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed', 'different:current_password'],
        ], [
            'current_password.current_password' => 'Password lama salah.',
            'password.different' => 'Password baru harus berbeda dari password lama.',
        ]);

        $request->user()->update(['password' => $data['password']]);

        return back()->with('success', 'Password berhasil diganti.');
    }
}