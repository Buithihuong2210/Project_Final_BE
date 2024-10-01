<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    // Specify the primary key
    protected $primaryKey = 'product_id';

    // Specify which fields can be mass-assigned
    protected $fillable = [
        'name',
        'description',
        'price',
        'discount',
        'discounted_price',
        'quantity',
        'brand_id',
        'images',
        'status',
        'short_description',
        'volume',
        'nature',
        'rating', // Add rating to fillable
    ];

    // Define the status constants
    const STATUS_AVAILABLE = 'available';
    const STATUS_OUT_OF_STOCK = 'out of stock';

    /**
     * Get the brand associated with the product.
     */
    public function brand()
    {
        return $this->belongsTo(Brand::class, 'brand_id', 'brand_id');
    }

    /**
     * Calculate the discounted price.
     *
     * @return float
     */
    public function calculateDiscountedPrice()
    {
        if ($this->discount > 0 && $this->discount < 100) {
            return $this->price * (1 - $this->discount / 100);
        }
        return $this->price;
    }

    /**
     * Boot method to calculate the discounted price before saving.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($product) {
            // Calculate and set the discounted price when creating the product
            $product->discounted_price = $product->calculateDiscountedPrice();
        });

        static::updating(function ($product) {
            // Calculate and set the discounted price when updating the product
            $product->discounted_price = $product->calculateDiscountedPrice();
        });
    }

    /**
     * Get the average rating for the product based on reviews.
     */
    public function reviews()
    {
        return $this->hasMany(Review::class, 'product_id', 'product_id');
    }

    public function calculateAverageRating()
    {
        return $this->reviews()->avg('rate');
    }
}
