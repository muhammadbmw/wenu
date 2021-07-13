<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Profile;
use Validator;
use Illuminate\Support\Facades\DB;
use App\Models\MenuMedia;

class ScheduledDishesController extends Controller
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
        'day' => 'required|integer',
		'latitude' => 'required|string',
        'longitude' => 'required|string'
      
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
		$day = $request->day;
		//get all the chefs latitude and longitude
		$chefs = DB::table('users')
							->join('profiles','users.id','=','profiles.user_id')
							->where([
							  ['users.status','active'],
							  ['users.chef_status', 1]
							])
							->select('users.id','users.name','profiles.latitude','profiles.longitude','profiles.image')
							->get();
		//get all the chefs within the range 15kms
		$chefs_in_range = [];
		foreach($chefs as $chef)
		{
			$latitudeTo = $chef->latitude;
			$longitudeTo = $chef->longitude;
		
			$distance = $this->twopoints_on_earth($latitudeFrom, $longitudeFrom,
                                    $latitudeTo,  $longitudeTo);
			$chef->distance = round($distance,2).' km(s)';
			if($distance<=15){
				array_push($chefs_in_range,$chef);
			}
				
		}
		$data = [];
		foreach($chefs_in_range as $chef){
			$user_id = $chef->id;
			$chef_name = $chef->name;
			$chef_image = $chef->image;
			$distance = $chef->distance;
			$menus = DB::table('menus')
						->join('menu_availability','menus.id','=','menu_availability.menu_id')
						->where([
						['menus.user_id',$user_id],
						['menus.status','active'],
						['menu_availability.status','active'],
						['menu_availability.day',$day],
						])->orderBy('menus.sequence','asc')
						->select('menus.id as menu_id','menus.name as menu_name','menus.image as menu_image','price','start_time','end_time','cutoff_time','unit')
						->get();
			foreach($menus as $menu)
			{
				$menu->chef_id = $user_id;
				$menu->chef_name = $chef_name;
				$menu->chef_image = $chef_image;
				$menu->distance = $distance;
				$menu_id = $menu->menu_id;
				//menu media
				$links = MenuMedia::where([
					['menu_id',$menu_id],
					['type','image']
					])->pluck('link')->toArray();
				if(!is_null($menu->menu_image))
					array_unshift($links, $menu->menu_image);
				$menu->menu_image = $links;
				
				array_push($data,$menu);
			}
			// if($menus->isNotEmpty())
				// array_push($data,$menus);
		 }
		
		
		 $response = [
            'success' => true,
            'data' => $data
			
        ];
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
