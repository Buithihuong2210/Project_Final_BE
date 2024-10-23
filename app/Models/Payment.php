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
        'amount',
        'transaction_no',
        'bank_code',
        'card_type',
        'pay_date',
        'status',
        'amount'
    ];

    // Define relationships
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'order_id');
    }
}
