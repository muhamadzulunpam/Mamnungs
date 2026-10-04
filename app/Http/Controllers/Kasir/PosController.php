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

    public function checkout(Request $request)
    {
        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:99'],
            'notes' => ['nullable', 'string', 'max:500'],
            'amount_received' => ['required', 'integer', 'min:0'],
        ], [
            'items.required' => 'Keranjang masih kosong.',
            'amount_received.required' => 'Isi uang yang diterima.',
        ]);

        $order = DB::transaction(function () use ($data, $request) {
            // Harga SELALU diambil dari database, bukan dari browser
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
                    'product_name' => $product->name,   // salinan nama saat transaksi
                    'price' => $product->price,         // salinan harga saat transaksi
                    'quantity' => $item['quantity'],
                    'subtotal' => $lineTotal,
                ];
            }

            $total = $subtotal;

            if ($data['amount_received'] < $total) {
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
                'payment_method' => 'CASH',
                'status' => 'PAID',
                'notes' => $data['notes'] ?? null,
            ]);

            $order->items()->createMany($rows);

            Payment::create([
                'order_id' => $order->id,
                'payment_method' => 'CASH',
                'provider' => 'CASH',
                'amount' => $total,
                'amount_received' => $data['amount_received'],
                'change_amount' => $data['amount_received'] - $total,
                'status' => 'PAID',
                'paid_at' => now(),
            ]);

            return $order;
        });

        return redirect()->route('orders.receipt', $order);
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