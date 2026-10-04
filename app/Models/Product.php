<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;   // 1. import, di luar class

class Product extends Model
{
    use SoftDeletes;                            // 2. pakai trait, di dalam class

    protected $fillable = ['category_id', 'name', 'description', 'price', 'image', 'is_available'];
    protected $casts = ['is_available' => 'boolean'];

    public function category() { return $this->belongsTo(Category::class); }
}