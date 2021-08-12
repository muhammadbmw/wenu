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
use App\Http\Controllers\API\FoodSafetyController;
use App\Http\Controllers\API\ScheduledDishesController;
use App\Http\Controllers\API\CookNearController;
use App\Http\Controllers\API\DishDetailsController;
use App\Http\Controllers\API\ChefKitchenController;
use App\Http\Controllers\API\ChefScheduledDishesController;
use App\Http\Controllers\API\CartController;
use App\Http\Controllers\API\AddToCartController;
use App\Http\Controllers\API\OnboardingController;
use App\Http\Controllers\API\OnboardingReturnController;
use App\Http\Controllers\API\OnboardingRefreshController;
use App\Http\Controllers\API\CheckOnboardingController;
use App\Http\Controllers\API\AccountUpdateController;
use App\Http\Controllers\API\CheckoutSessionController;
use App\Http\Controllers\API\CheckoutSessionCompletedController;
use App\Http\Controllers\API\OrderController;
use App\Http\Controllers\API\OrderDetailsController;
use App\Http\Controllers\API\UpcomingPickupDeliveryController;
use App\Http\Controllers\API\ChefOrderController;
use App\Http\Controllers\API\StripeDashboardController;
use App\Http\Controllers\API\ChefCancelOrderController;
use App\Http\Controllers\API\ChefUpcomingOrderController;
use App\Http\Controllers\API\ChefDeliveryController;
use App\Http\Controllers\API\CheckDeliveryRangeController;
use App\Http\Controllers\API\OrderQuantityController;
use App\Http\Controllers\API\CheckoutFilterController;

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
Route::get('guest_login', [UserController::class,'guest_login']);
Route::post('social-login', [UserController::class,'social_login']);
Route::post('chefRegistration', [UserController::class,'chef_register']);
Route::post('foodieRegistration', [UserController::class,'foodie_register']);
Route::post('/forgot-password',ForgotPasswordController::class);
Route::post('/reset-password',ResetPasswordController::class);
Route::get('email/verify/{id}', [VerificationController::class,'verify'])
->name('verification.verify');
Route::post('email/resend', [VerificationController::class,'resend'])
->name('verification.resend');
Route::get('onboarding_return',OnboardingReturnController::class);
Route::get('onboarding_refresh',OnboardingRefreshController::class);
Route::post('account_updated',AccountUpdateController::class);
Route::post('checkout_session_completed',CheckoutSessionCompletedController::class);

Route::middleware('auth:api')->group(function () {
    Route::post('logout', [UserController::class,'logout']);
	Route::apiResource('order',OrderController::class)
						->only(['update']);
});

//chef api
Route::middleware(['auth:api','chef'])->group(function () {
	Route::get('chefProfile', [UserController::class,'chef_profile']);
	Route::post('updateChefProfile', [UserController::class,'update_chef_profile']);
	Route::get('switch_to_foodie', [SwitchController::class,'chef_to_foodie']);
	
	Route::apiResource('dish', DishController::class)->only(['index']);
	
	Route::apiResource('tag', TagController::class)->only(['index']);
	Route::apiResource('menuTag', MenuTagController::class)
					->only(['index', 'store', 'destroy']);
	Route::apiResource('kitchenMedia', KitchenMediaController::class)
					->only(['index', 'store', 'destroy','update']);
	Route::apiResource('foodSafety', FoodSafetyController::class)
					->only(['index', 'store']);
	Route::get('onboarding',OnboardingController::class);
	Route::get('check_onboarding',CheckOnboardingController::class);
	
	Route::apiResource('menu', MenuController::class)
					->only(['index']);
	Route::get('chef_order',ChefOrderController::class);
	Route::get('chef_cancel_order',ChefCancelOrderController::class);
	Route::get('stripe_dashboard',StripeDashboardController::class);
	Route::get('chef_upcoming_order',ChefUpcomingOrderController::class);
	//Route::post('saveMenuSequence',[MenuController::class,'saveSequence']);
	
	Route::apiResource('menuAvailability', MenuAvailabilityController::class)
			->only(['index']);
		
	Route::apiResource('menuMedia', MenuMediaController::class)
					->only(['index']);
	Route::post('chefDelivery', [ChefDeliveryController::class,'store']);
					
});

Route::middleware(['auth:api','activeChef'])->group(function () {
	
	Route::apiResource('dish', DishController::class)->only(['store','update']);
	Route::post('dishImage', [DishController::class,'update_dish']);
	Route::post('saveMenuSequence',[MenuController::class,'saveSequence']);
	
	Route::apiResource('menuAvailability', MenuAvailabilityController::class)
			->only(['store']);
		
	Route::apiResource('menuMedia', MenuMediaController::class)
					->only(['store', 'destroy']);
	
});

//foodie or guest api
Route::middleware(['auth:api','foodieOrGuest'])->group(function () {
	Route::post('scheduledDishes',ScheduledDishesController::class);
	Route::post('checkDeliveryRange',CheckDeliveryRangeController::class);
	Route::post('cooksNear',CookNearController::class);
	Route::get('dishDetails',DishDetailsController::class);
	Route::get('chefKitchen',ChefKitchenController::class);
	Route::post('chefScheduledDishes',ChefScheduledDishesController::class);
});

//foodie api
Route::middleware(['auth:api','foodie'])->group(function () {
	Route::get('foodieProfile', [UserController::class,'foodie_profile']);
	Route::post('updateFoodieProfile', [UserController::class,'update_foodie_profile']);
	Route::get('switch_to_chef', [SwitchController::class,'foodie_to_chef']);
	//Route::post('scheduledDishes',ScheduledDishesController::class);
	//Route::post('checkDeliveryRange',CheckDeliveryRangeController::class);
	//Route::post('cooksNear',CookNearController::class);
	//Route::get('dishDetails',DishDetailsController::class);
	//Route::get('chefKitchen',ChefKitchenController::class);
	//Route::post('chefScheduledDishes',ChefScheduledDishesController::class);
	Route::get('addToCart',AddToCartController::class);
	Route::apiResource('cart', CartController::class)
					->only(['index']);
	Route::apiResource('order',OrderController::class)
						->only(['index']);
	Route::get('cancel_order',[OrderController::class,'cancel_order']);
	Route::get('orderDetails',OrderDetailsController::class);
	Route::get('upcoming_pickup_delivery',UpcomingPickupDeliveryController::class);
});

Route::middleware(['auth:api','activeFoodie'])->group(function () {
	Route::apiResource('cart', CartController::class)
					->only(['store', 'destroy','update']);
	Route::post('checkout_session',CheckoutSessionController::class);
	Route::post('check_order_quantity',OrderQuantityController::class);
	Route::post('checkout_filter',CheckoutFilterController::class);
});

//admin api
Route::middleware(['auth:api','admin'])->group(function () {
	Route::get('chefsFoodSafety', [FoodSafetyController::class,'chefsFoodSafety']);
	Route::apiResource('foodSafety', FoodSafetyController::class)
					->only(['update']);
	
});
