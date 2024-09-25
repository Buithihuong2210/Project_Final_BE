<?php

namespace App\Http\Controllers;

use App\Models\Question;
use Illuminate\Http\Request;

class QuestionController extends Controller
{
    // Store a new question
    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'survey_id' => 'required|exists:surveys,survey_id',
                'question_text' => 'required|string',
                'question_type' => 'required|string|in:text,multiple_choice',
                'options' => 'array|required_if:question_type,multiple_choice', // Require options if it's a multiple_choice
                'options.*' => 'string', // Each option should be a string
            ]);

            $question = Question::create($validatedData);
            return response()->json($question, 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'An error occurred while creating the question', 'error' => $e->getMessage()], 500);
        }
    }

    // Get all questions for a specific survey
    public function index($survey_id)
    {
        try {
            $questions = Question::where('survey_id', $survey_id)->get();
            return response()->json($questions);
        } catch (\Exception $e) {
            return response()->json(['message' => 'An error occurred while fetching questions', 'error' => $e->getMessage()], 500);
        }
    }

    // Get a specific question
    public function show($question_id)
    {
        try {
            $question = Question::findOrFail($question_id);
            return response()->json($question);
        } catch (\Exception $e) {
            return response()->json(['message' => 'An error occurred while fetching the question', 'error' => $e->getMessage()], 500);
        }
    }

    // Update a question
    public function update(Request $request, $question_id)
    {
        try {
            $validatedData = $request->validate([
                'question_text' => 'nullable|string',
                'question_type' => 'nullable|string|in:text,multiple_choice',
                'options' => 'array|required_if:question_type,multiple_choice', // Require options if it's a multiple_choice
                'options.*' => 'string', // Each option should be a string
            ]);

            $question = Question::findOrFail($question_id);
            $question->update($validatedData);
            return response()->json($question);
        } catch (\Exception $e) {
            return response()->json(['message' => 'An error occurred while updating the question', 'error' => $e->getMessage()], 500);
        }
    }

    // Delete a question
    public function destroy($question_id)
    {
        try {
            $question = Question::findOrFail($question_id);
            $question->delete();
            return response()->json(null, 204);
        } catch (\Exception $e) {
            return response()->json(['message' => 'An error occurred while deleting the question', 'error' => $e->getMessage()], 500);
        }
    }
}
