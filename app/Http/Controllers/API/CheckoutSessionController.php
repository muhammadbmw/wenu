<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Stripe;
use App\Models\StripeAccount;
use App\Models\CheckoutSession;
use App\Models\User;
use Validator;
use Auth;
use DB;

class CheckoutSessionController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
		$validator = Validator::make($request->all(), [
			'chef_id' =>'required|integer',
			'cart_ids' => 'required|array',
        ]);
        if ($validator->fails()) {
            $response = [
                'success' => false,
                'data' => $validator->errors(),
                'message' => 'Validation Error.'
            ];
            return response()->json($response, 200);
        }
		$foodie_id = Auth::id();
		$foodie_email = Auth::user()->email;
		
		$chef_id = $request->chef_id;
		$cart_ids = $request->input('cart_ids');
		$chef_email = User::where('id',$chef_id)->value('email');
		$account_id = StripeAccount::where('user_id',$chef_id)->value('account_id');
		$product = [];
		$subtotal = 0;
		foreach($cart_ids as $cart_id){
			$item = [];
			$item = DB::table('carts')
						->join('menus','carts.menu_id','=','menus.id')
						->where('carts.id',$cart_id)
						->select('quantity','name','price','image')
						->first();
			
			$subtotal += $item->price * $item->quantity;
			array_push($product, [
				'price_data' => [
				  'currency' => 'cad',
				  'unit_amount' => $item->price * 100,
				  'product_data' => [
					'name' => $item->name,
					'images' => [$item->image],
				  ],
				],
				'quantity' => $item->quantity,
				 'tax_rates' => ['txr_1IxCIYHvEncyRFYapDGwFvoM']
			]);
		}
		$subtotal += $subtotal * 0.13;
		 
		$application_fee_amount = sprintf('%0.2f',$subtotal * 0.10);
		
		$cart = implode(',',$cart_ids);
		
        Stripe\Stripe::setApiKey(env('STRIPE_SECRET'));
		header('Content-Type: application/json');

		$YOUR_DOMAIN = 'https://app.wenueat.com/foodie/checkout';

		$checkout_session = Stripe\Checkout\Session::create([
		  'payment_method_types' => ['card'],
		  'line_items' => [$product],
		  'metadata' => ['cart_id' => $cart,'chef_id' => $chef_id],
		  'payment_intent_data' => [
			'application_fee_amount' => $application_fee_amount * 100,
			'receipt_email' => $chef_email,
			'transfer_data' => [
			  'destination' => $account_id,
			],
		  ],
		  'customer_email' => $foodie_email,
		   'mode' => 'payment',
		  'success_url' => $YOUR_DOMAIN . '?success=true',
		  'cancel_url' => $YOUR_DOMAIN . '?canceled=true',
		]);

		//echo json_encode(['id' => $checkout_session->id]);
		//create checkout_session in the database
		$checkoutSession = new CheckoutSession;
		$checkoutSession->session_id = $checkout_session->id;
		$checkoutSession->user_id = $foodie_id;
		$checkoutSession->save();
		
		$response = [
					'success' => true,
					'id' => $checkout_session->id
				];
		return response()->json($response, 200);
    }
}
