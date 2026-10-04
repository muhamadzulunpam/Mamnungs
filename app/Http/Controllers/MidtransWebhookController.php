<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\MidtransService;
use Illuminate\Http\Request;

class MidtransWebhookController extends Controller
{
    public function __invoke(Request $request, MidtransService $midtrans)
    {
        $d = $request->all();

        // Pastikan kiriman benar-benar dari Midtrans
        $expected = hash('sha512',
            ($d['order_id'] ?? '') . ($d['status_code'] ?? '') . ($d['gross_amount'] ?? '') . config('midtrans.server_key')
        );

        if (! hash_equals($expected, (string) ($d['signature_key'] ?? ''))) {
            abort(403, 'Signature tidak valid.');
        }

        $order = Order::where('invoice_number', $d['order_id'])->first();

        if ($order) {
            $midtrans->sync($order, $d);
        }

        return response()->json(['ok' => true]);
    }
}