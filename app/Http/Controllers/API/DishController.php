<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use Illuminate\Http\Request;
use Validator;
use Storage;
use Auth;
use Illuminate\Support\Facades\DB;

class DishController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
         $user_id = Auth::id();
		 $dishes = Menu::where('user_id',$user_id)
						->orderBy('sequence','asc')
					->get();
		$response = [
            'success' => true,
            'data' =>  $dishes
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
            'name' => 'required',
			'description' => 'required',
			//'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'ingredients' => 'nullable|string',
            'price' => 'required|numeric',
			'instructions' =>'nullable|string',
			'max_portions' =>'required|integer',
			'customer_prep_time' =>'nullable|integer'
	
        ]);
        if ($validator->fails()) {
            $response = [
                'success' => false,
                'data' => $validator->errors(),
                'message' => 'Validation Error.'
            ];
            return response()->json($response, 200);
        }
		$menu = new Menu;
		$menu->name = $request->name;
		$menu->description = $request->description;
		$menu->max_portions = $request->max_portions;
		$menu->price = $request->price;
		//$menu->status = $request->status;
		$menu->user_id = Auth::id();
		$menu->sequence = Menu::where('user_id', Auth::id())->count();
		if ($request->filled('instructions')) {
			$menu->instructions = $request->instructions;
		}
		if ($request->filled('ingredients')) {
			$menu->ingredients = $request->ingredients;
		}
		if ($request->filled('customer_prep_time')) {
			$menu->customer_prep_time = $request->customer_prep_time;
		}
		if ($request->filled('options')) {
			$menu->options = $request->options;
		}
		/*
		 if($request->hasFile('image')){
            //get image file.
           $image = $request->image;   
            //get just extension.
            $ext = $image->getClientOriginalExtension();           
            //make a unique name
            $filename = uniqid().'.'.$ext;          
            //upload the image
            $image->storeAs('public/images',$filename);
			$path = asset('public/storage/images/'.$filename);
            $menu->image = $path;
        } */
		$menu->save();
		$id = $menu->id;
		 $response = [
            'success' => true,
			'id' => $id,
            'message' => 'Dish created successfully.'
        ];
        return response()->json($response, 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Menu  $menu
     * @return \Illuminate\Http\Response
     */
    public function show(Menu $menu)
    {
        $response = [
            'success' => true,
            'data' =>  $menu
        ];
        return response()->json($response, 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Menu  $menu
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Menu $menu)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string',
			'description' => 'nullable|string',
			//'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'ingredients' => 'nullable|string',
            'price' => 'nullable|numeric',
			'status' => 'nullable|string',
			'instructions' =>'nullable|string',
			'max_portions' =>'nullable|integer',
			'customer_prep_time' =>'nullable|integer'
	
        ]);
		
        if ($validator->fails()) {
            $response = [
                'success' => false,
                'data' => $validator->errors(),
                'message' => 'Validation Error.'
            ];
            return response()->json($response, 200);
        }
		if ($request->filled('name')) {
			$menu->name = $request->name;
		}
		if ($request->filled('description')) {
			$menu->description = $request->description;
		}
		if ($request->filled('price')) {
			$menu->price = $request->price;
		}
		if ($request->filled('status')) {
			$menu->status = $request->status;
		}
		
		if ($request->filled('ingredients')) {
			$menu->ingredients = $request->ingredients;
		}
		if ($request->filled('instructions')) {
			$menu->instructions = $request->instructions;
		}
		if ($request->filled('customer_prep_time')) {
			$menu->customer_prep_time = $request->customer_prep_time;
		}
		if ($request->filled('max_portions')) {
			$menu->max_portions = $request->max_portions;
		}
		if ($request->filled('options')) {
			$menu->options = $request->options;
		}
		/* if($request->hasFile('image')){
            //get image file.
           $image = $request->image;   
            //get just extension.
            $ext = $image->getClientOriginalExtension();           
            //make a unique name
            $filename = uniqid().'.'.$ext;          
            //upload the image
            $image->storeAs('public/images',$filename);
			$path = asset('public/storage/images/'.$filename);
			 //delete the previous image.
			if(isset($menu->image))
			{
				$mimage = basename($menu->image);
				Storage::delete("public/images/{$mimage}");
			}
            $menu->image = $path;
        } */
		$menu->save();
		
		 $response = [
            'success' => true,
            'message' => 'Dish updated successfully.'
        ];
        return response()->json($response, 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Menu  $menu
     * @return \Illuminate\Http\Response
     */
    public function destroy(Menu $menu)
    {
        //
    }
	//update dish defualt image
	public function update_dish(Request $request)
    {
		 $validator = Validator::make($request->all(), [
            'id' => 'required|integer',
			//'link' => 'required|string',
			'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:5120',
        ]);
		
        if ($validator->fails()) {
            $response = [
                'success' => false,
                'data' => $validator->errors(),
                'message' => 'Validation Error.'
            ];
            return response()->json($response, 200);
        }
		$id =  $request->id;
		//$link =  $request->link;
		$menu = Menu::where('id',$id)->first();
		if($menu){
			$image = $request->image;   
            $ext = $image->getClientOriginalExtension();           
            $filename = uniqid().'.'.$ext;          
            //upload the image
            $image->storeAs('public/images',$filename);
			$path = asset('public/storage/images/'.$filename);
			 //delete the previous image.
			if(isset($menu->image))
			{
				$mimage = basename($menu->image);
				Storage::delete("public/images/{$mimage}");
			}
            $menu->image = $path;
			$menu->save();
			$response = [
            'success' => true,
            'message' => 'Dish updated successfully.'
			];
		} 
		else {
			$response = [
            'success' => false,
            'message' => 'No Dish'
			];
		}
        return response()->json($response, 200);
	}
}
