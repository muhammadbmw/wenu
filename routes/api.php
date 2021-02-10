<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\MenuController;
use App\Http\Controllers\API\ForgotPasswordController;
use App\Http\Controllers\API\ResetPasswordController;
use App\Http\Controllers\API\VerificationController;
use App\Http\Controllers\API\MenuGroupAvailabilityController;
use App\Http\Controllers\API\MenuGroupRelController;

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
Route::post('social-login', [UserController::class,'social_login']);
Route::post('chefRegistration', [UserController::class,'chef_register']);
Route::post('foodieRegistration', [UserController::class,'foodie_register']);
Route::post('/forgot-password',ForgotPasswordController::class);
Route::post('/reset-password',ResetPasswordController::class);
Route::get('email/verify/{id}', [VerificationController::class,'verify'])
->name('verification.verify');
Route::post('email/resend', [VerificationController::class,'resend'])
->name('verification.resend');


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
	Route::get('menuNotIn', [MenuController::class,'menu_not_in']);
	Route::apiResource('menuGroupAvailability', MenuGroupAvailabilityController::class)
			->only([
    'index', 'store'
		]);
;
	Route::post('menu_group_rel',[MenuGroupRelController::class,'add_or_remove']);
});

