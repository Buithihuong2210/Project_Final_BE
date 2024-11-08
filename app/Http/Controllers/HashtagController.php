<?php

namespace App\Http\Controllers;

use App\Models\Hashtag;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;


class HashtagController extends Controller
{
    public function index()
    {
        return Hashtag::all();
    }

    // Phương thức tìm kiếm hashtag và tự động thêm nếu không có
    public function search(Request $request)
    {
        // Validate query parameter
        $request->validate([
            'query' => 'required|string|max:255',
        ]);

        try {
            // Lấy chuỗi tìm kiếm từ yêu cầu
            $query = $request->input('query');

            // Loại bỏ dấu ngoặc nhọn và các ký tự không mong muốn
            $cleanedQuery = str_replace(['{', '}'], '', $query);

            // Tìm kiếm hashtag có chứa chuỗi đã loại bỏ dấu ngoặc
            $hashtags = Hashtag::where('name', 'like', '%' . $cleanedQuery . '%')
                ->orderBy('name')
                ->limit(10) // Giới hạn kết quả trả về nếu cần
                ->get();

            // Trả về danh sách các hashtag tìm được
            return response()->json($hashtags, 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while searching for hashtags.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Phương thức lưu hashtag mới
    public function store(Request $request)
    {
        // Kiểm tra xem hashtag đã tồn tại chưa
        $request->validate([
            'name' => 'required|string|max:255|unique:hashtags,name',
        ]);

        try {
            $hashtag = Hashtag::create([
                'name' => $request->input('name'),
            ]);

            return response()->json([
                'hashtag_id' => $hashtag->id,
                'name' => $hashtag->name,
                'created_at' => $hashtag->created_at,
                'updated_at' => $hashtag->updated_at,
            ], 201);
        } catch (\Exception $e) {
            \Log::error('Error creating hashtag: ' . $e->getMessage());
            return response()->json(['error' => 'Could not create hashtag. Details: ' . $e->getMessage()], 500);
        }
    }

    public function searchOrCreate(Request $request)
    {
        // Validate the incoming query parameter
        $request->validate([
            'query' => 'required|string|max:255',
        ]);

        // Tìm kiếm hashtags chứa từ khóa người dùng nhập
        $hashtags = Hashtag::where('name', 'like', '%' . $request->query('query') . '%')->get();

        // Nếu tìm thấy hashtags, trả về danh sách
        if ($hashtags->isNotEmpty()) {
            return response()->json($hashtags, 200);
        }

        // Nếu không tìm thấy hashtags, tạo mới
        $newHashtag = Hashtag::create([
            'name' => $request->query('query'),
        ]);

        return response()->json([
            'message' => 'Hashtag created successfully.',
            'hashtag_id' => $newHashtag->id,
            'name' => $newHashtag->name,
        ], 201);
    }
    public function show($id)
    {
        try {
            $hashtag = Hashtag::findOrFail($id);
            return response()->json($hashtag);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Hashtag not found'], 404);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            // Update validation to use the correct primary key (id)
            $request->validate([
                'name' => 'required|string|unique:hashtags,name,' . $id . ',id', // Change 'hashtag_id' to 'id'
            ]);

            // Find the hashtag by id
            $hashtag = Hashtag::findOrFail($id);

            // Update the hashtag
            $hashtag->update($request->all());

            // Return the updated hashtag
            return response()->json($hashtag);

        } catch (ValidationException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Hashtag not found'], 404);
        } catch (\Exception $e) {
            // Log the exact error message for easier debugging
            return response()->json(['message' => 'An error occurred', 'details' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            // Find the hashtag or fail
            $hashtag = Hashtag::findOrFail($id);

            // Delete the hashtag
            $hashtag->delete();

            // Prepare the response with a success message
            $response = [
                'message' => 'Hashtag deleted successfully',
                'hashtag_id' => $id
            ];

            // Return the response with a 200 OK status code
            return response()->json($response, 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Hashtag not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['message' => 'An error occurred'], 500);
        }
    }

    public function getByID($id)
    {
        try {
            // Find all blogs related to the hashtag through the hashtag_blog table
            $blogs = DB::table('hashtag_blog')
                ->join('blogs', 'hashtag_blog.blog_id', '=', 'blogs.blog_id')
                ->where('hashtag_blog.hashtag_id', $id)
                ->select('blogs.*')
                ->get();

            // Check if no blogs were found
            if ($blogs->isEmpty()) {
                return response()->json([
                    'message' => 'No blogs found for this hashtag.',
                ], 404);
            }

            // Return the list of blogs
            return response()->json($blogs, 200);

        } catch (\Exception $e) {
            // Handle the error and return a failed response
            return response()->json([
                'message' => 'Error retrieving blogs.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

}