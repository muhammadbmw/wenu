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
		$stripeAccount = StripeAccount::where('user_id',$user_id)->first();
		if($stripeAccount) {
			$account_id = $stripeAccount->account_id;
			Stripe\Stripe::setApiKey(env('STRIPE_SECRET'));
			$account_links = Stripe\AccountLink::create([
			  'account' => $account_id,
			  'refresh_url' => 'https://api.wenueat.com/api/onboarding_refresh',
			  'return_url' => 'https://api.wenueat.com/api/onboarding_return',
			  'type' => 'account_onboarding',
			]);
			$link = $account_links->url;
			
			$response = [
					'success' => true,
					'message' => 'Please go to the onboarding link within 5 minutes otherwise it will expire',
					'link' => $link
				];
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
