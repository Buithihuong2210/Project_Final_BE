    <?php

    use Illuminate\Support\Facades\Route;

    Route::get('/not_login', function () {
        return response()->json(['message' => 'You are not logged in'], 403);
    });
