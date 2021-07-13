<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Validator;
use DB;
use Auth;
use App\Models\Menu;
use App\Models\MenuAvailability;
use App\Models\ChefDelivery;

class AddToCartController extends Controller
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
		$menu_id = $request->menu_id;
		
		$menu = Menu::where('id',$menu_id)->first();
		$user_id = $menu->user_id;
		$data['name'] = $menu->name;
		$data['description'] = $menu->description;
		$data['price'] = $menu->price;
		$data['max_portions'] = $menu->max_portions;
		$data['option'] = $menu->options;
		//check chef delivery
		$chefDelivery = ChefDelivery::where('user_id',$user_id)->first();
		if($chefDelivery){
			$delivery = $chefDelivery->delivery? true: false;
		}
		else 
			$delivery = false;
		$data['delivery'] = $delivery;
		
		//menu availability
		$menuAvailability = MenuAvailability::where([
								['menu_id', $menu_id],
								['status','active']
								])
								->select('day','start_time','end_time','cutoff_time','unit')
								->orderBy('day')->get();
		
		$data['availability'] = $menuAvailability;
		$response = [
            'success' => true,
            'data' =>  $data
        ];
        return response()->json($response, 200);
		
    }
	private function getDay($val) {
		$day = null;
		switch($val) {
			case 0:
				$day = 'Monday';
				break;
			case 1:
				$day = 'Tuesday';
				break;
			case 2:
				$day = 'Wednesday';
				break;
			case 3:
				$day = 'Thursday';
				break;
			case 4:
				$day = 'Friday';
				break;
			case 5:
				$day = 'Saturday';
				break;
			case 6:
				$day = 'Sunday';
				break;
		}
		return $day;
	}
}
