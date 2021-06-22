<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Validator;
use Auth;
use DB;

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
					->select('carts.id','carts.quantity','carts.pickupOrDelivery as option','carts.date','carts.available','menus.name','carts.price',DB::raw("IFNULL(menus.image,'') as image"))
					->get();
		
		$orderDetails->items = $carts;			
		$response = [
            'success' => true,
            'data' =>  $orderDetails
        ];
        return response()->json($response, 200);
    }
}
