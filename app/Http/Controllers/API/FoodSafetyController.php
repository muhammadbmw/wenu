<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\FoodSafety;
use App\Models\User;
use App\Models\Profile;
use Illuminate\Http\Request;
use Validator;
use Storage;
use Auth;
use Illuminate\Support\Facades\DB;

class FoodSafetyController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
         $user_id = Auth::id();
		 $foodSafety = FoodSafety::where('user_id',$user_id)
					->first();
		$response = [
            'success' => true,
            'data' =>  $foodSafety
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
			'expiration_date' => 'required',
			'file' => 'required|mimes:jpeg,png,jpg,pdf|max:2048',
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
		$foodSafety = FoodSafety::where('user_id',$user_id)->first();
					
		if(empty($foodSafety)){
			$foodSafety = new FoodSafety;
			$foodSafety->user_id = $user_id;
		}
		
		$foodSafety->expiration_date = $request->expiration_date;
		 if($request->hasFile('file')){
           $file = $request->file;   
            //get just extension.
            $ext = $file->getClientOriginalExtension();           
            //make a unique name
            $filename = uniqid().'.'.$ext;          
            $file->storeAs('public/images',$filename);
			$path = asset('public/storage/images/'.$filename);
            $foodSafety->file = $path;
        }
		$foodSafety->status = 'pending';
		$foodSafety->save();
		$response = [
            'success' => true,
            'message' => 'Food safety certificate uploaded successfully.'
        ];
        return response()->json($response, 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\FoodSafety  $foodSafety
     * @return \Illuminate\Http\Response
     */
    public function show(FoodSafety $foodSafety)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\FoodSafety  $foodSafety
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, FoodSafety $foodSafety)
    {
        $validator = Validator::make($request->all(), [
			'status' => 'required',
        ]);
        if ($validator->fails()) {
            $response = [
                'success' => false,
                'data' => $validator->errors(),
                'message' => 'Validation Error.'
            ];
            return response()->json($response, 200);
        }
		$foodSafety->status = $request->status;
		$foodSafety->save();
		//update chef status if satisfy
		$user_id = $foodSafety->user_id;
		$user = User::where('id',$user_id)->first();
		if($request->status == 'active'){
			$profile = Profile::where('user_id',$user_id)->first();
			if($profile){
				$user->chef_status = 1;
				if($user->foodie_status == 0)
					$user->foodie_status = 1;
				$user->save();
			}
		} else {
			$user->chef_status = 0;
			$user->save();
		}
		$response = [
            'success' => true,
            'message' => 'Status updated successfully.'
        ];
        return response()->json($response, 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\FoodSafety  $foodSafety
     * @return \Illuminate\Http\Response
     */
    public function destroy(FoodSafety $foodSafety)
    {
        try{
            if(isset($foodSafety->file))
			{
				$mimage = basename($foodSafety->file);
				Storage::delete("public/images/{$mimage}");
			}

			$foodSafety->delete();
		}
		catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 200);
		}
		 $response = [
            'success' => true,
            'message' => 'Food safety certificate deleted successfully.'

        ];
        return response()->json($response, 200);
    }
	public function chefsFoodSafety()
	{
		$foodSafety = DB::table('food_safeties as f')
						->join('users as u','f.user_id','=','u.id')
						->where('u.status','active')
						->select('f.id','u.name','u.email','expiration_date','file','f.status')->get();
		$response = [
            'success' => true,
            'data' =>  $foodSafety
        ];
        return response()->json($response, 200);
	}
}
