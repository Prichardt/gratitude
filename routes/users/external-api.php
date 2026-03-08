<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Gratitude\ImportController;

// Define external API routes for users and security here if needed
// Route::get('users/profile', [UserController::class, 'externalProfile']);

Route::post('/gratitude/import', [ImportController::class, 'import']);
