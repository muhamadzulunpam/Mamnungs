<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\LogsActivity;

class Category extends Model
{
    use LogsActivity;

    protected $activityLabel = 'Kategori';
    protected $fillable = ['name', 'slug'];



    public function products() { return $this->hasMany(Product::class); }   
}
