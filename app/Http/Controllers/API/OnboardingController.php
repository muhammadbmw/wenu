<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\StripeAccount;
use Illuminate\Support\Facades\Auth;
use Stripe;

class OnboardingController extends Controller
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
		Stripe\Stripe::setApiKey(env('STRIPE_SECRET'));
		$stripeAccount = StripeAccount::where('user_id',$user_id)->first();
		$link = '';
		if($stripeAccount) {
			$account_id = $stripeAccount->account_id;
			$account_links = Stripe\AccountLink::create([
			  'account' => $account_id,
			  'refresh_url' => 'https://api.wenueat.com/api/onboarding_refresh',
			  'return_url' => 'https://api.wenueat.com/api/onboarding_return',
			  'type' => 'account_onboarding',
			]);
			$link = $account_links->url;
			
		}
		else {
			//create stripe account 
				$account = Stripe\Account::create([
				  'country' => 'CA',
				  'type' => 'express',
				   'capabilities' => [
					'card_payments' => [
					  'requested' => true,
					],
					'transfers' => [
					  'requested' => true,
					],
				  ],
				]);

				$account_id = $account->id;
				$stripeAccount = new StripeAccount;
				$stripeAccount->account_id = $account_id;
				$stripeAccount->details_submitted = 0;
				$stripeAccount->user_id = $user_id;;
				$stripeAccount->save();
				
			$account_links = Stripe\AccountLink::create([
			  'account' => $account_id,
			  'refresh_url' => 'https://api.wenueat.com/api/onboarding_refresh',
			  'return_url' => 'https://api.wenueat.com/api/onboarding_return',
			  'type' => 'account_onboarding',
			]);
			$link = $account_links->url;	
			
		}
		$response = [
					'success' => true,
					'message' => 'Please go to the onboarding link within 5 minutes otherwise it will expire',
					'link' => $link
				];
		return response()->json($response, 200);

    }
}
