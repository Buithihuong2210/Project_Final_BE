<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\SocialController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SocialAuthController;
use App\Http\Controllers\ImageController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\HashtagController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\SurveyController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\AnswerController;
use App\Http\Controllers\ResponseController;
use App\Http\Controllers\ShippingController;



/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('users', [UserController::class, 'index']);
Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);
Route::middleware('auth:sanctum')->put('/user/update/{id}', [UserController::class, 'update']);
Route::middleware('auth:sanctum')->delete('/user/{id}', [UserController::class, 'destroy']);


Route::post('password/forgot', [PasswordResetController::class, 'sendResetLink']);
Route::post('password/reset', [PasswordResetController::class, 'reset']);

Route::get('auth/google', [SocialController::class, 'redirectToGoogle']);
Route::get('auth/google/callback', [SocialController::class, 'handleGoogleCallback']);

Route::group(['middleware' => 'web'], function () {
    Route::get('auth/facebook', [SocialAuthController::class, 'redirectToFacebook']);
    Route::get('auth/facebook/callback', [SocialAuthController::class, 'handleFacebookCallback']);
});

Route::prefix('upload')->group(function () {
    Route::post('/', [ImageController::class, 'uploadImage']);
    Route::delete('/{id}', [ImageController::class, 'destroy']); // Delete a specific image
});


// User routes
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::prefix('surveys')->group(function () {
        Route::post('/', [SurveyController::class, 'store']);  // Create a new survey
        Route::get('/', [SurveyController::class, 'index']);   // List all surveys
        Route::get('/{survey_id}', [SurveyController::class, 'show']); // Show a specific survey
        Route::put('/{survey_id}', [SurveyController::class, 'update']); // Update a specific survey
        Route::delete('/{survey_id}', [SurveyController::class, 'destroy']); // Delete a specific survey
    });

    // Question routes
    Route::prefix('questions')->group(function () {
        Route::post('/', [QuestionController::class, 'store']);  // Create a new question
        Route::get('/survey/{survey_id}', [QuestionController::class, 'index']); // List all questions for a survey
        Route::get('/{question_id}', [QuestionController::class, 'show']); // Show a specific question
        Route::put('/{question_id}', [QuestionController::class, 'update']); // Update a specific question
        Route::delete('/{question_id}', [QuestionController::class, 'destroy']); // Delete a specific question
    });

    // Answer routes
    Route::prefix('answers')->group(function () {
        Route::post('/', [AnswerController::class, 'store']);  // Create a new answer
        Route::get('/question/{question_id}', [AnswerController::class, 'index']); // List all answers for a question
        Route::get('/{answer_id}', [AnswerController::class, 'show']); // Show a specific answer
        Route::put('/{answer_id}', [AnswerController::class, 'update']); // Update a specific answer
        Route::delete('/{answer_id}', [AnswerController::class, 'destroy']); // Delete a specific answer
    });

    // Response routes
    Route::prefix('responses')->group(function () {
        Route::post('/', [ResponseController::class, 'store']);  // Create a new response
        Route::get('/question/{question_id}', [ResponseController::class, 'index']); // List all responses for a question
        Route::get('/{response_id}', [ResponseController::class, 'show']); // Show a specific response
        Route::put('/{response_id}', [ResponseController::class, 'update']); // Update a specific response
        Route::delete('/{response_id}', [ResponseController::class, 'destroy']); // Delete a specific response
    });

    // User routes for Cart, Products, Blogs, etc.
    Route::prefix('cart')->group(function () {
        Route::get('/', [CartController::class, 'index']);       // List all items in cart
        Route::post('/', [CartController::class, 'store']);      // Add item to cart
        Route::get('/{id}', [CartController::class, 'show']);    // Show specific cart
        Route::put('/{item}', [CartController::class, 'update']); // Update specific cart item
        Route::delete('/{item}', [CartController::class, 'destroy']); // Delete specific cart item
    });

    Route::prefix('products')->group(function () {
        Route::get('/', [ProductController::class, 'index']); // List all products
        Route::get('/{product_id}', [ProductController::class, 'show']); // Show a specific product
    });

    Route::prefix('blogs')->group(function () {
        Route::post('/', [BlogController::class, 'store']);
        Route::get('/', [BlogController::class, 'showAll']);
        Route::put('/{blog}', [BlogController::class, 'updateUser']);
        Route::get('/{blog}', [BlogController::class, 'show']);

    });

    Route::prefix('comments')->group(function () {
        Route::post('/', [CommentController::class, 'store']); // Create a new comment
        Route::get('/blogs/{blog_id}', [CommentController::class, 'index']); // Fetch comments for a blog
        Route::put('/{comment_id}', [CommentController::class, 'update']); // Update a specific comment
        Route::delete('/{comment_id}', [CommentController::class, 'destroy']); // Delete a specific comment
    });

    Route::prefix('hashtags')->group(function () {
        Route::get('/', [HashtagController::class, 'index']); // List all hashtags
        Route::get('/{hashtag_id}', [HashtagController::class, 'show']); // Show a specific hashtag
    });
});

// Admin routes
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
    // Admin user management routes
    Route::get('users', [UserController::class, 'index']);
    Route::put('/user/update/{id}', [UserController::class, 'update']);
    Route::delete('/user/{id}', [UserController::class, 'destroy']);

    // Admin routes for managing brands and products
    Route::prefix('brands')->group(function () {
        Route::get('/', [BrandController::class, 'index']); // List all brands
        Route::post('/', [BrandController::class, 'store']); // Store a new brand
        Route::get('/{id}', [BrandController::class, 'show']); // Show a specific brand
        Route::put('/{id}', [BrandController::class, 'update']); // Update a specific brand
        Route::delete('/{id}', [BrandController::class, 'destroy']); // Delete a specific brand
    });

    Route::prefix('products')->group(function () {
        Route::get('/', [ProductController::class, 'index']); // List all products
        Route::post('/', [ProductController::class, 'store']); // Store a new product
        Route::get('/{product_id}', [ProductController::class, 'show']); // Show a specific product
        Route::put('/{product_id}', [ProductController::class, 'update']); // Update a specific product
        Route::delete('/{product_id}', [ProductController::class, 'destroy']); // Delete a specific product
    });

    // Admin hashtag management routes
    Route::prefix('hashtags')->group(function () {
        Route::get('/', [HashtagController::class, 'index']); // List all hashtags
        Route::post('/', [HashtagController::class, 'store']); // Store a new hashtag
        Route::get('/{hashtag_id}', [HashtagController::class, 'show']); // Show a specific hashtag
        Route::put('/{hashtag_id}', [HashtagController::class, 'update']); // Update a specific hashtag
        Route::delete('/{hashtag_id}', [HashtagController::class, 'destroy']); // Delete a specific hashtag
    });
    Route::prefix('blogs')->group(function () {
        Route::post('/', [BlogController::class, 'store']);
        Route::put('/{blog_id}', [BlogController::class, 'updateAdmin']);
        Route::put('/changestatus/{blog_id}', [BlogController::class, 'changeStatus']);
        Route::get('/{blog}', [BlogController::class, 'show']);
        Route::delete('/{blog}', [BlogController::class, 'destroy']);
    });
    Route::prefix('shippings')->group(function () {
        Route::get('/', [ShippingController::class, 'index']); // Get all shipping records
        Route::get('/{shipping_id}', [ShippingController::class, 'show']); // Get a specific shipping record
        Route::post('/', [ShippingController::class, 'store']); // Create a new shipping record
        Route::put('/{shipping_id}', [ShippingController::class, 'update']); // Update a shipping record
        Route::delete('/{shipping_id}', [ShippingController::class, 'destroy']); // Delete a shipping record
    });
});









