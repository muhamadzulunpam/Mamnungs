<?php

namespace App\Http\Controllers\Kasir;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\MidtransService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\ActivityLog;

class QrisController extends Controller
{
    public function show(Request $request, Order $order)
    {
        $this->authorizeOrder($request, $order);

        if ($order->status === 'PAID') {
            return redirect("/transaksi/{$order->id}/struk");
        }

        $order->load('payment');
        $raw = $order->payment?->raw_response ?? [];
        $actions = collect($raw['actions'] ?? []);
        $qr = $actions->firstWhere('name', 'generate-qr-code-v2')
            ?? $actions->firstWhere('name', 'generate-qr-code');

        return Inertia::render('Kasir/Qris', [
            'order' => $order->only('id', 'invoice_number', 'total', 'status'),
            'qrUrl' => $qr['url'] ?? null,
            'qrString' => config('midtrans.is_production') ? null : ($raw['qr_string'] ?? null),
            'expiresAt' => $raw['expiry_time'] ?? null,
        ]);
    }

    public function status(Request $request, Order $order, MidtransService $midtrans)
    {
        $this->authorizeOrder($request, $order);

        if ($order->status === 'PENDING') {
            try {
                $midtrans->sync($order, $midtrans->status($order->invoice_number));
            } catch (\Throwable $e) {
                // abaikan, dicoba lagi pada polling berikutnya
            }
            $order->refresh();
        }

        return response()->json(['status' => $order->status]);
    }

    public function cancel(Request $request, Order $order, MidtransService $midtrans)
    {
        $this->authorizeOrder($request, $order);

        if ($order->status === 'PENDING') {
            try {
                $midtrans->cancel($order->invoice_number);
            } catch (\Throwable $e) {
                report($e);
            }

            $order->update(['status' => 'CANCELLED']);
            $order->payment?->update(['status' => 'FAILED']);
            
            ActivityLog::record('payment_cancelled', "Membatalkan pembayaran QRIS {$order->invoice_number}", $order);
        }

        return redirect('/kasir')->with('success', 'Pembayaran QRIS dibatalkan.');
    }

    private function authorizeOrder(Request $request, Order $order): void
    {
        abort_if(
            $request->user()->role === 'kasir' && $order->user_id !== $request->user()->id,
            403
        );
    }
}