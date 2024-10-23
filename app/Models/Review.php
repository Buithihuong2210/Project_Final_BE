<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;

    protected $primaryKey = 'review_id';

    protected $fillable = ['content', 'rate', 'user_id', 'product_id',  'order_id',];

    // Each review belongs to a user
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    // Thiết lập quan hệ với Product
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'product_id');
    }

    // Thiết lập quan hệ với Order
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'order_id');
    }
}
