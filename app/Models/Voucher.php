<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Voucher extends Model
{
    use HasFactory;

    protected $primaryKey = 'voucher_id';

    protected $fillable = [
        'code',
        'discount_amount',
        'status',
        'start_date',
        'expiry_date'
    ];

    public $timestamps = true;
}
