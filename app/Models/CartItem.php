<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CartItem extends Model
{
    protected $fillable = ['shopping_cart_id', 'product_id', 'quantity', 'price'];

    public function cart()
    {

        return $this->belongsTo(ShoppingCart::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'product_id');
    }

    use HasFactory;
}
