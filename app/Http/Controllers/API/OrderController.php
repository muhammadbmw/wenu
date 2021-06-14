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
use App\Models\Menu;
use App\models\Payment;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $foodie_id = Auth::id();
		$orders = DB::table('orders')
					->join('users','orders.chef_id','=','users.id')
					->join('profiles','users.id','=','profiles.user_id')
					->join('payments','orders.payment_id','=','payments.id')
					->where('orders.foodie_id',$foodie_id)
					->orderBy('orders.id','desc')
					->select('orders.id as order_id',DB::raw("DATE_FORMAT(orders.created_at,'%Y-%m-%d') as order_date"),'orders.chef_id','users.name as chef_name','profiles.image as chef_image','payments.total')
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
								->select('menus.name','carts.quantity')
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
        //
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
}
