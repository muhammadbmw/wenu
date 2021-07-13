<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MenuAvailability;
use Validator;
use DB;


class CheckoutFilterController extends Controller
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
			'current_time' =>'required',
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
		$current_time = $request->current_time;
		$cart_ids = $request->input('cart_ids');
		$carts = DB::table('carts')
						->join('menus','carts.menu_id','=','menus.id')
						->whereIn('carts.id',$cart_ids)
						->select('carts.id','carts.quantity','carts.pickupOrDelivery as option','carts.date','carts.menu_id','menus.max_portions')
						->get();
		$error = [];
		$status = true;
		foreach($carts as $cart){
			$cart_id = $cart->id;
			$quantity = $cart->quantity;
			$date = $cart->date;
			$menu_id = $cart->menu_id;
			$max_portions = $cart->max_portions;
			//check max portions
			$order_quantity = DB::table('orders')
					->join('order_details','orders.id','=','order_details.order_id')
					->join('carts','order_details.cart_id','=','carts.id')
				   ->where([
							['carts.date',$date],
							['orders.status','active'],
							['carts.menu_id',$menu_id],
							])
					->sum('carts.quantity');
			$order_quantity =  (int)$order_quantity;
			$available = $max_portions - $order_quantity;
			if($quantity > $available){
				$msg = array("menu_id" => $menu_id, "message" => "Max portion exceeded.");
				array_push($error,$msg);
				$status = false;
			}
			//check cut off time
			$day = $this->getDay(date("l",strtotime($date)));
			$menuAvailability = MenuAvailability::where([
							['menu_id', $menu_id],
							['day',$day]
							])->first();
			$start_time = $menuAvailability->start_time;
			$pickup_start_time = date("Y-m-d H:i",strtotime($date." ".$start_time));
			$cutoff_time = $menuAvailability->cutoff_time.' '.$menuAvailability->unit;
			$cutoff_end_time = strtotime(date("Y-m-d H:i", strtotime('-'.$cutoff_time, strtotime($pickup_start_time))));
			
			if($current_time > $cutoff_end_time ) {
				$msg = array("menu_id" => $menu_id, "message" => "This ".$cart->option." date window has passed.");
				array_push($error,$msg);
				$status = false;
			}
		}
		$response = [
            'success' => true,
            'status' =>  $status,
			'error' => $error
        ];
        return response()->json($response, 200);
    }
	
	private function getDay($val) {
		$day = null;
		switch($val) {
			case 'Monday':
				$day = 0;
				break;
			case 'Tuesday':
				$day = 1;
				break;
			case 'Wednesday':
				$day = 2;
				break;
			case 'Thursday':
				$day = 3;
				break;
			case 'Friday':
				$day = 4;
				break;
			case 'Saturday':
				$day = 5;
				break;
			case 'Sunday':
				$day = 6;
				break;
		}
		return $day;
	}
}
