<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use Illuminate\Http\Request;
use App\Models\Menu;
use App\Models\MenuAvailability;
use App\Models\User;
use App\Models\ChefDelivery;
use Validator;
use DB;
use Auth;

class CartController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
			'current_time' =>'required|string',
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
        $user_id = Auth::id();
		//gets each chef on the cart
		$chefs = DB::table('carts')
				   ->join('menus','carts.menu_id','=','menus.id')
				   ->join('users','users.id', '=','menus.user_id')
				    ->where([
								['carts.user_id',$user_id],
								['carts.status','active'],
								])
				   ->select('users.id as chef_id','users.name as chef_name')
				   ->groupBy('users.id','users.name')
				   ->get();
		
		foreach($chefs as $chef){
			$chef_id = $chef->chef_id;
			$chefDelivery = ChefDelivery::where('user_id',$chef_id)->first();
			if($chefDelivery)
				$chef->charge_per_delivery = $chefDelivery->charge_per_delivery;
			else
				$chef->charge_per_delivery = 0;
		
			$carts = DB::table('carts')
					   ->join('menus','carts.menu_id','=','menus.id')
					   ->where([
								['carts.user_id',$user_id],
								['carts.status','active'],
								['menus.user_id',$chef_id]
								])->orderBy('carts.id','asc')
						->select('carts.id','carts.cook_notes','carts.quantity','carts.price','carts.pickupOrDelivery as option','carts.date','carts.available','carts.driver_notes','carts.address','menus.name','carts.menu_id','menus.image','menus.max_portions')
						->get();
			
			foreach($carts as $cart){
				$option = $cart->option;
				$cart_id = $cart->id;
				$quantity = $cart->quantity;
				$menu_id = $cart->menu_id;	
				$max_portions = $cart->max_portions;
				if(is_null($cart->image))
					$cart->image = '';
				//if($quantity > 1)
					//$cart->price =  sprintf('%0.2f',$cart->price * $quantity);
				if(is_null($cart->cook_notes))
					$cart->cook_notes = '';
				if($option == 'pickup') {
					
					$pickup_time = strtotime($cart->date);
					$day = $this->getDay(date("l",$pickup_time));
					
					//menu availability
					$menuAvailability = MenuAvailability::where([
									['menu_id', $menu_id],
									['day',$day]
									])->first();
					$start_time = $menuAvailability->start_time;
					$pickup_start_time = date("Y-m-d H:i",strtotime($cart->date." ".$start_time));
					$cutoff_time = $menuAvailability->cutoff_time.' '.$menuAvailability->unit;
					$cutoff_end_time = strtotime(date("Y-m-d H:i", strtotime('-'.$cutoff_time, strtotime($pickup_start_time))));
					
					if($current_time > $cutoff_end_time ) {
						$cart->flag = false;
						$cart->message = 'This Pickup date window has passed.';
					}
					else {
						$cart->flag = true;
						$cart->message = '';
					}
					$cart->driver_notes = '';
					$cart->address = '';				
				}
				if($option == 'delivery') {
					//$deliver = Deliver::where('cart_id',$cart_id)->first();
					//$cart->date = $deliver->date;
					//$cart->available = $deliver->available;
					$deliver_time = strtotime($cart->date);
					$day = $this->getDay(date("l",$deliver_time));
					
					//menu availability
					$menuAvailability = MenuAvailability::where([
									['menu_id', $menu_id],
									['day',$day]
									])->first();
					$start_time = $menuAvailability->start_time;
					$deliver_start_time = date("Y-m-d H:i",strtotime($cart->date." ".$start_time));
					$cutoff_time = $menuAvailability->cutoff_time.' '.$menuAvailability->unit;
					$cutoff_end_time = strtotime(date("Y-m-d H:i", strtotime('-'.$cutoff_time, strtotime($deliver_start_time))));
					
					
					if($current_time > $cutoff_end_time) {
						$cart->flag = false;
						$cart->message = 'This delivery date window has passed.';
					}
					else {
						$cart->flag = true;
						$cart->message = '';
					}
					//$cart->address = $deliver->address;
					if(is_null($cart->driver_notes))
						$cart->driver_notes = '';
				}
				//check max portions
				if($cart->flag){
					$order_quantity = DB::table('orders')
							->join('order_details','orders.id','=','order_details.order_id')
							->join('carts','order_details.cart_id','=','carts.id')
						   ->where([
									['carts.date',$cart->date],
									['orders.status','active'],
									['carts.menu_id',$menu_id],
									])
							->sum('carts.quantity');
					$order_quantity =  (int)$order_quantity;
					$available = $max_portions - $order_quantity;
					if($quantity > $available){
						$cart->flag = false;
						$cart->message = "Quantity exceeds the maximum portions available.";
					}
				}
			}
			$chef->items = $carts;
		}
		$response = [
            'success' => true,
            'data' =>  $chefs
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
        $validator = Validator::make($request->all(), [
            'quantity' => 'required|integer',
            'cook_notes' => 'nullable|string',
			'pickupOrDelivery' =>'required|in:pickup,delivery',
			'menu_id' =>'required|integer',
			'date' => 'required|date_format:Y-m-d',
			'available' => 'required|string',
			'address' => 'required_if:pickupOrDelivery,delivery',
			'driver_notes' => 'nullable|string'
        ]);
        if ($validator->fails()) {
            $response = [
                'success' => false,
                'data' => $validator->errors(),
                'message' => 'Validation Error.'
            ];
            return response()->json($response, 200);
        }
		$cart = new Cart;
		$cart->user_id = Auth::id();
		$cart->menu_id = $request->menu_id;
		$cart->pickupOrDelivery = $request->pickupOrDelivery;
		$cart->quantity = $request->quantity;
		$price = Menu::where('id',$request->menu_id)->value('price');
		$chef_id = Menu::where('id',$request->menu_id)->value('user_id');
		$cart->price =  sprintf('%0.2f',$price * $request->quantity);
		$cart->chef_id = $chef_id;
		if($request->filled('cook_notes'))
			$cart->cook_notes = $request->cook_notes;
		$cart->date = $request->date;
		$cart->available = $request->available;
		if($request->filled('address'))
			$cart->address = $request->address;
		if($request->filled('driver_notes'))
				$cart->driver_notes = $request->driver_notes;
		$cart->save();
	
		
		$response = [
            'success' => true,
            'message' => 'Added to cart successfully.'
        ];
        return response()->json($response, 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Cart  $cart
     * @return \Illuminate\Http\Response
     */
    public function show(Cart $cart)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Cart  $cart
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Cart $cart)
    {
        $validator = Validator::make($request->all(), [
            'quantity' => 'nullable|integer',
            'cook_notes' => 'nullable|string',
			'address' => 'nullable|string',
			'driver_notes' => 'nullable|string'
        ]);
        if ($validator->fails()) {
            $response = [
                'success' => false,
                'data' => $validator->errors(),
                'message' => 'Validation Error.'
            ];
            return response()->json($response, 200);
        }
		
		if ($request->filled('quantity')) {
			$cart->quantity = $request->quantity;
			$price = Menu::where('id',$cart->menu_id)->value('price');
			$cart->price =  sprintf('%0.2f',$price * $request->quantity);
		}
		if ($request->filled('cook_notes')) {
			$cart->cook_notes = $request->cook_notes;
		}
		if( $request->filled('driver_notes')){
				$cart->driver_notes = $request->driver_notes;
			}
		if( $request->filled('address')){
				$cart->address = $request->address;
			}
		
		$cart->save();
		$response = [
            'success' => true,
            'message' => 'Cart updated successfully.'
        ];
        return response()->json($response, 200);
		
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Cart  $cart
     * @return \Illuminate\Http\Response
     */
    public function destroy(Cart $cart)
    {
        $cart->delete();
		$response = [
            'success' => true,
            'message' => 'Removed successfully.'

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
