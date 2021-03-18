<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\KitchenMedia;
use Illuminate\Http\Request;
use Validator;
use Storage;
use Auth;
use Illuminate\Support\Facades\DB;
use Vimeo\Laravel\Facades\Vimeo;

class KitchenMediaController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $user_id = Auth::id();
		
		$kitchenMedias = KitchenMedia::where('user_id',$user_id)
					->get();
		$response = [
            'success' => true,
            'data' =>  $kitchenMedias
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
			'image' => 'required_without:video|image|mimes:jpeg,png,jpg,gif,svg|max:5120',
			'video' =>'required_without:image|max:102400',
			'description' => 'required|string',
	
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
		$type = $request->type;
		$description = $request->description;
		
		if($type == 'image' && $request->hasFile('image') ){
			$count = KitchenMedia::where([
							['user_id',$user_id],
							['type',$type]
							])->count();
			if($count >= 10)
			{
				$response = [
					'success' => false,
					'message' => 'You can upload maximum 10 images'
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
					
					$kitchenMedia = new KitchenMedia;
					$kitchenMedia->link = $path;
					$kitchenMedia->type = $type;
					$kitchenMedia->description = $description;
					$kitchenMedia->user_id = $user_id;
					$kitchenMedia->save();
					$response = [
						'success' => true,
						'message' => 'Kitchen Media image uploaded successfully'
					];
				} 
				
			}
			else if($type == 'video' && $request->hasFile('video')){
				$file = $request->video;  
				$mime = $file->getMimeType();
				$fileType = explode('/',$mime)[0];
				if($fileType === 'video'){
					$count = KitchenMedia::where([
								['user_id',$user_id],
								['type',$type]
								])->count();
					if($count >= 5)
					{
						$response = [
							'success' => false,
							'message' => 'You can upload maximum 5 videos'
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
								
								$kitchenMedia = new KitchenMedia;
								$kitchenMedia->link = $path;
								$kitchenMedia->type = $type;
								$kitchenMedia->description = $description;
								$kitchenMedia->user_id = $user_id;
								$kitchenMedia->save();
							$response = [
								'success' => true,
								'message' => 'Kitchen Media video uploaded successfully'
							];
							}
							else {
								$response = [
								'success' => false,
								'message' => 'Kitchen Media video upload error'
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
     * @param  \App\Models\KitchenMedia  $kitchenMedia
     * @return \Illuminate\Http\Response
     */
    public function show(KitchenMedia $kitchenMedia)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\KitchenMedia  $kitchenMedia
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, KitchenMedia $kitchenMedia)
    {
        $validator = Validator::make($request->all(), [
			'description' => 'required|string',
	
        ]);
        if ($validator->fails()) {
            $response = [
                'success' => false,
                'data' => $validator->errors(),
                'message' => 'Validation Error.'
            ];
            return response()->json($response, 200);
        }
		$description = $request->description;
		$kitchenMedia->description = $description;
		$kitchenMedia->save();
		
		 $response = [
            'success' => true,
            'message' => 'Kitchen media updated successfully.'
        ];
        return response()->json($response, 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\KitchenMedia  $kitchenMedia
     * @return \Illuminate\Http\Response
     */
    public function destroy(KitchenMedia $kitchenMedia)
    {
        try{
	
            if(isset($kitchenMedia->link) && $kitchenMedia->type == 'image')
			{
				$mimage = basename($kitchenMedia->link);
				Storage::delete("public/images/{$mimage}");
				$kitchenMedia->delete();
			}
			else  if(isset($kitchenMedia->link) && $kitchenMedia->type == 'video'){
				$mimage = basename($kitchenMedia->link);
				$uri = '/videos/'.$mimage;
				Vimeo::request($uri,[], 'DELETE');
				$kitchenMedia->delete();
			}

			
		}
		catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 200);
		}
		 $response = [
            'success' => true,
            'message' => 'Kitchen media deleted successfully.'

        ];
        return response()->json($response, 200);
    }
}
