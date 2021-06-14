<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Validator;
use DB;
use App\Models\MenuMedia;

class ChefScheduledDishesController extends Controller
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
		 'day' => 'required|integer',   
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
		$day = $request->day;
		
		$menus = DB::table('menus')
				->join('menu_availability','menus.id','=','menu_availability.menu_id')
				->where([
				['menus.user_id',$chef_id],
				['menus.status','active'],
				['menu_availability.status','active'],
				['menu_availability.day',$day],
				])->orderBy('menus.sequence','asc')
				->select('menus.id as menu_id','menus.name as menu_name','menus.image as menu_image','price','start_time','end_time','cutoff_time','unit')
				->get();
		/*$data = [];		
		foreach($menus as $menu){
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
		*/		
		 $response = [
            'success' => true,
            'data' => $menus
			
        ];
        return response()->json($response, 200);
    }
}
