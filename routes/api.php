<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\MenuController;

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
Route::post('register', [UserController::class,'register']);
Route::post('login', [UserController::class,'login']);
Route::post('chefRegistration', [UserController::class,'chef_register']);

/*Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});*/

Route::middleware('auth:api')->group(function () {
    Route::post('logout', [UserController::class,'logout']);
});

//chef api
Route::middleware(['auth:api','chef'])->group(function () {
	Route::get('chefProfile', [UserController::class,'chef_profile']);
	Route::post('updateChefProfile', [UserController::class,'update_chef_profile']);
});

Route::middleware(['auth:api','activeChef'])->group(function () {
	Route::apiResource('menu', MenuController::class);
});

