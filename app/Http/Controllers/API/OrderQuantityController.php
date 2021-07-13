<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Validator;
use DB;

class OrderQuantityController extends Controller
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
			'date' =>'required|date_format:Y-m-d',
			'menu_id' =>'required|integer',
        ]);
        if ($validator->fails()) {
            $response = [
                'success' => false,
                'data' => $validator->errors(),
                'message' => 'Validation Error.'
            ];
            return response()->json($response, 200);
        }
		$date = $request->date;
		$menu_id = $request->menu_id;
		$order_quantity = DB::table('orders')
					->join('order_details','orders.id','=','order_details.order_id')
					->join('carts','order_details.cart_id','=','carts.id')
				   ->where([
							['carts.date',$date],
							['orders.status','active'],
							['carts.menu_id',$menu_id],
							])
					->sum('carts.quantity');
		$response = [
            'success' => true,
            'order_quantity' =>  (int)$order_quantity
        ];
        return response()->json($response, 200);
    }
}
