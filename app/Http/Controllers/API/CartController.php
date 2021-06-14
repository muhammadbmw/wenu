<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Pickup;
use App\Models\Deliver;
use Illuminate\Http\Request;
use App\Models\Menu;
use App\Models\MenuAvailability;
use App\Models\User;
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
		           ->where('carts.status','active')
				   ->join('menus','carts.menu_id','=','menus.id')
				   ->join('users','users.id', '=','menus.user_id')
				   ->select('users.id as chef_id','users.name as chef_name')
				   ->groupBy('users.id','users.name')
				   ->get();
		foreach($chefs as $chef){
			$chef_id = $chef->chef_id;
			
			$carts = DB::table('carts')
					   ->join('menus','carts.menu_id','=','menus.id')
					   ->where([
								['carts.user_id',$user_id],
								['carts.status','active'],
								['menus.user_id',$chef_id]
								])->orderBy('carts.id','asc')
						->select('carts.id','carts.cook_notes','carts.quantity','carts.pickupOrDelivery as option','menus.name','menus.price','carts.menu_id','menus.image')
						->get();
			
			foreach($carts as $cart){
				$option = $cart->option;
				$cart_id = $cart->id;
				$quantity = $cart->quantity;
				//$chef_id = $cart->chef_id;
				$menu_id = $cart->menu_id;
				
				if(is_null($cart->image))
					$cart->image = '';
				//$cart->chef_name = User::where('id',$chef_id)->value('name');
				if($quantity > 1)
					$cart->price =  sprintf('%0.2f',$cart->price * $quantity);
				if(is_null($cart->cook_notes))
					$cart->cook_notes = '';
				if($option == 'pickup') {
					$pickup = Pickup::where('cart_id',$cart_id)->first();
					$cart->date = $pickup->date;
					$cart->available = $pickup->available;
					$pickup_time = strtotime($pickup->date);
					$day = $this->getDay(date("l",$pickup_time));
					
					//menu availability
					$menuAvailability = MenuAvailability::where([
									['menu_id', $menu_id],
									['day',$day]
									])->first();
					$start_time = $menuAvailability->start_time;
					$pickup_start_time = date("Y-m-d H:i",strtotime($pickup->date." ".$start_time));
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
					$deliver = Deliver::where('cart_id',$cart_id)->first();
					$cart->date = $deliver->date;
					$cart->available = $deliver->available;
					$deliver_time = strtotime($deliver->date);
					$day = $this->getDay(date("l",$deliver_time));
					
					//menu availability
					$menuAvailability = MenuAvailability::where([
									['menu_id', $menu_id],
									['day',$day]
									])->first();
					$start_time = $menuAvailability->start_time;
					$deliver_start_time = date("Y-m-d H:i",strtotime($deliver->date." ".$start_time));
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
					$cart->address = $deliver->address;
					if(is_null($deliver->driver_notes))
						$cart->driver_notes = '';
					else
						$cart->driver_notes = $deliver->driver_notes;
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
		if($request->filled('cook_notes'))
			$cart->cook_notes = $request->cook_notes;
		$cart->save();
	
		if($request->pickupOrDelivery == 'pickup'){
			$pickup = new Pickup;
			$pickup->date = $request->date;
			$pickup->available = $request->available;
			$pickup->cart_id = $cart->id;
			$pickup->save();
		}
		if($request->pickupOrDelivery == 'delivery'){
			$deliver = new Deliver;
			$deliver->date = $request->date;
			$deliver->available = $request->available;
			$deliver->address = $request->address;
			$deliver->cart_id = $cart->id;
			if($request->filled('driver_notes'))
				$deliver->driver_notes = $request->driver_notes;
			$deliver->save();
		}
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
		}
		if ($request->filled('cook_notes')) {
			$cart->cook_notes = $request->cook_notes;
		}
		if( $request->filled('address') || $request->filled('driver_notes') ) {
			$deliver = Deliver::where('cart_id',$cart->id)->first();
			if( $request->filled('address')){
				$deliver->address = $request->address;
			}
			if( $request->filled('driver_notes')){
				$deliver->driver_notes = $request->driver_notes;
			}
			$deliver->save();
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
