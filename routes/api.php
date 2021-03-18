<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\MenuController;
use App\Http\Controllers\API\DishController;
use App\Http\Controllers\API\ForgotPasswordController;
use App\Http\Controllers\API\ResetPasswordController;
use App\Http\Controllers\API\VerificationController;
use App\Http\Controllers\API\MenuAvailabilityController;
use App\Http\Controllers\API\MenuGroupRelController;
use App\Http\Controllers\API\SwitchController;
use App\Http\Controllers\API\TagController;
use App\Http\Controllers\API\MenuMediaController;
use App\Http\Controllers\API\KitchenMediaController;
use App\Http\Controllers\API\MenuTagController;


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
	Route::get('switch_to_foodie', [SwitchController::class,'chef_to_foodie']);
});

Route::middleware(['auth:api','activeChef'])->group(function () {
	Route::apiResource('menu', MenuController::class)
					->only(['index']);
	Route::post('saveMenuSequence',[MenuController::class,'saveSequence']);
	Route::apiResource('dish', DishController::class);
	Route::apiResource('menuAvailability', MenuAvailabilityController::class)
			->only(['index', 'store']);
	//Route::get('menuNotIn', [MenuController::class,'menu_not_in']);
	//Route::apiResource('menuGroupAvailability', MenuGroupAvailabilityController::class)
		//	->only(['index', 'store']);
	//Route::post('menu_group_rel',[MenuGroupRelController::class,'add_or_remove']);
	Route::apiResource('tag', TagController::class)->only(['index']);
	Route::apiResource('menuTag', MenuTagController::class)
					->only(['index', 'store', 'destroy']);
	Route::apiResource('menuMedia', MenuMediaController::class)
					->only(['index', 'store', 'destroy']);
	Route::apiResource('kitchenMedia', KitchenMediaController::class)
					->only(['index', 'store', 'destroy','update']);
});

//foodie api
Route::middleware(['auth:api','foodie'])->group(function () {
	Route::get('switch_to_chef', [SwitchController::class,'foodie_to_chef']);
});
