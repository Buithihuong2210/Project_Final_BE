<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;

    protected $table = 'order_items'; // Đặt tên bảng nếu không phải là dạng số nhiều của tên mô hình
    protected $primaryKey = 'id'; // Hoặc tên khóa chính của bạn

    protected $fillable = [
        'order_id',
        'product_id',
        'quantity',
        'price',
    ];

    // Mối quan hệ đến Order
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'order_id');
    }

    // Mối quan hệ đến Product
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'product_id'); // Đảm bảo rằng product_id là khóa chính trong bảng products
    }
}
