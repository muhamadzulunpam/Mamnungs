<?php

namespace App\Http\Controllers\Kasir;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use App\Services\MidtransService;
use App\Models\ActivityLog;

class PosController extends Controller
{
    public function index()
    {
        return Inertia::render('Kasir/Pos', [
            'categories' => Category::orderBy('name')->get(['id', 'name']),
            'products' => Product::where('is_available', true)
                ->orderBy('name')
                ->get(['id', 'category_id', 'name', 'price', 'image']),
        ]);
    }

    public function checkout(Request $request, MidtransService $midtrans)
    {
        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:99'],
            'notes' => ['nullable', 'string', 'max:500'],
            'payment_method' => ['required', 'in:CASH,QRIS'],
            'amount_received' => ['required_if:payment_method,CASH', 'nullable', 'integer', 'min:0'],
        ], [
            'items.required' => 'Keranjang masih kosong.',
            'amount_received.required_if' => 'Isi uang yang diterima.',
        ]);

        $method = $data['payment_method'];

        $order = DB::transaction(function () use ($data, $request, $method) {
            $products = Product::whereIn('id', collect($data['items'])->pluck('product_id'))
                ->get()
                ->keyBy('id');

            $subtotal = 0;
            $rows = [];

            foreach ($data['items'] as $item) {
                $product = $products[$item['product_id']] ?? null;

                if (! $product || ! $product->is_available) {
                    throw ValidationException::withMessages([
                        'items' => 'Ada produk yang sudah tidak tersedia. Muat ulang halaman.',
                    ]);
                }

                $lineTotal = $product->price * $item['quantity'];
                $subtotal += $lineTotal;

                $rows[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'price' => $product->price,
                    'quantity' => $item['quantity'],
                    'subtotal' => $lineTotal,
                ];
            }

            $total = $subtotal;
            $isCash = $method === 'CASH';

            if ($isCash && $data['amount_received'] < $total) {
                throw ValidationException::withMessages([
                    'amount_received' => 'Uang yang diterima kurang dari total.',
                ]);
            }

            $order = Order::create([
                'invoice_number' => $this->nextInvoiceNumber(),
                'user_id' => $request->user()->id,
                'subtotal' => $subtotal,
                'discount' => 0,
                'total' => $total,
                'payment_method' => $method,
                'status' => $isCash ? 'PAID' : 'PENDING',
                'notes' => $data['notes'] ?? null,
            ]);

            $order->items()->createMany($rows);

            Payment::create([
                'order_id' => $order->id,
                'payment_method' => $method,
                'provider' => $isCash ? 'CASH' : 'MIDTRANS',
                'amount' => $total,
                'amount_received' => $isCash ? $data['amount_received'] : null,
                'change_amount' => $isCash ? $data['amount_received'] - $total : null,
                'status' => $isCash ? 'PAID' : 'PENDING',
                'paid_at' => $isCash ? now() : null,
            ]);

            return $order;
        });
        
        ActivityLog::record(
            'checkout',
            "Membuat transaksi {$order->invoice_number} ({$method}) Rp" . number_format($order->total, 0, ',', '.'),
            $order,
        );

        if ($method === 'CASH') {
            return redirect("/transaksi/{$order->id}/struk");
        }

        // QRIS: minta QR ke Midtrans (di luar DB transaction)
        try {
            $charge = $midtrans->chargeQris($order);

            $order->payment->update([
                'transaction_id' => $charge['transaction_id'] ?? null,
                'raw_response' => $charge,
            ]);
        } catch (\Throwable $e) {
            report($e);
            $order->update(['status' => 'CANCELLED']);
            $order->payment->update(['status' => 'FAILED']);

            return back()->withErrors([
                'payment_method' => 'Gagal membuat QRIS: ' . $e->getMessage(),
            ]);
        }

        return redirect("/kasir/pembayaran/{$order->id}");
    }

    private function nextInvoiceNumber(): string
    {
        $prefix = 'INV-' . now()->format('Ymd') . '-';

        // lockForUpdate: dua kasir checkout bersamaan tidak akan dapat nomor yang sama
        $last = Order::where('invoice_number', 'like', $prefix . '%')
            ->lockForUpdate()
            ->orderByDesc('invoice_number')
            ->value('invoice_number');

        $next = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $prefix . str_pad($next, 4, '0', STR_PAD_LEFT);
    }
}