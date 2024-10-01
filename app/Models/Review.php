<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;

    protected $primaryKey = 'review_id';

    protected $fillable = ['content', 'rate', 'user_id', 'product_id'];

    // Each review belongs to a user
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Each review belongs to a product
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'product_id');
    }
}
