<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\StripeAccount;
use Illuminate\Support\Facades\Auth;
use Stripe;


class CheckOnboardingController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        $user_id = Auth::id();
		$stripeAccount = StripeAccount::where('user_id',$user_id)->first();
		if($stripeAccount) {
			$account_id = $stripeAccount->account_id;
			Stripe\Stripe::setApiKey(env('STRIPE_SECRET'));
			$account = Stripe\Account::retrieve(
				  $account_id,
				  []
				);
			if($account->details_submitted){
				$stripeAccount->details_submitted = 1;
				$stripeAccount->save();
				$response = [
					'success' => true,
					'onboarding' => true
				];
			}
			else {
				$response = [
					'success' => false,
					'onboarding' => false
				];
			}
				
	
		}
		else {
			 $response = [
				'success' => false,
				'message' => 'No Stripe Account'
				];
		}
		return response()->json($response, 200);
    }
}
