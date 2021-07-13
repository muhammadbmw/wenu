<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ChefDelivery;
use Validator;
use Auth;

class ChefDeliveryController extends Controller
{
    public function store(Request $request)
    {
		$validator = Validator::make($request->all(), [
			'delivery' => 'required|in:0,1',
			'delivery_range' => 'required|integer',
			'charge_per_delivery' => 'required|numeric',
        ]);
        if ($validator->fails()) {
            $response = [
                'success' => false,
                'data' => $validator->errors(),
                'message' => 'Validation Error.'
            ];
            return response()->json($response, 200);
        }
		$user_id = Auth::id();
		$chefDelivery = ChefDelivery::where('user_id',$user_id)->first();
		if(empty($chefDelivery)){
			$chefDelivery = new ChefDelivery;
			$chefDelivery->user_id = $user_id;
		}
		$chefDelivery->delivery = $request->delivery;
		$chefDelivery->delivery_range = $request->delivery_range;
		$chefDelivery->charge_per_delivery = $request->charge_per_delivery;
		$chefDelivery->save();
		
		$response = [
            'success' => true,
            'message' => 'Food delivery has updated successfully.'
        ];
        return response()->json($response, 200);
	}
		
}
