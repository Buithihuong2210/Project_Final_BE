<?php

namespace App\Http\Controllers;

use App\Models\Response;
use Illuminate\Http\Request;

class ResponseController extends Controller
{
    // Store a new response
    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'question_id' => 'required|exists:questions,question_id',
                'answer_id' => 'nullable|exists:answers,answer_id',
                'response_text' => 'nullable|string',
            ]);

            $response = Response::create([
                'user_id' => auth()->id(),
                'question_id' => $validatedData['question_id'],
                'answer_id' => $validatedData['answer_id'] ?? null,
                'response_text' => $validatedData['response_text'] ?? null,
            ]);

            return response()->json($response, 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'An error occurred while creating the response', 'error' => $e->getMessage()], 500);
        }
    }

    // Get all responses for a specific question
    public function index($question_id)
    {
        try {
            $responses = Response::where('question_id', $question_id)->get();
            return response()->json($responses);
        } catch (\Exception $e) {
            return response()->json(['message' => 'An error occurred while fetching responses', 'error' => $e->getMessage()], 500);
        }
    }
}

