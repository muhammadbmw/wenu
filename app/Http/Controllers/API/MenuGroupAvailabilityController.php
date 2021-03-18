<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\MenuGroupAvailability;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\MenuGroup;
use Validator;
use Storage;
use Auth;

class MenuGroupAvailabilityController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
         $user_id = Auth::id();
		 $menuGroups = MenuGroup::where('user_id',$user_id)->get();
		 $data = [];
		 foreach($menuGroups as $menuGroup)
		 {
			  $menuGroup_data = [];
			  $menu_group_id = $menuGroup->id;
			  $name = $menuGroup->name;
			  $status = $menuGroup->status;
			  $menuGroup_data['id'] = $menu_group_id;
			  $menuGroup_data['name'] = $name;
			  $menuGroup_data['status'] = $status;
			  
			  $menuGroupAvailability = MenuGroupAvailability::where('menu_group_id', $menu_group_id)
														->orderBy('day')->get();
			  $menuGroup_data['availability'] =  $menuGroupAvailability;
			  array_push($data, $menuGroup_data);
		 }
		 $response = [
            'success' => true,
            'data' => $data

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
			'id'	=> 'required|integer',
			'name'	=> 'required|string',
			'status'	=> 'required|string',
			'availability.*.day' => 'required|integer',
			'availability.*.start_time' => 'required|string',
			'availability.*.end_time' => 'required|string',
			'availability.*.status' => 'required|string',
		 ]);
		 
		 if ($validator->fails()) {
            $response = [
                'success' => false,
                'data' => $validator->errors(),
                'message' => 'Validation Error.'
            ];
            return response()->json($response, 200);
        }
		
		$id = $request->id;
		$name = $request->name;
		$status = $request->status;
		$user_id = Auth::id();
		$availability = $request->input('availability');
		if($id == 0) {
			//create menu group
			$menuGroup = new MenuGroup;
			$menuGroup->name = $name;
			$menuGroup->user_id = $user_id;
			$menuGroup->status = $status;
			$menuGroup->save();
			$menu_group_id = $menuGroup->id;
			//create menu group availability
			foreach($availability as $row) {
				$menuGroupAvailability = new MenuGroupAvailability;
				$menuGroupAvailability->menu_group_id = $menu_group_id;
				$menuGroupAvailability->day = $row['day'];
				$menuGroupAvailability->start_time = $row['start_time'];
				$menuGroupAvailability->end_time = $row['end_time'];
				$menuGroupAvailability->status = $row['status'];
				$menuGroupAvailability->save();			
			}
			
			$message = 'Menu category created successfully';
		}
		else {
			//update menu group
			$menu_group_id = $id;
			$menuGroup = MenuGroup::find($menu_group_id);
			$menuGroup->name = $name;
			$menuGroup->status = $status;
			$menuGroup->save();
			//update menu group availability
			foreach($availability as $row) {
				$day = $row['day'];
				$menuGroupAvailability = MenuGroupAvailability::where([
												['menu_group_id',$menu_group_id],
												['day',$day]
											])->first();
				$menuGroupAvailability->start_time = $row['start_time'];
				$menuGroupAvailability->end_time = $row['end_time'];
				$menuGroupAvailability->status = $row['status'];
				$menuGroupAvailability->save();			
			}
			
			$message = 'Menu category updated successfully';
		}
		

		$response = [
            'success' => true,
            'message' => $message

        ];
        return response()->json($response, 200);
		
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\MenuGroupAvailability  $menuGroupAvailability
     * @return \Illuminate\Http\Response
     */
    public function show(MenuGroupAvailability $menuGroupAvailability)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\MenuGroupAvailability  $menuGroupAvailability
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, MenuGroupAvailability $menuGroupAvailability)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\MenuGroupAvailability  $menuGroupAvailability
     * @return \Illuminate\Http\Response
     */
    public function destroy(MenuGroupAvailability $menuGroupAvailability)
    {
        //
    }
}
