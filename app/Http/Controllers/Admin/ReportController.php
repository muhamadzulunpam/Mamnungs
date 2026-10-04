<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\ActivityLog;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        [$from, $to] = $this->range($request);

        $paid = fn () => Order::where('status', 'PAID')->whereBetween('created_at', [$from, $to]);

        $total = (int) $paid()->sum('total');
        $count = $paid()->count();

        $byMethod = $paid()
            ->selectRaw('payment_method, COUNT(*) as count, SUM(total) as total')
            ->groupBy('payment_method')
            ->get()
            ->map(fn ($r) => [
                'method' => $r->payment_method,
                'count' => (int) $r->count,
                'total' => (int) $r->total,
            ]);

        $daily = $paid()
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count, SUM(total) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(fn ($r) => [
                'date' => $r->date,
                'count' => (int) $r->count,
                'total' => (int) $r->total,
            ]);

        $topProducts = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.status', 'PAID')
            ->whereBetween('orders.created_at', [$from, $to])
            ->selectRaw('order_items.product_name as name, SUM(order_items.quantity) as sold, SUM(order_items.subtotal) as revenue')
            ->groupBy('order_items.product_name')
            ->orderByDesc('sold')
            ->limit(10)
            ->get()
            ->map(fn ($p) => ['name' => $p->name, 'sold' => (int) $p->sold, 'revenue' => (int) $p->revenue]);

        return Inertia::render('Admin/Reports/Index', [
            'filters' => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
            'summary' => [
                'total' => $total,
                'count' => $count,
                'average' => $count > 0 ? (int) round($total / $count) : 0,
            ],
            'byMethod' => $byMethod,
            'daily' => $daily,
            'topProducts' => $topProducts,
        ]);
    }

    public function export(Request $request)
    {
        [$from, $to] = $this->range($request);

        $orders = Order::with('user:id,name')
            ->where('status', 'PAID')
            ->whereBetween('created_at', [$from, $to])
            ->orderBy('created_at')
            ->get();

        $filename = "laporan-{$from->toDateString()}-sd-{$to->toDateString()}.csv";
        ActivityLog::record('export', "Export laporan {$from->toDateString()} s/d {$to->toDateString()}");

        return response()->streamDownload(function () use ($orders) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // agar Excel membaca UTF-8 dengan benar
            fputcsv($out, ['Invoice', 'Tanggal', 'Kasir', 'Metode', 'Subtotal', 'Diskon', 'Total']);

            foreach ($orders as $o) {
                fputcsv($out, [
                    $o->invoice_number,
                    $o->created_at->format('Y-m-d H:i'),
                    $o->user?->name,
                    $o->payment_method,
                    $o->subtotal,
                    $o->discount,
                    $o->total,
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    // Rentang tanggal dari query string; default: awal bulan sampai hari ini
    private function range(Request $request): array
    {
        $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $from = $request->filled('from')
            ? Carbon::parse($request->from)->startOfDay()
            : now()->startOfMonth();

        $to = $request->filled('to')
            ? Carbon::parse($request->to)->endOfDay()
            : now()->endOfDay();

        return [$from, $to];
    }
}