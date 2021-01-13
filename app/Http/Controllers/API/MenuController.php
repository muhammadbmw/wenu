<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Models\Menu;
use Illuminate\Http\Request;
use Validator;
use Storage;
use Auth;

class MenuController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $user_id = Auth::id();
		$menus = Menu::where('user_id',$user_id)
						->orderBy('id','desc')
						->get();
		 $response = [
            'success' => true,
            'data' => $menus
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
			'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'ingredients' => 'nullable|string',
            'price' => 'required|numeric',
	
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
		$menu->price = $request->price;
		$menu->user_id = Auth::id();
		if ($request->filled('ingredients')) {
			$menu->ingredients = $request->ingredients;
		}
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
        }
		$menu->save();
		
		 $response = [
            'success' => true,
            'message' => 'Menu created successfully.'
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
        //
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
			'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'ingredients' => 'nullable|string',
            'price' => 'nullable|numeric',
			'status' => 'nullable|string',
	
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
			 //delete the previous image.
			if(isset($menu->image))
			{
				$mimage = basename($menu->image);
				Storage::delete("public/images/{$mimage}");
			}
            $menu->image = $path;
        }
		$menu->save();
		
		 $response = [
            'success' => true,
            'message' => 'Menu updated successfully.'
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
         try{
            if(isset($menu->image))
			{
				$mimage = basename($menu->image);
				Storage::delete("public/images/{$mimage}");
			}

			$menu->delete();
		}
		catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 200);
		}
		 $response = [
            'success' => true,
            'message' => 'Menu deleted successfully.'

        ];
        return response()->json($response, 200);
    }
}
