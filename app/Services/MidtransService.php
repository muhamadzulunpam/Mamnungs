<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Midtrans\Config;
use Midtrans\CoreApi;
use Midtrans\Transaction;
use App\Models\ActivityLog;

class MidtransService
{
    public function __construct()
    {
        Config::$serverKey = config('midtrans.server_key');
        Config::$isProduction = config('midtrans.is_production');
        Config::$isSanitized = true;
    }

    public function chargeQris(Order $order): array
    {
        $order->loadMissing('items');

        $response = CoreApi::charge([
            'payment_type' => 'qris',
            'transaction_details' => [
                'order_id' => $order->invoice_number,
                'gross_amount' => $order->total,
            ],
            'item_details' => $order->items->map(fn ($i) => [
                'id' => (string) $i->product_id,
                'price' => $i->price,
                'quantity' => $i->quantity,
                'name' => mb_substr($i->product_name, 0, 50),
            ])->all(),
            'qris' => ['acquirer' => 'gopay'],
            'custom_expiry' => ['expiry_duration' => 15, 'unit' => 'minute'],
        ]);

        return json_decode(json_encode($response), true);
    }

    public function status(string $orderId): array
    {
        return json_decode(json_encode(Transaction::status($orderId)), true);
    }

    public function cancel(string $orderId): void
    {
        Transaction::cancel($orderId);
    }

    // Menyamakan status order/payment dengan status dari Midtrans
    public function sync(Order $order, array $data): void
    {
        $status = $data['transaction_status'] ?? null;
        $fraud = $data['fraud_status'] ?? null;

        [$orderStatus, $paymentStatus] = match (true) {
            $status === 'settlement', $status === 'capture' && $fraud !== 'challenge' => ['PAID', 'PAID'],
            $status === 'expire' => ['EXPIRED', 'EXPIRED'],
            in_array($status, ['cancel', 'deny', 'failure'], true) => ['CANCELLED', 'FAILED'],
            default => [null, null],
        };

        if (! $orderStatus) {
            return; // masih pending
        }

        DB::transaction(function () use ($order, $orderStatus, $paymentStatus, $data) {
            $locked = Order::whereKey($order->id)->lockForUpdate()->first();

            // Hanya order PENDING yang boleh berubah (aman kalau webhook terkirim dua kali)
            if (! $locked || $locked->status !== 'PENDING') {
                return;
            }

            $locked->update(['status' => $orderStatus]);

            $locked->payment?->update([
                'status' => $paymentStatus,
                'paid_at' => $paymentStatus === 'PAID' ? now() : null,
                'transaction_id' => $data['transaction_id'] ?? $locked->payment->transaction_id,
            ]);

            $label = match ($orderStatus) {
                'PAID' => 'lunas',
                'EXPIRED' => 'kedaluwarsa',
                default => 'dibatalkan/ditolak',
            };

            ActivityLog::record(
                'payment_' . strtolower($orderStatus),
                "Pembayaran QRIS {$locked->invoice_number} {$label}",
                $locked,
                ['source' => auth()->check() ? 'pengecekan kasir' : 'webhook Midtrans'],
            );
        });
    }
}