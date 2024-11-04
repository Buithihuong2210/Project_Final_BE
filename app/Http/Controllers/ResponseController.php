<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Response;
use App\Models\Survey;
use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;



class ResponseController extends Controller
{
    // Store a new response for a survey
    public function store(Request $request, $survey_id)
    {
        // Validate rằng 'responses' đã được cung cấp và là một mảng
        $validated = $request->validate([
            'responses' => 'required|array',
        ]);

        try {
            // Kiểm tra xem khảo sát có tồn tại không
            Survey::findOrFail($survey_id);

            $answers = [];

            // Lặp qua các phản hồi và lưu trữ
            foreach ($validated['responses'] as $response) {
                $question = Question::findOrFail($response['question_id']);

                // Lưu câu trả lời vào mảng
                $answers[$response['question_id']] = $response['answer'];

                // Tạo bản ghi phản hồi
                Response::create([
                    'survey_id' => $survey_id,
                    'question_id' => $response['question_id'],
                    'user_id' => auth()->id(),
                    'answer_text' => $response['answer'],
                ]);
            }

            // Lọc sản phẩm dựa trên câu trả lời
            $recommendedProducts = Product::query()
                ->when(isset($answers[29]), function ($query) use ($answers) {
                    return $query->where('target_skin_type', $answers[29]); // Lọc theo loại da
                })
                ->when(isset($answers[30]), function ($query) use ($answers) {
                    return $query->where('product_type', $answers[30]); // Lọc theo loại sản phẩm
                })
                ->when(isset($answers[34]), function ($query) use ($answers) {
                    return $query->where('main_ingredient', $answers[34]); // Lọc theo thành phần chính
                })
                ->get();

            return response()->json([
                'message' => 'Response submitted successfully.',
                'recommended_products' => $recommendedProducts,
            ], 201);

        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Survey or question not found.'], 404);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to submit response.'], 500);
        }
    }

// Phương thức để lấy sản phẩm khuyến nghị dựa trên phản hồi của người dùng
    protected function getRecommendedProducts($responses)
    {
        // Tạo một mảng để lưu trữ các điều kiện tìm kiếm sản phẩm
        $conditions = [];

        // Định nghĩa ánh xạ giữa ID câu hỏi và thuộc tính sản phẩm
        $questionToProductField = [
            1 => 'target_skin_type', // ID câu hỏi loại da
            2 => 'product_type', // ID câu hỏi ưu tiên sản phẩm
            3 => 'main_ingredient', // ID câu hỏi thành phần chính
            // Thêm ánh xạ cho các câu hỏi khác nếu cần
        ];

        foreach ($responses as $response) {
            // Kiểm tra xem ID câu hỏi có trong ánh xạ không
            if (array_key_exists($response['question_id'], $questionToProductField)) {
                $productField = $questionToProductField[$response['question_id']];
                $conditions[$productField] = $response['answer']; // Lưu trữ điều kiện
            }
        }

        // Tìm kiếm các sản phẩm khuyến nghị dựa trên các điều kiện
        $recommendedProducts = Product::where($conditions)->get();

        return $recommendedProducts;
    }



    // Show a specific response by its ID
    public function show($response_id)
    {
        try {
            // Find the response by its ID
            $response = Response::findOrFail($response_id);

            // Return the response data with a 200 status code
            return response()->json($response, 200);

        } catch (ModelNotFoundException $e) {
            // Return a 404 error if the response is not found
            return response()->json(['error' => 'Response not found.'], 404);
        } catch (\Exception $e) {
            // Return a 500 error for any other failure
            return response()->json(['error' => 'Failed to retrieve response.'], 500);
        }
    }

    // List all responses
    public function index()
    {
        try {
            // Retrieve all response records with related questions and surveys
            $responses = Response::with(['question', 'survey'])->get();

            // Check if responses are empty
            if ($responses->isEmpty()) {
                return response()->json(['error' => 'No responses found.'], 404);
            }

            // Format the response to include question_text and survey title
            $formattedResponses = $responses->map(function ($response) {
                return [
                    'response_id' => $response->response_id,
                    'question_id' => $response->question_id,
                    'question_text' => $response->question->question_text,
                    'category' => $response->question->category,
                    'user_id' => $response->user_id,
                    'survey_id' => $response->survey_id,
                    'title' => $response->survey->title,
                    'answer_text' => $response->answer_text,
                    'created_at' => $response->created_at,
                    'updated_at' => $response->updated_at,
                ];
            });

            // Return the list of formatted responses with a 200 status code
            return response()->json($formattedResponses, 200);

        } catch (\Exception $e) {
            // Log the exception message
            \Log::error('Failed to retrieve responses: ' . $e->getMessage());

            // Return a 500 error if fetching responses fails
            return response()->json(['error' => 'Failed to retrieve responses.'], 500);
        }
    }

    // Update a specific response by its ID
    public function update(Request $request, $response_id)
    {
        // Validate that 'answer_text' is provided
        $validated = $request->validate([
            'answer_text' => 'required|string', // Expecting answer_text to be a string
        ]);

        try {
            // Find the response by its ID
            $response = Response::findOrFail($response_id);

            // Update the response
            $response->update($validated);

            // Return the updated response data with a 200 status code
            return response()->json($response, 200);

        } catch (ModelNotFoundException $e) {
            // Return a 404 error if the response is not found
            return response()->json(['error' => 'Response not found.'], 404);
        } catch (\Exception $e) {
            // Return a 500 error for any other failure
            return response()->json(['error' => 'Failed to update response.'], 500);
        }
    }

    // Delete a specific response by its ID
    public function destroy($response_id)
    {
        try {
            // Find the response by its ID
            $response = Response::findOrFail($response_id);

            // Delete the response from the database
            $response->delete();

            // Return a success message with a 204 No Content status
            return response()->json(['message' => 'Response deleted successfully.'], 204);

        } catch (ModelNotFoundException $e) {
            // Return a 404 error if the response is not found
            return response()->json(['error' => 'Response not found.'], 404);
        } catch (\Exception $e) {
            // Return a 500 error for any other failure
            return response()->json(['error' => 'Failed to delete response.'], 500);
        }
    }
}
