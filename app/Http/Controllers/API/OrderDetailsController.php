<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Validator;
use Auth;
use DB;
use App\Models\Pickup;
use App\Models\Deliver;

class OrderDetailsController extends Controller
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
			'order_id' =>'required|integer',
        ]);
        if ($validator->fails()) {
            $response = [
                'success' => false,
                'data' => $validator->errors(),
                'message' => 'Validation Error.'
            ];
            return response()->json($response, 200);
        }
		$order_id = $request->order_id;
		$orderDetails = DB::table('payments')
						->join('orders','payments.id','=','orders.payment_id')
						->where('orders.id',$order_id)
						->select('subtotal','tax','total')
						->first();
		$carts = DB::table('order_details')
					->join('carts','order_details.cart_id','=','carts.id')
					->join('menus','carts.menu_id','=','menus.id')
					->where('order_details.order_id',$order_id)
					->select('carts.id','carts.quantity','carts.pickupOrDelivery as option','menus.name','menus.price','menus.image')
					->get();
		foreach($carts as $cart){
				$option = $cart->option;
				$cart_id = $cart->id;
				$quantity = $cart->quantity;				
				if(is_null($cart->image))
					$cart->image = '';
				if($quantity > 1)
					$cart->price =  sprintf('%0.2f',$cart->price * $quantity);
				if($option == 'pickup') {
					$pickup = Pickup::where('cart_id',$cart_id)->first();
					$cart->date = $pickup->date;
					$cart->available = $pickup->available;
				}
				if($option == 'delivery') {
					$deliver = Deliver::where('cart_id',$cart_id)->first();
					$cart->date = $deliver->date;
					$cart->available = $deliver->available;
				}
		}
		$orderDetails->items = $carts;			
		$response = [
            'success' => true,
            'data' =>  $orderDetails
        ];
        return response()->json($response, 200);
    }
}
