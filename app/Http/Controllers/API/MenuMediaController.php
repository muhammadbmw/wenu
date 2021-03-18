<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\MenuMedia;
use Illuminate\Http\Request;
use Validator;
use Storage;
use Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Menu;
use Vimeo\Laravel\Facades\Vimeo;

class MenuMediaController extends Controller
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
		
		 $menuMedias = MenuMedia::where('menu_id',$menu_id)
					->get();
		$response = [
            'success' => true,
            'data' =>  $menuMedias
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
            'type' => 'required|string',
			'menu_id' =>'required|integer',
			'image' => 'required_without:video|image|mimes:jpeg,png,jpg,gif,svg|max:5120',
			'video' =>'required_without:image|max:102400',
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
		$type = $request->type;
		if($type == 'image' && $request->hasFile('image')){
			$count = MenuMedia::where([
								['menu_id',$menu_id],
								['type',$type]
								])->count();
			if($count >= 5)
			{
				$response = [
					'success' => false,
					'message' => 'You can upload maximum 5 images per dish'
				];
				
			}
			else {				
					//get image file.
				   $image = $request->image;   
					//get just extension.
					$ext = $image->getClientOriginalExtension();           
					//make a unique name
					$filename = uniqid().'.'.$ext;          
					//upload the image
					$image->storeAs('public/images',$filename);
					$path = asset('public/storage/images/'.$filename);
					
					$menuMedia = new MenuMedia;
					$menuMedia->link = $path;
					$menuMedia->type = $type;
					$menuMedia->menu_id = $menu_id;
					$menuMedia->save();
					$response = [
						'success' => true,
						'message' => 'Media created successfully'
					];			
			}
			
			
		}
		else if($type == 'video' && $request->hasFile('video')){
				$file = $request->video;  
				$mime = $file->getMimeType();
				$fileType = explode('/',$mime)[0];
				if($fileType === 'video'){
					$count =  MenuMedia::where([
								['menu_id',$menu_id],
								['type',$type]
								])->count();
					if($count >= 2)
					{
						$response = [
							'success' => false,
							'message' => 'You can upload maximum 2 videos'
						];
						
					}
					else {
						$file_name = $file->getPathName();  
							$uri = Vimeo::upload($file_name, array(
								'name' => uniqid('wenu')
							));
							$video_data = Vimeo::request($uri);
							if ($video_data['status'] == 200) {
								$link = $video_data['body']['link'];
								$video_link = explode("/",$link);
								$path = "https://player.vimeo.com/video/".$video_link [3];
								
								$menuMedia = new MenuMedia;
								$menuMedia->link = $path;
								$menuMedia->type = $type;
								$menuMedia->menu_id = $menu_id;
								$menuMedia->save();
								$response = [
									'success' => true,
									'message' => 'Menu Media video uploaded successfully'
								];
							}
							else {
								$response = [
								'success' => false,
								'message' => 'Menu Media video upload error'
								];
							}
					}
				} else {
						$response = [
						'success' => false,
						'message' => 'Did not get the video file'
						];
				}
			}
		else {
				$response = [
					'success' => false,
					'message' => 'Did not get the file'
				];
		}
		return response()->json($response, 200);
		
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\MenuMedia  $menuMedia
     * @return \Illuminate\Http\Response
     */
    public function show(MenuMedia $menuMedia)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\MenuMedia  $menuMedia
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, MenuMedia $menuMedia)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\MenuMedia  $menuMedia
     * @return \Illuminate\Http\Response
     */
    public function destroy(MenuMedia $menuMedia)
    {
        try{
            if(isset($menuMedia->link) && $menuMedia->type == 'image')
			{
				$mimage = basename($menuMedia->link);
				Storage::delete("public/images/{$mimage}");
				$menuMedia->delete();
			}
			else if(isset($menuMedia->link) && $menuMedia->type == 'video')
			{
				$mimage = basename($menuMedia->link);
				$uri = '/videos/'.$mimage;
				Vimeo::request($uri,[], 'DELETE');
				$menuMedia->delete();
			}
			
		}
		catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 200);
		}
		 $response = [
            'success' => true,
            'message' => 'Menu media deleted successfully.'

        ];
        return response()->json($response, 200);
    }
}
