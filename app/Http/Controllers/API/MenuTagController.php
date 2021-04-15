<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\MenuTag;
use Illuminate\Http\Request;
use Validator;
use Illuminate\Support\Facades\DB;

class MenuTagController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
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
		
		$menuTags = DB::table('menu_tags')
							->join('tags', 'menu_tags.tag_id','=','tags.id')
							->where('menu_id',$menu_id)
							->select('menu_tags.id','tags.name','tags.category')
							->get();
		$response = [
            'success' => true,
            'data' =>   $menuTags
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
            'menu_id' =>'required|integer',
			'tags' => 'required|array|max:10',

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
		$tags = $request->input('tags');
		$menu_tag_count = MenuTag::where('menu_id',$menu_id)->count();
		$tags_count = count($tags);
		if($menu_tag_count + $tags_count <=10)
		{
			foreach($tags as $tag) {
				$check = MenuTag::where([
										['menu_id',$menu_id],
										['tag_id',$tag]
									])->first();
				if($check)
					continue;
				else{
					$menuTag = new MenuTag;	
					$menuTag->tag_id =  $tag;
					$menuTag->menu_id =  $menu_id;
					$menuTag->save();
				}
			}
			
			$response = [
				'success' => true,
				'message' => 'Menu Tag created successfully'
			];
		}
		else {
				$response = [
				'success' => false,
				'message' => 'Maximum limit 10'
			];
		}
		 return response()->json($response, 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\MenuTag  $menuTag
     * @return \Illuminate\Http\Response
     */
    public function show(MenuTag $menuTag)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\MenuTag  $menuTag
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, MenuTag $menuTag)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\MenuTag  $menuTag
     * @return \Illuminate\Http\Response
     */
    public function destroy(MenuTag $menuTag)
    {
        $menu_id = $menuTag->menu_id;
		try{
          
			$menuTag->delete();
		}
		catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 200);
		}
		
		 $menuTags = DB::table('menu_tags')
							->join('tags', 'menu_tags.tag_id','=','tags.id')
							->where('menu_id',$menu_id)
							->select('menu_tags.id','tags.name','tags.category')
							->get();
		 $response = [
            'success' => true,
			 'data' =>   $menuTags,
            'message' => 'Menu tag deleted successfully.'

        ];
        return response()->json($response, 200);
    }
}
