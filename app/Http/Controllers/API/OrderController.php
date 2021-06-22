<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use App\Models\Cart;
use App\Models\User;
use Validator;
use DB;
use Auth;
use Stripe;
use App\Models\Menu;
use App\Models\Payment;
use App\Models\Refund;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
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
		$foodie_id = Auth::id();
	
		$orders = DB::table('orders')
					->join('users','orders.chef_id','=','users.id')
					->join('profiles','users.id','=','profiles.user_id')
					->join('payments','orders.payment_id','=','payments.id')
					->where([
						['orders.foodie_id',$foodie_id],
						['orders.status','active']
					])
					->orderBy('orders.id','desc')
					->select('orders.id as order_id',DB::raw("DATE_FORMAT(orders.created_at,'%Y-%m-%d') as order_date"),'orders.chef_id','users.name as chef_name','users.email as chef_email','profiles.mobile as chef_phone','profiles.image as chef_image',DB::raw("CONCAT( IFNULL(CONCAT(profiles.unit,'-'),''),profiles.address,', ',profiles.city,' ',profiles.province,' ',profiles.postal_code) as chef_address"),'payments.total')
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

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Order  $order
     * @return \Illuminate\Http\Response
     */
    public function show(Order $order)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Order  $order
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Order $order)
    {
        $validator = Validator::make($request->all(), [
			'current_time' =>'required|integer',
			'reason' => 'required|string',
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
		$cutoff = date('Y-m-d H:i:s',strtotime(('+1 days'),$current_time));
		$reason = $request->reason;
		$user_id = Auth::id();
		$order_id = $order->id;
		//check order cutoff time not more than 24 hours
		$items = DB::table('order_details')
					->join('carts','order_details.cart_id','=','carts.id')
					->where('order_details.order_id',$order_id)
					->select('carts.date')
					->get();
		$check = true;
		foreach($items as $item){
			$date = $item->date;
			if(strtotime($date) < strtotime($cutoff )){
				$check = false;
				break;
			}
		}
		if(!$check){
			$response = [
				'success' => false,
				'message' =>  'Cancellation cutoff time exceeded'
			];
			return response()->json($response, 200);
		}
		
		if($order->status == 'active'){
			$payment_id = $order->payment_id;
			//get the payment intent for refund
			$payment_intent = Payment::where('id',$payment_id)->value('payment_intent');
			//refund the amount
			Stripe\Stripe::setApiKey(env('STRIPE_SECRET'));
			try{
				$re = Stripe\Refund::create([
					  'payment_intent' => $payment_intent,
					  'reverse_transfer' => true,
					]);
			} catch(Exception $e){
				$response = [
					'success' => false,
					'message' => 'Something went wrong',
				];
				return response()->json($response, 400);
			}
			$refund_id = $re->id;
			$amount = sprintf('%0.2f',($re->amount/100));
			$refund = new Refund;
			$refund->amount = $amount;
			$refund->refund_id = $refund_id;
			$refund->order_id = $order_id;
			$refund->user_id = $user_id;
			$refund->save();
			
			$order->status = "cancelled";
			$order->reason = $reason;
			$order->save();
			
			$response = [
				'success' => true,
				'message' =>  'Order has been cancelled'
			];
		}
		else {
			$response = [
				'success' => false,
				'message' =>  'Order is not active'
			];
		}
		return response()->json($response, 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Order  $order
     * @return \Illuminate\Http\Response
     */
    public function destroy(Order $order)
    {
        //
    }
	//Cancelled order
	public function cancel_order(){
		$foodie_id = Auth::id();
	
		$orders = DB::table('orders')
					->join('users','orders.chef_id','=','users.id')
					->join('profiles','users.id','=','profiles.user_id')
					->join('payments','orders.payment_id','=','payments.id')
					->join('refunds','orders.id','=','refunds.order_id')
					->join('users as staff','refunds.user_id','=','staff.id')
					->where([
						['orders.foodie_id',$foodie_id],
						['orders.status','cancelled']
					])
					->orderBy('orders.id','desc')
					->select('orders.id as order_id',DB::raw("DATE_FORMAT(orders.created_at,'%Y-%m-%d') as order_date"),DB::raw("IFNULL(orders.reason,'') as cancel_reason"),'users.name as chef_name','users.email as chef_email','profiles.mobile as chef_phone','profiles.image as chef_image',DB::raw("CONCAT( IFNULL(CONCAT(profiles.unit,'-'),''),profiles.address,', ',profiles.city,' ',profiles.province,' ',profiles.postal_code) as chef_address"),'payments.total','refunds.amount as refund_amount',DB::raw("DATE_FORMAT(refunds.created_at,'%Y-%m-%d') as cancel_date"),'staff.name as cancel_by')
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
