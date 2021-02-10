<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MenuGroupRel;
use Validator;

class MenuGroupRelController extends Controller
{
    public function add_or_remove(Request $request)
    {
		$validator = Validator::make($request->all(), [
            'menu_group_id' => 'required|integer',
			'menu_id' => 'required|integer',
			'operation'		=> 'required|string',
	
        ]);
        if ($validator->fails()) {
            $response = [
                'success' => false,
                'data' => $validator->errors(),
                'message' => 'Validation Error.'
            ];
            return response()->json($response, 200);
        }
		$menu_group_id = $request->menu_group_id;
		$menu_id = $request->menu_id;
		$op = $request->operation;
		$response = [];
		if($op == 'add') {
			$menuGroupRel = new MenuGroupRel;
			$menuGroupRel->menu_group_id = $menu_group_id;
			$menuGroupRel->menu_id = $menu_id;
			$menuGroupRel->save();
			$response = [
            'success' => true,
            'message' => 'Menu item added.'
			];
		}
		if($op == 'remove') {
			try{
			$menuGroupRel = MenuGroupRel::where([
												['menu_group_id',$menu_group_id],
												['menu_id',$menu_id]
										])->first();
			$menuGroupRel->delete();
			}
			catch (\Exception $e) {
				return response()->json(['error' => $e->getMessage()], 200);
			}
			$response = [
            'success' => true,
            'message' => 'Menu item removed.'
			];
		}
		 
        return response()->json($response, 200);
	}
}
