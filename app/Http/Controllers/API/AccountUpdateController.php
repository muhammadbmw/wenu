<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\StripeAccount;
use App\Models\User;
use App\Models\Profile;
use App\Models\FoodSafety;
use Stripe;

class AccountUpdateController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        Stripe\Stripe::setApiKey(env('STRIPE_SECRET'));
		$endpoint_secret = 'whsec_12S6D1Q40j0oKJ2dCWtxMjG62LOT7HHO';
		$payload = @file_get_contents('php://input');
		$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
		$event = null;

		try {
			$event = Stripe\Webhook::constructEvent(
				$payload, $sig_header, $endpoint_secret
			);
		}catch(\UnexpectedValueException $e) {
			// Invalid payload.
			 $response = [
					'success' => false,
					'message' => 'Invalid payload',
				];
			return response()->json($response, 400);
			//return $response->withStatus(400);
			
		} catch(\Stripe\Exception\SignatureVerificationException $e) {
			 // Invalid Signature.
			 $response = [
					'success' => false,
					'message' => 'Invalid signature',
				];
			return response()->json($response, 400);
			//return $response->withStatus(400);
		}
		
		// Handle the account.update event
		if ($event->type == 'account.updated') {
			  $account = $event->data->object;
			  $account_id = $account->id;
			  $this->handleAccountUpdate($account_id);	  		 
		}
		$response = [
					'success' => true,
					'account_id' =>  $account_id
				];
			return response()->json($response, 200);
		//return $response->withStatus(200);


    }
	private function handleAccountUpdate($account_id) {
		 $stripeAccount = StripeAccount::where('account_id',$account_id)->first();
		if($stripeAccount) {
			Stripe\Stripe::setApiKey(env('STRIPE_SECRET'));
			$account = Stripe\Account::retrieve(
				  $account_id,
				  []
				);
			if($account->details_submitted){
				$stripeAccount->details_submitted = 1;
				$stripeAccount->save();
				//update chef status if satisfy
				$user_id = $stripeAccount->user_id;
				$profile = Profile::where('user_id',$user_id)->first();
				if($profile){
					$foodSafety = FoodSafety::where('user_id',$user_id)->first();
					if($foodSafety){
						if($foodSafety->status == 'active'){
							$user = User::where('id',$user_id)->first();
							$user->chef_status = 1;
							if($user->foodie_status == 0)
								$user->foodie_status = 1;
							$user->save();
						}
					}
				}
			}
		}
	}
}
