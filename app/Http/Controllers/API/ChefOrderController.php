<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Validator;
use DB;
use Auth;

class ChefOrderController extends Controller
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
			'current_date' =>'required|date_format:Y-m-d',
        ]);
        if ($validator->fails()) {
            $response = [
                'success' => false,
                'data' => $validator->errors(),
                'message' => 'Validation Error.'
            ];
            return response()->json($response, 200);
        }
		$current_date  = $request->current_date;
		$chef_id = Auth::id();
		
		$orders = DB::table('orders')
					->join('users','orders.foodie_id','=','users.id')
					//->join('profiles','users.id','=','profiles.user_id')
					->join('payments','orders.payment_id','=','payments.id')
					->where([
						['orders.chef_id',$chef_id],
						['orders.status','active']
					])
					->orderBy('orders.id','desc')
					->select('orders.id as order_id',DB::raw("DATE_FORMAT(orders.created_at,'%Y-%m-%d') as order_date"),'users.name as foodie_name','users.email as foodie_email','payments.transfer_amount')
					->get();
		if($orders){
			foreach($orders as $order) {
				$order_id = $order->order_id;
				$items_count = DB::table('order_details')
								->join('carts','order_details.cart_id','=','carts.id')
								->where('order_details.order_id',$order_id)
								->sum('carts.quantity');
				$order->items_count = (int)$items_count;
				$items = DB::table('order_details')
								->join('carts','order_details.cart_id','=','carts.id')
								->join('menus','carts.menu_id','=','menus.id')
								->where('order_details.order_id',$order_id)
								->select('menus.name','carts.quantity','carts.date')
								->get();
				foreach($items as $item){
					$date = $item->date;
					if(strtotime($date) >= strtotime($current_date)){
						$item->status = 'Pending';
						
					}
					else {
						$item->status = 'Completed';
					}
				}
				$order->items = $items;
			}
		}
		$response = [
            'success' => true,
            'data' =>  $orders
        ];
        return response()->json($response, 200);
    }
}
