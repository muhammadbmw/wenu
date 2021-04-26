<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\KitchenMedia;
use Validator;
use DB;

class ChefKitchenController extends Controller
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
		'chef_id' => 'required|integer',
      
		]);
		if ($validator->fails()) {
			$response = [
				'success' => false,
				'data' => $validator->errors(),
				'message' => 'Validation Error.'
			];
			return response()->json($response, 200);
		}
		
		$chef_id = $request->chef_id;
		//get chef details info
		$chefs = DB::table('users')
				->join('profiles','users.id','=','profiles.user_id')
				->where('users.id',$chef_id)
				->select('users.name','profiles.image')
				->first();
		//get chef kitchen medias
		$kitchenMedias = KitchenMedia::where('user_id',$chef_id)
						->select('link','type','description')
						->get();
		$data = [];
		$data['chef_name'] = $chefs->name;
		$data['chef_image'] = $chefs->image;
		$data['kitchen'] = $kitchenMedias;
		
		$response = [
            'success' => true,
            'data' => $data
			
        ];
        return response()->json($response, 200);
    }
}
