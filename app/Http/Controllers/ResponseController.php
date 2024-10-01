<?php

namespace App\Http\Controllers;

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
        // Validate that 'responses' is provided and is an array
        $validated = $request->validate([
            'responses' => 'required|array', // Expecting an array of response objects
        ]);

        try {
            // Check if the survey exists (this will throw an exception if not found)
            Survey::findOrFail($survey_id);

            // Loop through the responses and create records
            foreach ($validated['responses'] as $response) {
                // Find the question to get its options
                $question = Question::findOrFail($response['question_id']); // Assuming you have a Question model

                // Check if the answer is valid (it must be in the options)
                if ($question->type === 'multiple_choice') {
                    $validOptions = $question->options; // Assuming options are stored as a JSON array or similar
                    if (!in_array($response['answer'], $validOptions)) {
                        return response()->json(['error' => 'Invalid answer for question ' . $response['question_id']], 400);
                    }
                }

                // Create the response record
                Response::create([
                    'survey_id' => $survey_id,
                    'question_id' => $response['question_id'], // Get the question ID
                    'user_id' => auth()->id(), // Assuming you have authentication set up
                    'answer_text' => $response['answer'], // Store the answer
                ]);
            }

            // Return a success message with a 201 status code
            return response()->json(['message' => 'Response submitted successfully.'], 201);

        } catch (ModelNotFoundException $e) {
            // Return a 404 error if the survey or question is not found
            return response()->json(['error' => 'Survey or question not found.'], 404);
        } catch (\Exception $e) {
            // Return a 500 error for any other failure
            dd($e); // Debug detailed error message
            return response()->json(['error' => 'Failed to submit response.'], 500);
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
