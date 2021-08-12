<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Stripe;
use App\Models\StripeAccount;
use App\Models\CheckoutSession;
use App\Models\User;
use App\Models\Profile;
use App\Models\PlatformFee;
use App\Models\ChefDelivery;
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
		//platform fees
		$platformFee = PlatformFee::where('id',1)->first();
		$service_fee = $platformFee->service_fee;
		$afp =  $platformFee->application_fee_percent;
		
		$chef_id = $request->chef_id;
		$cart_ids = $request->input('cart_ids');
		$chef_email = User::where('id',$chef_id)->value('email');
		$chef_province = Profile::where('user_id',$chef_id)->value('province');
		//get tax rate
		$tax_rate = $this->get_tax_rate($chef_province);
		//get tax percent
		$tax_percent = $this->get_tax_percent($chef_province);
		
		$account_id = StripeAccount::where('user_id',$chef_id)->value('account_id');
		$product = [];
		$subtotal = 0;
		$delivery_date = [];
		$items = DB::table('carts')
						->join('menus','carts.menu_id','=','menus.id')
						->whereIn('carts.id',$cart_ids)
						->select('quantity','name','menus.price','image','pickupOrDelivery','date')
						->get();			
		foreach($items as $item){
			$subtotal += $item->price * $item->quantity;
			if(is_null($item->image))
				$item->image ='https://api.wenueat.com/public/storage/images/default.png';
			array_push($product, [
				'price_data' => [
				  'currency' => 'cad',
				  'unit_amount' => $item->price * 100,
				  'product_data' => [
					'name' => $item->name,
					'description' => ucfirst($item->pickupOrDelivery).' Date: '.$item->date,
					'images' => [$item->image],
				  ],
				],
				'quantity' => $item->quantity,
				 'tax_rates' => [$tax_rate]
			]);
			//check if item delivery
			if($item->pickupOrDelivery == 'delivery')
				array_push($delivery_date,$item->date);
		}
		//add service fee
		array_push($product, [
				'price_data' => [
				  'currency' => 'cad',
				  'unit_amount' => $service_fee * 100,
				  'product_data' => [
					'name' => 'Service fee',
				  ],
				],
				'quantity' => 1,
				 'tax_rates' => [$tax_rate]
			]);
		//add delivery fee
		$n = count($delivery_date);
		$delivery = $this->countDistinct($delivery_date, $n);
		if($delivery>0){
			$delivery_fee = ChefDelivery::where('user_id',$chef_id)->value('charge_per_delivery');
			array_push($product, [
				'price_data' => [
				  'currency' => 'cad',
				  'unit_amount' => $delivery_fee * 100,
				  'product_data' => [
					'name' => 'Delivery fee',
				  ],
				],
				'quantity' => $delivery,
				 'tax_rates' => [$tax_rate]
			]);
			$subtotal += $delivery_fee * $delivery;
		}
			
		$subtotal += sprintf('%0.2f',($subtotal * $tax_percent));

		$application_fee_amount = sprintf('%0.2f',$subtotal * $afp);
		$transfer_amount = sprintf('%0.2f',($subtotal - $application_fee_amount));
		
		$cart = implode(',',$cart_ids);
		
        Stripe\Stripe::setApiKey(env('STRIPE_SECRET'));
		header('Content-Type: application/json');

		$YOUR_DOMAIN = 'https://app.wenueat.com/foodie/checkout';

		$checkout_session = Stripe\Checkout\Session::create([
		  'payment_method_types' => ['card'],
		  'line_items' => [$product],
		  'metadata' => ['cart_id' => $cart,'chef_id' => $chef_id,'afm' => $application_fee_amount,'sf' => $service_fee],
		  'payment_intent_data' => [
			//'receipt_email' => $chef_email,
			'transfer_data' => [
				'amount' => $transfer_amount * 100,
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
	//get tax rate based on province
	private function get_tax_rate($province)
	{
		$tax_rate = null;
		$region = strtoupper($province);
		$tax5 = 'txr_1JCoWXD6DBg5bJPg8hE9rQIZ';
		$tax13 = 'txr_1JCoUuD6DBg5bJPgkOTxzbJ7';
		$tax15 = 'txr_1JCoY0D6DBg5bJPgjDO3xbXU';
		switch ($region) {
                case "AB":
                    $tax_rate = $tax5;
                    break;
                case "BC":
                    $tax_rate = $tax5;
                    break;
                case "MB":
                    $tax_rate = $tax5;
                    break;
                case "NB":
                    $tax_rate = $tax15;
                    break;
                case "NL":
                   $tax_rate = $tax15;
                    break;
                case "NS":
                    $tax_rate = $tax15;
                    break;
                case "NT":
                    $tax_rate = $tax5;
                    break;
                case "NU":
                    $tax_rate = $tax5;
                    break;
                case "ON":
                    $tax_rate = $tax13;
                    break;
                case "PE":
                    $tax_rate = $tax15;
                    break;
                case "QC":
                   $tax_rate = $tax5;
                    break;
                case "SK":
                    $tax_rate = $tax5;
                    break;
                case "YT":
                    $tax_rate = $tax5;
                    break;
			}
			return $tax_rate;
	}
	//get tax percent based on province
	private function get_tax_percent($province)
	{
		$tax_percent = 0;
		$region = strtoupper($province);
		$tax5 = 0.05;
		$tax13 = 0.13;
		$tax15 = 0.15;
		switch ($region) {
                case "AB":
                    $tax_percent = $tax5;
                    break;
                case "BC":
                    $tax_percent = $tax5;
                    break;
                case "MB":
                    $tax_percent = $tax5;
                    break;
                case "NB":
                    $tax_percent = $tax15;
                    break;
                case "NL":
                   $tax_percent = $tax15;
                    break;
                case "NS":
                    $tax_percent = $tax15;
                    break;
                case "NT":
                    $tax_percent = $tax5;
                    break;
                case "NU":
                    $tax_percent = $tax5;
                    break;
                case "ON":
                    $tax_percent = $tax13;
                    break;
                case "PE":
                    $tax_percent = $tax15;
                    break;
                case "QC":
                   $tax_percent = $tax5;
                    break;
                case "SK":
                    $tax_percent = $tax5;
                    break;
                case "YT":
                    $tax_percent = $tax5;
                    break;
			}
			return $tax_percent;
	}
	private function countDistinct( &$arr, $n)
	{
		if($n>0)
			$count = 1;
		else
			$count = 0;
	 
		for ( $i = 1; $i < $n; $i++)
		{
	 
			for ($j = 0; $j < $i; $j++)
				if ($arr[$i] == $arr[$j])
					break;
	 
			if ($i == $j)
				$count++;
		}
		return $count;
	}
}
