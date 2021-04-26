<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Validator;
use Illuminate\Support\Facades\DB;
use App\Models\Menu;
use App\Models\MenuTag;
use App\Models\MenuMedia;
use App\Models\MenuAvailability;

class DishDetailsController extends Controller
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
		$data['ingredients'] = $menu->ingredients;
		$data['image'] = $menu->image;
		$data['price'] = $menu->price;
		$data['instructions'] = $menu->instructions;
		$data['max_portions'] = $menu->max_portions;
		$data['customer_prep_time'] = $menu->customer_prep_time;
		//menu media
		$menuMedias = MenuMedia::where('menu_id',$menu_id)
					->select('link','type')
					->get();
		$data['media'] = $menuMedias;
		//menu tags
		$menuTags = DB::table('menu_tags')
							->join('tags', 'menu_tags.tag_id','=','tags.id')
							->where('menu_id',$menu_id)
							->select('tags.name','tags.category')
							->get();
		$data['tags'] = $menuTags;
		//menu availability
		$menuAvailability = MenuAvailability::where('menu_id', $menu_id)
									->select('day','start_time','end_time','cutoff_time','unit','status')
									->orderBy('day')->get();
		$data['availability'] = $menuAvailability;
		//chef other dishes
		$menus = DB::table('menus')
					->where([
					['menus.user_id',$user_id],
					['menus.status','active'],
					['menus.id','<>',$menu_id],
					])->orderBy('menus.sequence','asc')
					->select('menus.id as menu_id','menus.name as menu_name','menus.image as menu_image','price')
					->get();
		$data['other_dishes'] = $menus;
		$response = [
            'success' => true,
            'data' =>  $data
        ];
        return response()->json($response, 200);
    }
}
