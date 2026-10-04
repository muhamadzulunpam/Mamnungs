<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $products = Product::with('category')
            ->when($request->search, fn($q, $s) =>
                $q->where('name', 'like', "%{$s}%"))
            ->when($request->category, fn($q, $c) =>
                $q->where('category_id', $c))
            ->when($request->status !== null && $request->status !== '', fn($q) =>
                $q->where('is_available', $request->status))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('Admin/Products/Index', [
            'products'   => $products,
            'categories' => Category::orderBy('name')->get(),
            'filters'    => $request->only(['search', 'category', 'status']),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Products/Form', [
            'categories' => Category::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('products', 'public');
        }

        Product::create($data);

        return redirect('/admin/products')->with('success', 'Produk berhasil ditambahkan.');
    }

    public function edit(Product $product)
    {
        return Inertia::render('Admin/Products/Form', [
            'product' => $product,
            'categories' => Category::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, Product $product)
    {
        $data = $this->validated($request);

        if ($request->hasFile('image')) {
            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }
            $data['image'] = $request->file('image')->store('products', 'public');
        } else {
            unset($data['image']); // tidak upload baru = pakai foto lama
        }

        $product->update($data);

        return redirect('/admin/products')->with('success', 'Produk berhasil diubah.');
    }

    public function destroy(Product $product)
    {
        // Soft delete: data hilang dari daftar, tapi riwayat order tetap aman
        $product->delete();

        return back()->with('success', 'Produk berhasil dihapus.');
    }

    public function toggle(Product $product)
    {
        $product->update(['is_available' => ! $product->is_available]);

        return back()->with('success', 'Status produk diperbarui.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:1000'],
            'price' => ['required', 'integer', 'min:0', 'max:10000000'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $data['is_available'] = $request->boolean('is_available');

        return $data;
    }
}