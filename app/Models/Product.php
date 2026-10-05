<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes; 
use App\Models\Concerns\LogsActivity;

class Product extends Model
{
    use SoftDeletes, LogsActivity;                            

    protected $activityLabel = 'Produk';

    protected $fillable = ['category_id', 'name', 'description', 'price', 'image', 'is_available'];
    protected $casts = ['is_available' => 'boolean'];

    public function category() { return $this->belongsTo(Category::class); }
}