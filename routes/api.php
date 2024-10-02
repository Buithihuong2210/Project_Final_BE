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
use App\Http\Controllers\ResponseController;
use App\Http\Controllers\ShippingController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\VoucherController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\MoMoPaymentController;





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

Route::prefix('responses')->group(function () {
    Route::get('/', [ResponseController::class, 'index']); // List all responses
});

Route::prefix('users')->group(function () {
    Route::get('/{id}', [UserController::class, 'getUserById']);
});

// User routes
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Survey routes for users
    Route::prefix('surveys')->group(function () {
        Route::post('/{survey_id}/responses', [ResponseController::class, 'store']); // Submit a response for a specific survey
    });


    // User routes for Cart, Products, Blogs, etc.
    Route::prefix('cart')->group(function () {
        Route::get('/', [CartController::class, 'index']);       // List all items in cart
        Route::post('/', [CartController::class, 'store']);      // Add item to cart
        Route::get('/{id}', [CartController::class, 'show']);    // Show specific cart
        Route::put('/{item}', [CartController::class, 'update']); // Update specific cart item
        Route::delete('/{item}', [CartController::class, 'destroy']); // Delete specific cart item
    });

// Order routes
    Route::prefix('orders')->group(function () {
        Route::post('/', [OrderController::class, 'store']);  // Create a new order
        Route::get('/' ,[OrderController::class, 'showAll']);
    });

    Route::prefix('products')->group(function () {
        Route::get('/', [ProductController::class, 'index']); // List all products
        Route::get('/{product_id}', [ProductController::class, 'show']); // Show a specific product
    });

    Route::prefix('reviews')->group(function () {
        Route::post('/{product_id}', [ReviewController::class, 'store']); // Create a review for a product
        Route::get('/product/{product_id}', [ReviewController::class, 'index']); // Get all reviews for a specific product
        Route::put('/{review_id}', [ReviewController::class, 'update']); // Update a review
        Route::delete('/{review_id}', [ReviewController::class, 'destroy']); // Delete a specific review
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
        Route::put('/{product_id}/status', [ProductController::class, 'changeStatus']); // Change product status
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
        Route::get('/', [ShippingController::class, 'index']); // List all shipping records
        Route::get('/{shipping_id}', [ShippingController::class, 'show']); // Get a specific shipping record
        Route::post('/', [ShippingController::class, 'store']); // Create a new shipping record
        Route::put('/{shipping_id}', [ShippingController::class, 'update']); // Update a shipping record
        Route::delete('/{shipping_id}', [ShippingController::class, 'destroy']); // Delete a specific shipping record
    });
    // Admin routes for managing vouchers
    Route::prefix('vouchers')->group(function () {
        Route::get('/', [VoucherController::class, 'index']); // List all vouchers
        Route::post('/', [VoucherController::class, 'store']); // Create a new voucher
        Route::get('/{voucher_id}', [VoucherController::class, 'show']); // Show a specific voucher
        Route::put('/{voucher_id}', [VoucherController::class, 'update']); // Update a specific voucher
        Route::delete('/{voucher_id}', [VoucherController::class, 'destroy']); // Delete a specific voucher
        // Route to change voucher status
        Route::post('/{voucher_id}/status', [VoucherController::class, 'changeStatus']); // Change status of voucher
    });

    Route::prefix('surveys')->group(function () {
        Route::post('/', [SurveyController::class, 'store']); // Create a new survey
        Route::get('/', [SurveyController::class, 'index']); // List all surveys
        Route::get('/{survey_id}', [SurveyController::class, 'show']); // Show a specific survey
        Route::put('/{survey_id}', [SurveyController::class, 'update']); // Update a specific survey
        Route::delete('/{survey_id}', [SurveyController::class, 'destroy']); // Delete a specific survey
    });

    // Question management routes
    Route::prefix('surveys/{survey_id}/questions')->group(function () {
        Route::post('/', [QuestionController::class, 'store']); // Add a question to a specific survey
        Route::get('/', [QuestionController::class, 'index']); // List questions for a specific survey
        Route::get('/{question_id}', [QuestionController::class, 'show']); // Show a specific question
        Route::put('/{question_id}', [QuestionController::class, 'update']); // Update a specific question
        Route::delete('/{question_id}', [QuestionController::class, 'destroy']); // Delete a specific question
    });

    // Response management routes (optional, if admins need to see all responses)
    Route::prefix('responses')->group(function () {
        Route::get('/', [ResponseController::class, 'index']); // List all responses
        Route::get('/{response_id}', [ResponseController::class, 'show']); // Show a specific response
        Route::delete('/{response_id}', [ResponseController::class, 'destroy']); // Delete a specific response
    });

    Route::post('/momo-payment', [MoMoPaymentController::class, 'createPayment']);
});









