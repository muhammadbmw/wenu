<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Profile;
use App\Models\FoodSafety;
use App\Models\SocialLogin;
use App\Models\StripeAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Auth\Events\Registered;
use Validator;
use Stripe;
use Storage;
use Illuminate\Support\Facades\Http;

class UserController extends Controller
{
  public function register(Request $request) 
  {
    $validator = Validator::make($request->all(), [
        'name' => 'required',
        'email' => 'required|email:rfc,dns|unique:users',
        'password' => 'required|string',
        'role' => 'required|string'
      
    ]);
    if ($validator->fails()) {
        $response = [
            'success' => false,
            'data' => $validator->errors(),
            'message' => 'Validation Error.'
        ];
        return response()->json($response, 200);
    }
    $input = $request->all();
    $input['password'] = bcrypt($input['password']);
    $user = User::create($input);
    $success['token'] = $user->createToken('wenu')->accessToken;
    $success['name'] = $user->name;
    $success['role'] = $user->role;
    $response = [
        'success' => true,
        'data' => $success,
        'message' => 'User register successfully.'
    ];
    return response()->json($response, 200);

  }
 public function chef_register(Request $request) 
  {
    $validator = Validator::make($request->all(), [
        'name' => 'required',
		'first_name' => 'nullable',
		'last_name' => 'nullable',
        'email' => 'required|email:rfc,dns|unique:users',
        'password' => 'required|string',
		'city' => 'required',
		'address' => 'required',
		'province' => 'required',
		'unit' => 'nullable',
		'mobile' => 'required',
		'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:5120',
		'postal_code' => 'required|string|min:6|max:7',
    ]);
    if ($validator->fails()) {
        $response = [
            'success' => false,
            'data' => $validator->errors(),
            'message' => 'Validation Error.'
        ];
        return response()->json($response, 200);
    }
	$user = new User;
	$user->name = $request->name;
	if ($request->filled('first_name')) {
		$user->first_name = $request->first_name;
	}
	if ($request->filled('last_name')) {
		$user->last_name = $request->last_name;
	}
	$user->email = $request->email;
	$user->password = bcrypt($request->password);
	//$user->status = 'pending';
	$user->status = 'active';
	$user->login_type = 'Registration';
    $user->save();
	$user_id = $user->id;
	$profile = new Profile;
	$profile->city = $request->city;
	$profile->address = $request->address;
	$profile->province = $request->province;
	$profile->timezone = $this->get_time_zone($request->province);
	$profile->postal_code = $request->postal_code;
	if ($request->filled('latitude')) {
		$profile->latitude = $request->latitude;
	}
	if ($request->filled('longitude')) {
		$profile->longitude = $request->longitude;
	}
	if ($request->filled('unit')) {
		$profile->unit = $request->unit;
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
            $profile->image = $path;
    }
	$profile->mobile = $request->mobile;
	$profile->user_id = $user_id;
	$profile->save();
	
	//upload food safety certificate
	if ($request->filled('expiration_date') && $request->hasFile('file') ) {
		$foodSafety = new FoodSafety;
		$foodSafety->user_id = $user_id;
		$foodSafety->expiration_date = $request->expiration_date;
		$file = $request->file;   
		//get just extension.
		$ext = $file->getClientOriginalExtension();           
		//make a unique name
		$filename = uniqid().'.'.$ext;          
		$file->storeAs('public/images',$filename);
		$path = asset('public/storage/images/'.$filename);
		$foodSafety->file = $path;
		$foodSafety->save();
	}
	
	//create stripe account 
	Stripe\Stripe::setApiKey(env('STRIPE_SECRET'));
	$account = Stripe\Account::create([
	  'country' => 'CA',
	  'type' => 'express',
	   'capabilities' => [
		'card_payments' => [
		  'requested' => true,
		],
		'transfers' => [
		  'requested' => true,
		],
	  ],
	]);

	$account_id = $account->id;
	$stripeAccount = new StripeAccount;
	$stripeAccount->account_id = $account_id;
	$stripeAccount->details_submitted = 0;
	$stripeAccount->user_id = $user_id;;
	$stripeAccount->save();
	
	//send notification 
	event(new Registered($user));
    
    $response = [
        'success' => true,
        'message' => 'Registration successful.'
    ];
    return response()->json($response, 200);

  }
  public function login(Request $request)
  {
      $validator = Validator::make($request->all(), [
          'email' => 'required|email',
          'password' => 'required',

      ]);
      if ($validator->fails()) {
          $response = [
              'success' => false,
              'data' => $validator->errors(),
              'message' => 'Validation Error.'
          ];
          return response()->json($response, 200);
      }
      if (Auth::attempt(['email' => request('email'), 'password' => request('password')])) {
        $user = Auth::user();
		
			if($user->role!='admin' && !$user->hasVerifiedEmail()) {
				$response = [
				'success' => false,
				'message' => 'unverified'];
				return response()->json($response, 200);
			}
			if($user->status != 'inactive'){
				$success['token'] = $user->createToken('access_token')->accessToken;
				$success['user_id'] = $user->id;
				$success['name'] = $user->name;
				$success['role'] = $user->role;
				$success['status'] = $user->status;
				$profile = $user->profile;
				if($profile){
					$success['image'] = $profile->image;
				}
            
				$response = [
				'success' => true,
				'data' => $success,
				'message' => 'User login successfully.'];
				return response()->json($response, 200);
			}
			else {
				 $response = [
				'success' => false,
				'message' => 'User is not active.'];
				return response()->json($response, 200);
			}
		} else {
				 $response = [
			'success' => false,
			'message' => 'User does not exist or wrong credentials! If you are a new user, please click Sign-up.'];
			 return response()->json($response, 200);
		}

    }
	
	//logout
	public function logout(Request $request)
    {
       $request->user()->token()->revoke();
		$response = [
            'success' => true,
            'message' => 'Successfully logged out'
        ];
        return response()->json($response, 200);
		
    }
	
	
	//get chef profile
	public function chef_profile()
	{
		$user = Auth::user();
		$profile = $user->profile;
		$foodSafety = FoodSafety::where('user_id',$user->id)
					->first();
		if($foodSafety){
			$food_certificate_file = $foodSafety->file;
			$food_certificate_expiration_date = $foodSafety->expiration_date;
			$food_certificate_status = $foodSafety->status;
		}
		else {
			$food_certificate_file = "";
			$food_certificate_expiration_date = "";
			$food_certificate_status = "";
		}
		$stripeAccount = StripeAccount::where('user_id',$user->id)->first();
		if($stripeAccount) {
			$details = $stripeAccount->details_submitted;
			if($details)
				$onboard = true;
			else 
				$onboard = false;
		}
		else {
			$onboard = false;
		}
		 
		$data = [ 'name' => $user->name,'first_name' => $user->first_name,'last_name' => $user->last_name,'email' => $user->email,'city' => $profile->city, 'address' => $profile->address,
				 'unit' => $profile->unit,'province' => $profile->province, 'postal_code' => $profile->postal_code,
				 'mobile' => $profile->mobile, 'image' => $profile->image,
				 'latitude' => $profile->latitude, 'longitude' => $profile->longitude,
				 'food_certificate_file' => $food_certificate_file, 'food_certificate_expiration_date' => $food_certificate_expiration_date,'food_certificate_status' => $food_certificate_status,'onboard' => $onboard
				];
		 
		 $response = [
            'success' => true,
            'data' => $data
			
        ];
        return response()->json($response, 200);
	}
	
	//update chef profile
	public function update_chef_profile(Request $request)
	{
		 $user = Auth::user();
		 $profile = $user->profile;
		
		if ($request->filled('name')) {
			$user->name = $request->name;
		}
		if ($request->filled('first_name')) {
			$user->first_name = $request->first_name;
		}
		if ($request->filled('last_name')) {
			$user->last_name = $request->last_name;
		}
		if ($request->filled('password')) {
			$user->password = bcrypt($request->password);
		}
		$user->save();
		
		if ($request->filled('city')) {
			$profile->city = $request->city;
		}
		if ($request->filled('address')) {
			$profile->address = $request->address;
		}
		if ($request->filled('province')) {
			$profile->province = $request->province;
		}
		if ($request->filled('postal_code')) {
			$profile->postal_code = $request->postal_code;
		}
		if ($request->filled('latitude')) {
			$profile->latitude = $request->latitude;
		}
		if ($request->filled('longitude')) {
			$profile->longitude = $request->longitude;
		}
		if ($request->filled('mobile')) {
			$profile->mobile = $request->mobile;
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
			if(isset($profile->image))
			{
				$mimage = basename($profile->image);
				Storage::delete("public/images/{$mimage}");
			}
            $profile->image = $path;
        }
		$profile->save();
		
		$data = [ 'name' => $user->name,'first_name' => $user->first_name,'last_name' => $user->last_name,'city' => $profile->city, 'address' => $profile->address,
				 'province' => $profile->province, 'postal_code' => $profile->postal_code,
				 'mobile' => $profile->mobile, 'image' => $profile->image
				];
		 
		 $response = [
            'success' => true,
            'data' => $data,
			'message' => 'Profile updated successfully.'
        ];
        return response()->json($response, 200);
	}
	//social login for foodie
  public function social_login(Request $request)
  {
      $validator = Validator::make($request->all(), [
		  'provider_name' => 'required',
          'token' => 'required',
		  'provider_id' => 'required',
		  'name' => 'required',
		  'email' => 'required',

      ]);
      if ($validator->fails()) {
          $response = [
              'success' => false,
              'data' => $validator->errors(),
              'message' => 'Validation Error.'
          ];
          return response()->json($response, 200);
      }
	  $provider_name = $request->provider_name;
	  $provider_id = $request->provider_id;
	  $name = $request->name;
	  $email = $request->email;
	  
	  if($provider_name == 'facebook'){
		  $input_token = $request->token;
		  $url = "https://graph.facebook.com/v9.0/debug_token?input_token=".$input_token."&access_token=".$input_token;
		  $res =  Http::get($url);
		  if($res->successful()){
			$jsonData = $res->json();
			$valid = $jsonData['data']['is_valid'];
			if($valid){
				//check social login 
				 $socialLogin = SocialLogin::where([
							['provider_id', $provider_id],
							['provider_name',$provider_name]
							])->first();
				if($socialLogin){
						$user_id = $socialLogin->user_id;
						$user = User::where('id',$user_id)->first();
						$success['token'] = $user->createToken('access_token')->accessToken;
						$success['user_id'] = $user->id;
						$success['name'] = $user->name;
						$success['role'] = $user->role;
						$success['status'] = $user->status;
							
					   $response = [
						'success' => true,
						'data' => $success,
						'message' => 'User login successfully.'];
						return response()->json($response, 200);
				}
				else {
					 //check the email 
					$user = User::where('email',$email)->first();
					if($user){
						$type = $user->login_type;
						$response = [
						'success' => false,
						'message' => 'You have already used '.$email.' for '.$type.' login.'
						];
						return response()->json($response, 200);
					}
					else {
						$user = new User;
						$user->name = $name;
						$user->email = $email;
						$user->role = 'foodie';
						//$user->status = 'pending';
						$user->status = 'active';
						$user->login_type = 'Facebook';
						$user->save();
						$user_id = $user->id;
						$social = new SocialLogin;
						$social->provider_id = $provider_id;
						$social->provider_name = $provider_name;
						$social->user_id = $user_id;
						$social->save();
						$success['token'] = $user->createToken('access_token')->accessToken;
						$success['user_id'] = $user->id;
						$success['name'] = $user->name;
						$success['role'] = $user->role;
						$success['status'] = $user->status;
						
					   $response = [
						'success' => true,
						'data' => $success,
						'message' => 'User login successfully.'];
						return response()->json($response, 200);
					}
			   }
			}
			else {
				 $response = [
				'success' => false,
				'message' => 'Invalid token'];
				return response()->json($response, 200);
			}
				
		 }
		 else {
			  $response = [
				'success' => false,
				'message' => 'Invalid token'];
				return response()->json($response, 200);
		 }
	  }
	  if($provider_name == 'google'){
		  $id_token = $request->token;
		  $url = "https://www.googleapis.com/oauth2/v3/tokeninfo?id_token=".$id_token;
		  $res =  Http::get($url);
		  if($res->successful()){
			$jsonData = $res->json();
			$email = $jsonData['email'];
			$name = $jsonData['name'];
			$provider_id = $jsonData['sub'];
			//check social login
			 $socialLogin = SocialLogin::where([
							['provider_id', $provider_id],
							['provider_name',$provider_name]
							])->first();
			 if($socialLogin){
				$user_id = $socialLogin->user_id;
				$user = User::where('id',$user_id)->first();
				$success['token'] = $user->createToken('access_token')->accessToken;
				$success['user_id'] = $user->id;
				$success['name'] = $user->name;
				$success['role'] = $user->role;
				$success['status'] = $user->status;
					
			   $response = [
				'success' => true,
				'data' => $success,
				'message' => 'User login successfully.'];
				return response()->json($response, 200);
			 }
			 else {
				 //check the email 
				$user = User::where('email',$email)->first();
				if($user){
					$type = $user->login_type;
						$response = [
						'success' => false,
						'message' => 'You have already used '.$email.' for '.$type.' login.'
						];
						return response()->json($response, 200);
				}
				else {
					$user = new User;
					$user->name = $name;
					$user->email = $email;
					$user->role = 'foodie';
					//$user->status = 'pending';
					$user->status = 'active';
					$user->login_type = 'Google';
					$user->save();
					$user_id = $user->id;
					$social = new SocialLogin;
					$social->provider_id = $provider_id;
					$social->provider_name = $provider_name;
					$social->user_id = $user_id;
					$social->save();
					$success['token'] = $user->createToken('access_token')->accessToken;
					$success['user_id'] = $user->id;
					$success['name'] = $user->name;
					$success['role'] = $user->role;
					$success['status'] = $user->status;
					
				   $response = [
					'success' => true,
					'data' => $success,
					'message' => 'User login successfully.'];
					return response()->json($response, 200);
				}
			 }
			
		  }
		  else
		  {
			 $response = [
            'success' => false,
            'message' => 'Invalid token'];
            return response()->json($response, 200);
		  }
	  }
	 
	 
	}
	
	//foodie registration
  public function foodie_register(Request $request) 
  {
    $validator = Validator::make($request->all(), [
        'name' => 'required',
        'email' => 'required|email:rfc,dns|unique:users',
        'password' => 'required|string',
		'city' => 'required',
		'address' => 'required',
		'unit' => 'nullable',
		'province' => 'required',
		'mobile' => 'required',
		'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:5120',
		'postal_code' => 'required|string|min:6|max:7',
    ]);
    if ($validator->fails()) {
        $response = [
            'success' => false,
            'data' => $validator->errors(),
            'message' => 'Validation Error.'
        ];
        return response()->json($response, 200);
    }
	$user = new User;
	$user->name = $request->name;
	$user->email = $request->email;
	$user->password = bcrypt($request->password);
	$user->role = 'foodie';
	$user->status = 'active';
	$user->login_type = 'Registration';
    $user->save();
	$user_id = $user->id;
	$profile = new Profile;
	$profile->city = $request->city;
	$profile->address = $request->address;
	$profile->province = $request->province;
	$profile->timezone = $this->get_time_zone($request->province);
	$profile->postal_code = $request->postal_code;
	if ($request->filled('latitude')) {
		$profile->latitude = $request->latitude;
	}
	if ($request->filled('longitude')) {
		$profile->longitude = $request->longitude;
	}
	if ($request->filled('unit')) {
		$profile->unit = $request->unit;
	}
	$profile->mobile = $request->mobile;
	$profile->user_id = $user_id;
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
            $profile->image = $path;
    }
	$profile->save();
	
	//send notification 
	event(new Registered($user));
    
    $response = [
        'success' => true,
        'message' => 'Registration successful.'
    ];
    return response()->json($response, 200);

  }
  //get timezone based on province
	private function get_time_zone($region)
	{
		$timezone = null;
		$region = strtoupper($region);
		switch ($region) {
                case "AB":
                    $timezone = "America/Edmonton";
                    break;
                case "BC":
                    $timezone = "America/Vancouver";
                    break;
                case "MB":
                    $timezone = "America/Winnipeg";
                    break;
                case "NB":
                    $timezone = "America/Halifax";
                    break;
                case "NL":
                    $timezone = "America/St_Johns";
                    break;
                case "NS":
                    $timezone = "America/Halifax";
                    break;
                case "NT":
                    $timezone = "America/Yellowknife";
                    break;
                case "NU":
                    $timezone = "America/Rankin_Inlet";
                    break;
                case "ON":
                    $timezone = "America/Toronto";
                    break;
                case "PE":
                    $timezone = "America/Halifax";
                    break;
                case "QC":
                    $timezone = "America/Montreal";
                    break;
                case "SK":
                    $timezone = "America/Regina";
                    break;
                case "YT":
                    $timezone = "America/Whitehorse";
                    break;
			}
			return $timezone;
	}

}
