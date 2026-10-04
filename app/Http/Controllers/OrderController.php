<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $date = $request->input('date', now()->toDateString());

        $query = Order::with('user:id,name')
            ->when($user->role === 'kasir', fn ($q) => $q->where('user_id', $user->id))
            ->whereDate('created_at', $date);

        $paid = (clone $query)->where('status', 'PAID');

        return Inertia::render('Transaksi/Index', [
            'orders' => $query->latest()->paginate(15)->withQueryString(),
            'filters' => ['date' => $date],
            'summary' => [
                'count' => $paid->count(),
                'total' => (int) $paid->sum('total'),
            ],
        ]);
    }

    public function receipt(Request $request, Order $order)
    {
        // Kasir hanya boleh melihat transaksinya sendiri
        abort_if(
            $request->user()->role === 'kasir' && $order->user_id !== $request->user()->id,
            403
        );

        $order->load(['items', 'payment', 'user:id,name']);

        return Inertia::render('Transaksi/Struk', [
            'order' => $order,
            // Sementara ditulis di sini, nanti dipindah ke menu Pengaturan
            'store' => [
                'name' => 'Es Teler Mamah Nunung',
                'address' => 'Alamat usaha Anda',
            ],
        ]);
    }
}