<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Validator;
use App\Models\Menu;
use App\Models\Profile;
use App\Models\ChefDelivery;

class CheckDeliveryRangeController extends Controller
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
        'menu_id' => 'required|integer',
		'latitude' => 'required',
        'longitude' => 'required'
      
		]);
		if ($validator->fails()) {
			$response = [
				'success' => false,
				'data' => $validator->errors(),
				'message' => 'Validation Error.'
			];
			return response()->json($response, 200);
		}
		
		$latitudeFrom = $request->latitude;
		$longitudeFrom = $request->longitude;
		$menu_id = $request->menu_id;
		$user_id = Menu::where('id',$menu_id)->value('user_id');
		$profile = Profile::where('user_id',$user_id)->first();
		$range = ChefDelivery::where('user_id',$user_id)->value('delivery_range');
		$latitudeTo = $profile->latitude;
		$longitudeTo = $profile->longitude;
		
		$distance = $this->twopoints_on_earth($latitudeFrom, $longitudeFrom,
                                    $latitudeTo,  $longitudeTo);
		$distance = sprintf('%0.2f',$distance);
		if($distance<=$range){
			$response = [
				'success' => true,
				//'distance_from_chef' => $distance,
				'delivery_range' => true
			];
		}
		else
		{
			$response = [
				'success' => true,
				//'distance_from_chef' => $distance,
				'delivery_range' => false
			];
		}
		return response()->json($response, 200);
		
    }
	private  function twopoints_on_earth($latitudeFrom, $longitudeFrom,
                                    $latitudeTo,  $longitudeTo)
    {
           $long1 = deg2rad($longitudeFrom);
           $long2 = deg2rad($longitudeTo);
           $lat1 = deg2rad($latitudeFrom);
           $lat2 = deg2rad($latitudeTo);
              
           //Haversine Formula
           $dlong = $long2 - $long1;
           $dlati = $lat2 - $lat1;
              
           $val = pow(sin($dlati/2),2)+cos($lat1)*cos($lat2)*pow(sin($dlong/2),2);
              
           $res = 2 * asin(sqrt($val));
              
           $radius = 3958.756;
              
           return  1.609344*($res*$radius);
      }
}
