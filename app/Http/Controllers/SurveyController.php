<?php

namespace App\Http\Controllers;

use App\Models\Survey;
use Illuminate\Http\Request;

class SurveyController extends Controller
{
    // Store a new survey
    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
            ]);

            $survey = Survey::create([
                'user_id' => auth()->id(),
                'title' => $validatedData['title'],
                'description' => $validatedData['description'] ?? null,
            ]);

            return response()->json($survey, 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'An error occurred while creating the survey', 'error' => $e->getMessage()], 500);
        }
    }

    // Get a list of all surveys
    public function index()
    {
        try {
            $surveys = Survey::all();
            return response()->json($surveys);
        } catch (\Exception $e) {
            return response()->json(['message' => 'An error occurred while fetching surveys', 'error' => $e->getMessage()], 500);
        }
    }
}

