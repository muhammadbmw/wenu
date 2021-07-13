<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Validator;
use DB;
use Auth;

class ChefUpcomingOrderController extends Controller
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
			'start_date' =>'required|date_format:Y-m-d',
			'end_date' =>'required|date_format:Y-m-d',
			'option' => 'required|in:pickup,delivery,all'
        ]);
        if ($validator->fails()) {
            $response = [
                'success' => false,
                'data' => $validator->errors(),
                'message' => 'Validation Error.'
            ];
            return response()->json($response, 200);
        }
		$start_date = $request->start_date;
		$end_date =  $request->end_date;
		$option = $request->option; 
        $user_id = Auth::id();
		if($option == 'all'){
			$carts = DB::table('orders')
					->join('order_details','orders.id','=','order_details.order_id')
					->join('carts','order_details.cart_id','=','carts.id')
					->join('menus','carts.menu_id','=','menus.id')
					->join('users','orders.foodie_id','=','users.id')
					//->join('profiles','users.id','=','profiles.user_id')
				   ->where([
							['orders.chef_id',$user_id],
							['orders.status','active'],
							['carts.date','>=',$start_date],
							['carts.date','<=',$end_date],
							])
					// ->whereBetween('carts.date', [$start_date, $end_date])
					->select('carts.quantity','carts.price','carts.pickupOrDelivery as option','carts.date','carts.available','carts.address','cook_notes','driver_notes','menus.name','menus.image','users.name as foodie_name','users.email as foodie_email')
					->orderBy('date','asc')
					->orderBy('menus.name','asc')
					->get();
		} else {
			$carts = DB::table('orders')
					->join('order_details','orders.id','=','order_details.order_id')
					->join('carts','order_details.cart_id','=','carts.id')
				   ->join('menus','carts.menu_id','=','menus.id')
					->join('users','orders.foodie_id','=','users.id')
					//->join('profiles','users.id','=','profiles.user_id')
				   ->where([
							['orders.chef_id',$user_id],
							['orders.status','active'],
							['carts.pickupOrDelivery',$option],
							])
					 ->whereBetween('carts.date', [$start_date, $end_date])
					->select('carts.quantity','carts.price','carts.pickupOrDelivery as option','carts.date','carts.available','carts.address','cook_notes','driver_notes','menus.name','menus.image','users.name as foodie_name','users.email as foodie_email')
					->orderBy('date','asc')
					->orderBy('menus.name','asc')
					->get();
		}

		$response = [
            'success' => true,
            'data' =>  $carts
        ];
        return response()->json($response, 200);
    }
}
