<?php

namespace App\Http\Controllers;

use App\Models\Answer;
use App\Models\Question;
use Illuminate\Http\Request;

class AnswerController extends Controller
{
    // Store a new answer
// Store a new answer
    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'question_id' => 'required|exists:questions,question_id', // Ensure this references the correct column
                'answer_text' => 'required|string',
                'question_type' => 'required|in:text,multiple_choice',
            ]);

            // Fetch the question using question_id
            $question = Question::where('question_id', $validatedData['question_id'])->firstOrFail(); // Corrected

            // Handle text answer
            if ($validatedData['question_type'] === 'text') {
                $answer = Answer::create([
                    'question_id' => $validatedData['question_id'],
                    'answer_text' => $validatedData['answer_text'],
                ]);
            }

            // Handle multiple choice answer
            if ($validatedData['question_type'] === 'multiple_choice') {
                if (!in_array($validatedData['answer_text'], $question->options)) {
                    return response()->json(['message' => 'Invalid answer option'], 422);
                }

                $answerIndex = array_search($validatedData['answer_text'], $question->options) + 1;
                $answer = Answer::create([
                    'question_id' => $validatedData['question_id'],
                    'answer_text' => $answerIndex, // Store the index as the answer
                ]);
            }

            return response()->json($answer, 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'An error occurred while creating the answer', 'error' => $e->getMessage()], 500);
        }
    }

    // Get all answers for a specific question
    public function index($question_id)
    {
        try {
            $answers = Answer::where('question_id', $question_id)->get();
            return response()->json($answers);
        } catch (\Exception $e) {
            return response()->json(['message' => 'An error occurred while fetching answers', 'error' => $e->getMessage()], 500);
        }
    }

    // Get a specific answer
    public function show($answer_id)
    {
        try {
            $answer = Answer::findOrFail($answer_id);
            return response()->json($answer);
        } catch (\Exception $e) {
            return response()->json(['message' => 'An error occurred while fetching the answer', 'error' => $e->getMessage()], 500);
        }
    }

    // Update an answer
    public function update(Request $request, $answer_id)
    {
        try {
            $validatedData = $request->validate([
                'answer_text' => 'required|string',
                'question_type' => 'required|in:text,multiple_choice',
            ]);

            // Fetch the answer and the associated question
            $answer = Answer::findOrFail($answer_id);
            $question = Question::findOrFail($answer->question_id);

            // Handle text answer
            if ($validatedData['question_type'] === 'text') {
                $answer->update(['answer_text' => $validatedData['answer_text']]);
            }

            // Handle multiple choice answer
            if ($validatedData['question_type'] === 'multiple_choice') {
                if (!in_array($validatedData['answer_text'], $question->options)) {
                    return response()->json(['message' => 'Invalid answer option'], 422);
                }

                $answerIndex = array_search($validatedData['answer_text'], $question->options) + 1;
                $answer->update(['answer_text' => $answerIndex]);
            }

            return response()->json($answer);
        } catch (\Exception $e) {
            return response()->json(['message' => 'An error occurred while updating the answer', 'error' => $e->getMessage()], 500);
        }
    }

    // Delete an answer
    public function destroy($answer_id)
    {
        try {
            $answer = Answer::findOrFail($answer_id);
            $answer->delete();
            return response()->json(null, 204);
        } catch (\Exception $e) {
            return response()->json(['message' => 'An error occurred while deleting the answer', 'error' => $e->getMessage()], 500);
        }
    }
}
