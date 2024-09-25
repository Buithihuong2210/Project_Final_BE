<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Brand;

class Product extends Model
{
    protected $primaryKey = 'product_id';
    protected $fillable = [
        'name',
        'description',
        'price',
        'quantity',
        'brand_id',
        'images',
        'status',
        'short_description',
        'volume',
        'nature'
    ];
    use HasFactory;

    public function brand()
    {
        return $this->belongsTo(Brand::class, 'brand_id', 'brand_id');
    }

    // Update the brand's total_products when a product is created
    protected static function booted()
    {
        static::created(function ($product) {
            $product->brand->increment('total_products');
        });

        static::deleted(function ($product) {
            $product->brand->decrement('total_products');
        });

        static::updated(function ($product) {
            // If the brand_id has changed, update both old and new brands
            if ($product->wasChanged('brand_id')) {
                $oldBrand = Brand::find($product->getOriginal('brand_id'));
                $oldBrand->decrement('total_products');

                $newBrand = Brand::find($product->brand_id);
                $newBrand->increment('total_products');
            }
        });
    }
}
