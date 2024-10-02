<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $table = 'payments'; // Specify the table name

    protected $primaryKey = 'payment_id'; // Specify the primary key

    protected $fillable = [
        'order_id',
        'user_id',
        'payment_method',
        'amount',
        'status',
        'transaction_id',
    ];

    // Define relationships
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'order_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
