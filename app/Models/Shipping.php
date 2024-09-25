<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shipping extends Model
{
    use HasFactory;
    protected $primaryKey = 'shipping_id';
    protected $fillable = ['name',  'shipping_amount'];
}
