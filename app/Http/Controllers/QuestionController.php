<?php

namespace App\Http\Controllers;

use App\Models\Question;
use App\Models\Survey;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class QuestionController extends Controller
{
    // Create a new question for a specific survey
    public function store(Request $request, $survey_id)
    {
        try {
            // Validate the incoming request
            $validator = Validator::make($request->all(), [
                'question_text' => 'required|string|max:255',
                'type' => 'required|string|in:multiple_choice,text',
                'options' => 'required_if:type,multiple_choice|array|min:2',
                'options.*' => 'required_if:type,multiple_choice|string',
                'category' => 'required|string|in:Interest,Goal,Factor',
            ]);

            // If validation fails, return errors
            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Find the survey
            $survey = Survey::findOrFail($survey_id);

            // Create the question
            $question = $survey->questions()->create([
                'question_text' => $request->input('question_text'),
                'type' => $request->input('type'),
                'options' => $request->input('type') === 'multiple_choice' ? $request->input('options') : null,
                'category' => $request->input('category'),
            ]);

            return response()->json([
                'message' => 'Question created successfully',
                'data' => $question,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create question',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // List all questions for a specific survey
    public function index($survey_id)
    {
        try {
            $survey = Survey::findOrFail($survey_id);
            $questions = $survey->questions;

            return response()->json([
                'message' => 'Questions retrieved successfully',
                'data' => $questions,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve questions',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Show a specific question
    public function show($survey_id, $question_id)
    {
        try {
            $survey = Survey::findOrFail($survey_id);
            $question = $survey->questions()->findOrFail($question_id);

            return response()->json([
                'message' => 'Question retrieved successfully',
                'data' => $question,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve question',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Update a specific question
    public function update(Request $request, $survey_id, $question_id)
    {
        try {
            // Validate the incoming request
            $validator = Validator::make($request->all(), [
                'question_text' => 'sometimes|required|string|max:255',
                'type' => 'sometimes|required|string|in:multiple_choice,text',
                'options' => 'sometimes|required_if:type,multiple_choice|array|min:2',
                'options.*' => 'required_if:type,multiple_choice|string',
                'category' => 'sometimes|required|string|in:Interest,Goal,Factor', // Validate the category field
            ]);

            // If validation fails, return errors
            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Find the survey and question
            $survey = Survey::findOrFail($survey_id);
            $question = $survey->questions()->findOrFail($question_id);

            // Update the question
            $question->update([
                'question_text' => $request->input('question_text', $question->question_text),
                'type' => $request->input('type', $question->type),
                'options' => $request->input('type') === 'multiple_choice' ? $request->input('options', $question->options) : null,
                'category' => $request->input('category', $question->category), // Update category
            ]);

            return response()->json([
                'message' => 'Question updated successfully',
                'data' => $question,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update question',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Delete a specific question
    public function destroy($survey_id, $question_id)
    {
        try {
            $survey = Survey::findOrFail($survey_id);
            $question = $survey->questions()->findOrFail($question_id);

            $question->delete();

            return response()->json([
                'message' => 'Question deleted successfully',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete question',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
