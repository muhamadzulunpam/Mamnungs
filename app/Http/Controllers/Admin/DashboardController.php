<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $now = now();
        $monthStart = $now->copy()->startOfMonth();
        $lastStart = $now->copy()->subMonthNoOverflow()->startOfMonth();
        $lastEnd = $now->copy()->subMonthNoOverflow()->endOfMonth();

        $paid = fn () => Order::where('status', 'PAID');

        $revenue = (int) $paid()->where('created_at', '>=', $monthStart)->sum('total');
        $revenueLast = (int) $paid()->whereBetween('created_at', [$lastStart, $lastEnd])->sum('total');

        $orders = $paid()->where('created_at', '>=', $monthStart)->count();
        $ordersLast = $paid()->whereBetween('created_at', [$lastStart, $lastEnd])->count();

        $todayQuery = $paid()->whereDate('created_at', $now->toDateString());

        // Penjualan 7 hari terakhir (hari tanpa transaksi diisi 0)
        $sales = $paid()
            ->where('created_at', '>=', $now->copy()->subDays(6)->startOfDay())
            ->selectRaw('DATE(created_at) as d, SUM(total) as total')
            ->groupBy('d')
            ->pluck('total', 'd');

        $weekly = collect(range(6, 0))->map(function ($i) use ($sales, $now) {
            $date = $now->copy()->subDays($i)->toDateString();

            return ['date' => $date, 'value' => (int) ($sales[$date] ?? 0)];
        });

        // Menu terlaris bulan ini (pakai salinan nama di order_items)
        $topProducts = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.status', 'PAID')
            ->where('orders.created_at', '>=', $monthStart)
            ->selectRaw('order_items.product_name as name, SUM(order_items.quantity) as sold, SUM(order_items.subtotal) as revenue')
            ->groupBy('order_items.product_name')
            ->orderByDesc('sold')
            ->limit(5)
            ->get()
            ->map(fn ($p) => ['name' => $p->name, 'sold' => (int) $p->sold, 'revenue' => (int) $p->revenue]);

        $recentOrders = Order::with(['user:id,name', 'items:id,order_id,product_name,quantity'])
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn ($o) => [
                'id' => $o->id,
                'invoice' => $o->invoice_number,
                'kasir' => $o->user?->name,
                'item' => $o->items->map(fn ($i) => $i->product_name . ($i->quantity > 1 ? " x{$i->quantity}" : ''))->implode(', '),
                'total' => $o->total,
                'status' => $o->status,
                'time' => $o->created_at->locale('id')->diffForHumans(),
            ]);

        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'revenue' => $revenue,
                'revenueChange' => $this->change($revenue, $revenueLast),
                'orders' => $orders,
                'ordersChange' => $this->change($orders, $ordersLast),
                'todayRevenue' => (int) (clone $todayQuery)->sum('total'),
                'todayOrders' => (clone $todayQuery)->count(),
                'products' => Product::where('is_available', true)->count(),
            ],
            'weekly' => $weekly,
            'topProducts' => $topProducts,
            'recentOrders' => $recentOrders,
        ]);
    }

    // Persentase perubahan; null kalau bulan lalu 0 (tidak bisa dibandingkan)
    private function change(int $now, int $before): ?float
    {
        return $before > 0 ? round((($now - $before) / $before) * 100, 1) : null;
    }
}