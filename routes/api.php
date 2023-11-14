<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\APIController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::post('/register', [APIController::class, 'register']);
Route::post('/login', [APIController::class, 'login']);

Route::get('/logout', [APIController::class, 'logout']);

Route::group(['middleware'=> 'auth:api'], function(){
    Route::post('/create', [APIController::class, 'create']);
    Route::post('/update', [APIController::class, 'update']);
    Route::get('/view', [APIController::class, 'get_todos']);
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
