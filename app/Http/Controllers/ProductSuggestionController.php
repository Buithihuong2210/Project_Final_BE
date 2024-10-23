<?php

namespace App\Http\Controllers;

use App\Models\Product; // Model cho bảng sản phẩm
use App\Models\Response; // Model cho bảng phản hồi
use Illuminate\Http\Request;

class ProductSuggestionController extends Controller
{
    // Gợi ý sản phẩm dựa trên câu trả lời khảo sát
    public function suggestProducts(Request $request)
    {
        // Xác thực rằng 'responses' được cung cấp và là một mảng
        $validated = $request->validate([
            'responses' => 'required|array',
        ]);

        // Giả sử bạn có logic để phân tích câu trả lời và gợi ý sản phẩm
        $suggestedProducts = [];

        foreach ($validated['responses'] as $response) {
            // Bạn có thể lấy thông tin từ phản hồi
            $answer = $response['answer']; // Giá trị trả lời
            // Thêm logic phân tích ở đây để tìm sản phẩm phù hợp
            // Ví dụ, giả sử bạn chỉ định loại sản phẩm dựa trên thành phần chính
            $products = Product::where('main_ingredient', $answer)
                ->orWhere('target_skin_type', $answer)
                ->get();

            // Thêm các sản phẩm được gợi ý vào danh sách
            $suggestedProducts = array_merge($suggestedProducts, $products->toArray());
        }

        // Trả về danh sách sản phẩm gợi ý
        return response()->json([
            'suggested_products' => array_unique($suggestedProducts), // Đảm bảo không có sản phẩm trùng lặp
        ]);
    }
}
