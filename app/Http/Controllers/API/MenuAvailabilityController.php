<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\MenuAvailability;
use App\Models\Menu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Validator;
use Storage;
use Auth;

class MenuAvailabilityController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
         $validator = Validator::make($request->all(), [
            'id' => 'required|integer',
	
        ]);
        if ($validator->fails()) {
            $response = [
                'success' => false,
                'data' => $validator->errors(),
                'message' => 'Validation Error.'
            ];
            return response()->json($response, 200);
        }
		$menu_id = $request->id;
		$menuAvailability = MenuAvailability::where('menu_id', $menu_id)
														->orderBy('day')->get();
		/*
		 $user_id = Auth::id();
		 $menus = Menu::where('user_id',$user_id)->get();
		 $data = [];
		 foreach($menus as $menu)
		 {
			  $menu_data = [];
			  $menu_id = $menu->id;
			  $name = $menu->name;
			  $status = $menu->status;
			  
			  $menu_data['id'] = $menu_id;
			  $menu_data['name'] = $name;
			  $menu_data['status'] = $status;
			  			  
			  $menuAvailability = MenuAvailability::where('menu_id', $menu_id)
														->orderBy('day')->get();
			  $menu_data['availability'] =  $menuAvailability;
			  array_push($data, $menu_data);
		 }*/
		 $response = [
            'success' => true,
            'data' => $menuAvailability

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
			'availability.*.day' => 'required|integer',
			'availability.*.start_time' => 'required|string',
			'availability.*.end_time' => 'required|string',
			'availability.*.cutoff_time' => 'required|integer',
			'availability.*.unit' => 'required|string',
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
		
		$menu_id = $request->id;
		$availability = $request->input('availability');
		//check menu have schedule or not
		$count = MenuAvailability::where('menu_id',$menu_id)->count();
		if($count == 0){
			//create menu  availability
			foreach($availability as $row) {
				$menuAvailability = new MenuAvailability;
				$menuAvailability->menu_id = $menu_id;
				$menuAvailability->day = $row['day'];
				$menuAvailability->start_time = $row['start_time'];
				$menuAvailability->end_time = $row['end_time'];
				$menuAvailability->cutoff_time = $row['cutoff_time'];
				$menuAvailability->status = $row['status'];
				$menuAvailability->unit = $row['unit'];
				$menuAvailability->save();			
			}
			
			$message = 'Availability created successfully';
		}
		else {
			//update menu availability
			foreach($availability as $row) {
				$day = $row['day'];
				$menuAvailability = MenuAvailability::where([
												['menu_id',$menu_id],
												['day',$day]
											])->first();
				$menuAvailability->start_time = $row['start_time'];
				$menuAvailability->end_time = $row['end_time'];
				$menuAvailability->cutoff_time = $row['cutoff_time'];
				$menuAvailability->unit = $row['unit'];
				$menuAvailability->status = $row['status'];
				$menuAvailability->save();			
			}
			
			$message = 'Availability updated successfully';
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
     * @param  \App\Models\MenuAvailability  $menuAvailability
     * @return \Illuminate\Http\Response
     */
    public function show(MenuAvailability $menuAvailability)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\MenuAvailability  $menuAvailability
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, MenuAvailability $menuAvailability)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\MenuAvailability  $menuAvailability
     * @return \Illuminate\Http\Response
     */
    public function destroy(MenuAvailability $menuAvailability)
    {
        //
    }
}
