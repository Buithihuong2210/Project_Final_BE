<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    // Specify the table name if it's not the plural of the model name
    protected $table = 'orders';
    protected $primaryKey = 'order_id'; // Specify your custom primary key
    public $incrementing = true; // Ensure this is set to true for auto-incrementing
    // Allow mass assignment for these fields
    protected $fillable = [
        'user_id',
        'shipping_id',
        'voucher_id',
        'shipping_name',
        'shipping_cost',
        'shipping_address',
        'subtotal_of_cart',
        'total_amount',
        'payment_method',
        'order_date',
        'status'
    ];

    // Optionally, define relationships if needed
    public function shipping()
    {
        return $this->belongsTo(Shipping::class, 'shipping_id');
    }

    public function voucher()
    {
        return $this->belongsTo(Voucher::class, 'voucher_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
