<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShoppingCart extends Model
{
    use HasFactory;

    // Allow mass assignment for the following fields
    protected $fillable = ['user_id', 'subtotal']; // Added subtotal to the fillable fields

    // Define the relationship with CartItem
    public function items()
    {
        return $this->hasMany(CartItem::class, 'shopping_cart_id');
    }
}
