<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Exception;

class BrandController extends Controller
{
    // List all brands
    public function index()
    {
        $brands = Brand::all();  // Dữ liệu sẽ có thêm trường total_products
        return response()->json($brands, 200);
    }

    // Store a new brand
    public function store(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255|unique:brands,name',
                'description' => 'nullable|string',
                'image' => 'nullable|string|url'
            ]);
        }
        catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }
        try {
            $brand = Brand::create($request->all());
            return response()->json($brand, 201);
        } catch (Exception $e) {
            return response()->json(['message' => 'An error occurred', 'error' => $e->getMessage()], 500);
        }
    }

    // Show a specific brand
    public function show($id)
    {
        $brand = Brand::find($id);

        if (is_null($brand)) {
            return response()->json(['message' => 'Nhãn hiệu không tìm thấy'], 404);
        }

        return response()->json($brand, 200);
    }

    // Update a specific brand
    public function update(Request $request, $id)
    {
        try {
            $brand = Brand::find($id);

            if (is_null($brand)) {
                return response()->json(['message' => 'Brand not found'], 404);
            }

            $request->validate([
                'name' => 'required|string|max:255|unique:brands,name,' . $id. ',brand_id',
                'description' => 'nullable|string',
                'image' => 'nullable|string|url'
            ]);


            $brand->update($request->all());
            return response()->json($brand, 200);

        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Brand not found'], 404);

        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);

        } catch (Exception $e) {
            return response()->json(['message' => 'An error occurred', 'error' => $e->getMessage()], 500);
        }
    }

    // Delete a specific brand
    public function destroy($id)
    {
        $brand = Brand::find($id);

        if (is_null($brand)) {
            return response()->json(['message' => 'Brand not found'], 404);
        }

        try {
            // Optionally, you could delete all products related to this brand
            // Product::where('brand_id', $id)->delete();

            $brand->delete();
            return response()->json(['message' => "Brand {$id} deleted successfully"], 200);

        } catch (Exception $e) {
            return response()->json(['message' => 'An error occurred', 'error' => $e->getMessage()], 500);
        }
    }

    public function getProductsByBrand($brandId)
    {
        // Tìm nhãn hiệu theo brand_id
        $brand = Brand::find($brandId);

        if (is_null($brand)) {
            return response()->json(['message' => 'Nhãn hiệu không tìm thấy'], 404);
        }

        // Trả về tất cả các sản phẩm liên quan đến nhãn hiệu này
        $products = $brand->products;

        return response()->json($products, 200);
    }
}